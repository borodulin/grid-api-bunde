<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DoctrineEntityNormalizer implements NormalizerInterface
{
    private PropertyAccessExtractorInterface $propertyAccessExtractor;
    private PropertyAccessorInterface $propertyAccessor;
    private EntityConverterRegistry $entityConverterRegistry;
    private MetadataRegistry $metadataRegistry;
    private ScenarioInterface $scenario;
    private RecursiveExpander $recursiveExpander;

    public function __construct(
        MetadataRegistry $metadataRegistry,
        PropertyAccessExtractorInterface $propertyAccessExtractor,
        PropertyAccessorInterface $propertyAccessor,
        EntityConverterRegistry $entityConverterRegistry,
        RecursiveExpander $recursiveExpander,
        ScenarioInterface $scenario
    ) {
        $this->metadataRegistry = $metadataRegistry;
        $this->propertyAccessExtractor = $propertyAccessExtractor;
        $this->propertyAccessor = $propertyAccessor;
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->scenario = $scenario;
        $this->recursiveExpander = $recursiveExpander;
    }

    public function normalize($object, string $format = null, array $context = [])
    {
        $data = [];

        $class = \get_class($object);

        $metaData = $this->metadataRegistry->getMetadataForClass($class);

        $attributes = array_filter(
            $metaData->getFieldNames(),
            fn ($fieldName) => $this->propertyAccessExtractor->isReadable($class, $fieldName),
        );

        $scenario = $context['scenario'] ?? $this->scenario;
        $nameConverter = $scenario->getNameConverter();

        foreach ($attributes as $attribute) {
            $data[$nameConverter->normalize($attribute)] = $this->propertyAccessor->getValue($object, $attribute);
        }

        $expand = $context['expand'] ?? null;
        if (\is_array($expand)) {
            $expanded = $this->recursiveExpander->expand($object, $expand, $scenario);
            foreach ($expanded as $name => $value) {
                $data[$name] = $value;
            }
        }

        return \count($data) ? $data : new \ArrayObject();
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        if (\is_object($data)) {
            $class = \get_class($data);

            return null === $this->entityConverterRegistry->getConverterForClass($class)
                && null !== $this->metadataRegistry->getMetadataForClass($class);
        }

        return false;
    }
}
