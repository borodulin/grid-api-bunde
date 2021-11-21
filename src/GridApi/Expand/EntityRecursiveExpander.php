<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Expand;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\CustomExpandInterface;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Doctrine\Common\Collections\Collection;
use Psr\Log\LoggerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityRecursiveExpander
{
    private EntityConverterRegistry $entityConverterRegistry;
    private NormalizerInterface $normalizer;
    private PropertyAccessorInterface $propertyAccessor;
    private PropertyAccessExtractorInterface $propertyAccessExtractor;
    private MetadataRegistry $metadataRegistry;
    private LoggerInterface $logger;
    private ScenarioInterface $scenario;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry,
        PropertyAccessorInterface $propertyAccessor,
        PropertyAccessExtractorInterface $propertyAccessExtractor,
        NormalizerInterface $normalizer,
        MetadataRegistry $metadataRegistry,
        ScenarioInterface $scenario,
        LoggerInterface $logger
    ) {
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->normalizer = $normalizer;
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyAccessExtractor = $propertyAccessExtractor;
        $this->metadataRegistry = $metadataRegistry;
        $this->logger = $logger;
        $this->scenario = $scenario;
    }

    public function expand(
        object $entity,
        array $expand,
        ?ScenarioInterface $scenario = null
    ) {
        $className = \get_class($entity);

        $converter = $this->entityConverterRegistry->getConverterForClass($className, $scenario);

        if (null === $converter) {
            if ($this->normalizer->supportsNormalization($entity)) {
                $result = $this->normalizer->normalize($entity);
            } else {
                return $entity;
            }
        } elseif (\is_callable($converter)) {
            $result = \call_user_func($converter, $entity, $scenario);
        } else {
            $this->logger->debug('Invalid converter', ['entity' => \get_class($entity)]);

            return $entity;
        }

        if (!\is_array($result)) {
            return $result;
        }

        if (empty($expand)) {
            return $result;
        }

        $metaData = $this->metadataRegistry->getMetadataForClass($className);
        if (null === $metaData) {
            return $result;
        }

        $expandableFields = [];
        if ($converter instanceof CustomExpandInterface) {
            foreach ($converter->getExpandFields() as $key => $value) {
                if (\is_string($key) && (\is_string($value) || \is_callable($value))) {
                    $expandableFields[$key] = $value;
                } elseif (\is_int($key) && \is_string($value)) {
                    $expandableFields[$value] = $value;
                } else {
                    $this->logger->debug('Invalid expandable item', ['key' => $key, 'value' => $value]);
                }
            }
        } else {
            foreach ($metaData->getAssociationNames() as $associationName) {
                $expandableFields[$associationName] = $associationName;
            }
        }

        $expandTree = [];
        if (\in_array('*', $expand)) {
            foreach ($expandableFields as $key => $value) {
                $expandTree[$key] = [];
            }
            foreach (array_keys($expand, '*', true) as $key) {
                unset($expand[$key]);
            }
        }

        $nameConverter = $scenario ? $scenario->getNameConverter() : $this->scenario->getNameConverter();

        foreach ($expand as $expandItem) {
            $denormalizedNames = array_map(
                fn ($item) => $this->denormalize($item, $nameConverter),
                explode('.', $expandItem)
            );
            $denormalizedName = array_shift($denormalizedNames);
            if (!\array_key_exists($denormalizedName, $expandableFields)) {
                continue;
            }
            $nestedExpand = implode('.', $denormalizedNames);
            $expandTree[$denormalizedName][$nestedExpand] = $nestedExpand;
        }

        foreach ($expandTree as $expandName => $nestedExpand) {
            $normalizedName = $this->normalize($expandName, $nameConverter);
            $nestedExpand = array_values($nestedExpand);
            if (\array_key_exists($expandName, $expandableFields)) {
                $expandableField = $expandableFields[$expandName];
                if (\is_string($expandableField)) {
                    $expandName = $expandableField;
                } elseif (\is_callable($expandableField)) {
                    $normalizedName = $this->normalize($expandName, $nameConverter);
                    $value = \call_user_func($expandableField, $entity, $scenario);
                    if (\is_object($value)) {
                        if ($value instanceof Collection) {
                            $result[$normalizedName] = array_map(
                                fn ($association) => $this->expand($association, $nestedExpand, $scenario),
                                $value->toArray()
                            );
                        } else {
                            $result[$normalizedName] = $this->expand($value, $nestedExpand, $scenario);
                        }
                    } else {
                        $result[$normalizedName] = $value;
                    }
                    continue;
                }
            }

            if ($this->propertyAccessExtractor->isReadable($className, $expandName)) {
                $value = $this->propertyAccessor->getValue($entity, $expandName);
                if ($metaData->hasAssociation($expandName)) {
                    $multiple = $metaData->isCollectionValuedAssociation($expandName);
                    if (null === $value) {
                        $result[$normalizedName] = null;
                    } elseif ($multiple) {
                        if ($value instanceof Collection) {
                            $value = $value->toArray();
                        }
                        $result[$normalizedName] = array_map(
                            fn ($association) => $this->expand($association, $nestedExpand, $scenario),
                            $value
                        );
                    } else {
                        $result[$normalizedName] = $this->expand($value, $nestedExpand, $scenario);
                    }
                } else {
                    $result[$normalizedName] = $value;
                }
            }
        }

        return $result;
    }

    private function normalize(string $propertyName, NameConverterInterface $nameConverter): string
    {
        return $nameConverter->normalize($propertyName);
    }

    private function denormalize(string $propertyName, NameConverterInterface $nameConverter): string
    {
        return $nameConverter->denormalize($propertyName);
    }
}
