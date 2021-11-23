<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityConverterNormalizer implements NormalizerInterface
{
    private EntityConverterRegistry $entityConverterRegistry;
    private ScenarioInterface $scenario;
    private PropertyAccessorInterface $propertyAccessor;
    private PropertyListExtractorInterface $propertyListExtractor;
    private MetadataRegistry $metadataRegistry;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry,
        PropertyAccessorInterface $propertyAccessor,
        PropertyListExtractorInterface $propertyListExtractor,
        ScenarioInterface $scenario,
        MetadataRegistry $metadataRegistry
    ) {
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->scenario = $scenario;
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyListExtractor = $propertyListExtractor;
        $this->metadataRegistry = $metadataRegistry;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $data = [];

        $class = \get_class($data);

        $scenario = $context['scenario'] ?? $this->scenario;
        $nameConverter = $scenario->getNameConverter();

        $converter = $this->entityConverterRegistry->getConverterForClass($class, $scenario);
        $metaData = $this->metadataRegistry->getMetadataForClass($class);

        if (null !== $converter && \is_callable($converter)) {
            $converted = \call_user_func($converter, $object, $scenario);
        } elseif (null !== $metaData) {
            $converted = [];
            foreach ($metaData->getFieldNames() as $fieldName) {
                if ($this->propertyAccessor->isReadable($object, $fieldName)) {
                    $converted[$fieldName] = $this->propertyAccessor->getValue($object, $fieldName);
                }
            }
        } else {
            $converted = $object;
        }

        if (\is_object($converted)) {
            foreach ($this->propertyListExtractor->getProperties($converted) as $property) {
                if ($this->propertyAccessor->isReadable($converted, $property)) {
                    $data[$property] = $this->propertyAccessor->getValue($converted, $property);
                }
            }
        } elseif (\is_array($converted)) {
            $data = $converted;
        }

        $result = [];
        foreach ($data as $name => $value) {
            $result[$nameConverter->normalize($name)] = $value;
        }

        $expand = $context['expand'] ?? null;
        if (\is_array($expand)) {
            $expanded = $this->expand($object, $expand, $scenario);
            foreach ($expanded as $name => $value) {
                $result[$name] = $value;
            }
        }

        return \count($result) ? $result : new \ArrayObject();
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        if (\is_object($data)) {
            $class = \get_class($data);

            return null !== $this->entityConverterRegistry->getConverterForClass($class)
                || null !== $this->metadataRegistry->getMetadataForClass($class);
        }

        return false;
    }

    public function expand(
        object $object,
        array $expand,
        ScenarioInterface $scenario
    ): array {
        $result = $this->normalize($object, null, [
            'scenario' => $scenario,
            'expand' => $expand,
        ]);

        $class = \get_class($object);
        $metaData = $this->metadataRegistry->getMetadataForClass($class);
        $customExpandFields = $this->entityConverterRegistry->getCustomExpandFieldsForClass($class, $scenario);

        $expandable = [];
        if (null !== $customExpandFields) {
            $expandable = $customExpandFields->getExpandFields();
        } elseif (null !== $metaData) {
            $expandable = $metaData->getAssociationNames();
        }
        if (!\count($expandable)) {
            return [];
        }
        $nameConverter = $scenario->getNameConverter();
        $expandableNormalized = [];
        foreach ($expandable as $key => $value) {
            if (\is_string($key) && (\is_string($value) || \is_callable($value))) {
                $expandableNormalized[$nameConverter->normalize($key)] = $value;
            } elseif (\is_int($key) && \is_string($value)) {
                $expandableNormalized[$nameConverter->normalize($value)] = $value;
            }
        }
        $expandTree = [];
        if (\in_array('*', $expand)) {
            foreach ($expandableNormalized as $key => $value) {
                $expandTree[$key] = [];
            }
        }
        $expand = array_filter($expand, fn ($item) => false === strpos($item, '*'));
        foreach ($expand as $expandItem) {
            $normalizedNames = array_map(
                fn ($item) => $nameConverter->normalize($item),
                explode('.', $expandItem)
            );
            $normalizedName = array_shift($normalizedNames);
            if (!\array_key_exists($normalizedName, $expandableNormalized)) {
                continue;
            }
            if (\count($normalizedNames)) {
                $nestedExpand = implode('.', $normalizedNames);
                $expandTree[$normalizedName][$nestedExpand] = $nestedExpand;
            } else {
                $expandTree[$normalizedName] = [];
            }
        }

        foreach ($expandTree as $expandName => $nestedExpand) {
            $nestedExpand = array_values($nestedExpand);
            if (\array_key_exists($expandName, $expandableNormalized)) {
                $expandableField = $expandableNormalized[$expandName];
                if (\is_string($expandableField)) {
                    if ($this->propertyAccessor->isReadable($object, $expandableField)) {
                        $value = $this->propertyAccessor->getValue($object, $expandableField);
                        if (null !== $metaData && $metaData->hasAssociation($expandableField)) {
                            $multiple = $metaData->isCollectionValuedAssociation($expandableField);
                            if (null === $value) {
                                $result[$expandName] = null;
                            } elseif ($multiple) {
                                if ($value instanceof Collection) {
                                    $value = $value->toArray();
                                }
                                $result[$expandName] = array_map(
                                    fn ($association) => $this->expand($association, $nestedExpand, $scenario),
                                    $value
                                );
                            } else {
                                $result[$expandName] = $this->expand($value, $nestedExpand, $scenario);
                            }
                        } else {
                            $result[$expandName] = $value;
                        }
                    }
                } elseif (\is_callable($expandableField)) {
                    $value = \call_user_func($expandableField, $object, $scenario);
                    if (\is_object($value)) {
                        if ($value instanceof Collection) {
                            $result[$expandName] = array_map(
                                fn ($association) => $this->expand($association, $nestedExpand, $scenario),
                                $value->toArray()
                            );
                        } else {
                            $result[$expandName] = $this->expand($value, $nestedExpand, $scenario);
                        }
                    } else {
                        $result[$expandName] = $value;
                    }
                }
            }
        }

        return $result;
    }
}
