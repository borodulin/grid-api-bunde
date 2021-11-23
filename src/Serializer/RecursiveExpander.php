<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class RecursiveExpander
{
    private PropertyAccessorInterface $propertyAccessor;
    private MetadataRegistry $metadataRegistry;
    private EntityConverterRegistry $entityConverterRegistry;

    public function __construct(
        PropertyAccessorInterface $propertyAccessor,
        MetadataRegistry $metadataRegistry,
        EntityConverterRegistry $entityConverterRegistry
    ) {
        $this->propertyAccessor = $propertyAccessor;
        $this->metadataRegistry = $metadataRegistry;
        $this->entityConverterRegistry = $entityConverterRegistry;
    }

    public function expand(
        object $object,
        array $expand,
        ScenarioInterface $scenario
    ): array {
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
            $nestedExpand = implode('.', $normalizedNames);
            $expandTree[$normalizedName][$nestedExpand] = $nestedExpand;
        }

        $result = [];
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
