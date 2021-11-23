<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityConverterNormalizer implements NormalizerInterface
{
    private EntityConverterRegistry $entityConverterRegistry;
    private ScenarioInterface $scenario;
    private NormalizerInterface $normalizer;
    private PropertyAccessorInterface $propertyAccessor;
    private PropertyListExtractorInterface $propertyListExtractor;
    private RecursiveExpander $recursiveExpander;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry,
        PropertyAccessorInterface $propertyAccessor,
        PropertyListExtractorInterface $propertyListExtractor,
        ScenarioInterface $scenario,
        NormalizerInterface $normalizer,
        RecursiveExpander $recursiveExpander
    ) {
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->scenario = $scenario;
        $this->normalizer = $normalizer;
        $this->propertyAccessor = $propertyAccessor;
        $this->propertyListExtractor = $propertyListExtractor;
        $this->recursiveExpander = $recursiveExpander;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $data = [];

        $class = \get_class($data);

        $scenario = $context['scenario'] ?? $this->scenario;

        $converter = $this->entityConverterRegistry->getConverterForClass($class, $scenario);

        if (\is_callable($converter)) {
            $converted = \call_user_func($converter, $object, $scenario);
        } else {
            $converted = $object;
        }

        if (\is_object($converted)) {
            if ($this->normalizer->supportsNormalization($converted)) {
                $data = $this->normalizer->normalize($converted, null, ['scenario' => $scenario]);
            } else {
                foreach ($this->propertyListExtractor->getProperties($converted) as $property) {
                    if ($this->propertyAccessor->isReadable($converted, $property)) {
                        $data[$property] = $this->propertyAccessor->getValue($converted, $property);
                    }
                }
            }
        } elseif (\is_array($converted)) {
            $data = $converted;
        }

        $nameConverter = $scenario->getNameConverter();
        $result = [];
        foreach ($data as $name => $value) {
            $result[$nameConverter->normalize($name)] = $value;
        }

        $expand = $context['expand'] ?? null;
        if (\is_array($expand)) {
            $expanded = $this->recursiveExpander->expand($object, $expand, $scenario);
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

            return null !== $this->entityConverterRegistry->getConverterForClass($class);
        }

        return false;
    }
}
