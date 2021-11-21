<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DoctrineEntityNormalizer implements NormalizerInterface
{
    private PropertyAccessExtractorInterface $propertyAccessExtractor;
    private PropertyAccessorInterface $propertyAccessor;
    private EntityConverterRegistry $entityConverterRegistry;
    private MetadataRegistry $metadataRegistry;
    private ?NameConverterInterface $nameConverter;

    public function __construct(
        MetadataRegistry $metadataRegistry,
        PropertyAccessExtractorInterface $propertyAccessExtractor,
        PropertyAccessorInterface $propertyAccessor,
        EntityConverterRegistry $entityConverterRegistry,
        ?NameConverterInterface $nameConverter = null
    ) {
        $this->metadataRegistry = $metadataRegistry;
        $this->propertyAccessExtractor = $propertyAccessExtractor;
        $this->propertyAccessor = $propertyAccessor;
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->nameConverter = $nameConverter;
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

    public function normalize($object, string $format = null, array $context = [])
    {
        $data = [];

        $class = \get_class($object);

        $attributes = array_filter(
            $this->metadataRegistry->getMetadataForClass($class)->getFieldNames(),
            fn ($fieldName) => $this->propertyAccessExtractor->isReadable($class, $fieldName),
        );

        $nameConverter = $context['nameConverter'] ?? $this->nameConverter;

        foreach ($attributes as $attribute) {
            $normalizedName = $nameConverter ? $nameConverter->normalize($attribute) : $attribute;
            $data[$normalizedName] = $this->propertyAccessor->getValue($object, $attribute);
        }

        return \count($data) ? $data : new \ArrayObject();
    }
}
