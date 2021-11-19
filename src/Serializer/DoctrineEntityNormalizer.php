<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\Serializer\Mapping\ClassDiscriminatorResolverInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\MetadataRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;

class DoctrineEntityNormalizer extends AbstractObjectNormalizer
{
    private PropertyAccessExtractorInterface $propertyAccessExtractor;
    private PropertyAccessorInterface $propertyAccessor;
    private EntityConverterRegistry $entityConverterRegistry;
    private MetadataRegistry $metadataRegistry;

    public function __construct(
        MetadataRegistry $metadataRegistry,
        PropertyAccessExtractorInterface $propertyAccessExtractor,
        PropertyAccessorInterface $propertyAccessor,
        EntityConverterRegistry $entityConverterRegistry,
        ClassMetadataFactoryInterface $classMetadataFactory = null,
        NameConverterInterface $nameConverter = null,
        PropertyTypeExtractorInterface $propertyTypeExtractor = null,
        ClassDiscriminatorResolverInterface $classDiscriminatorResolver = null,
        callable $objectClassResolver = null,
        array $defaultContext = []
    ) {
        $this->metadataRegistry = $metadataRegistry;
        $this->propertyAccessExtractor = $propertyAccessExtractor;
        $this->propertyAccessor = $propertyAccessor;
        $this->entityConverterRegistry = $entityConverterRegistry;

        parent::__construct(
            $classMetadataFactory,
            $nameConverter,
            $propertyTypeExtractor,
            $classDiscriminatorResolver,
            $objectClassResolver,
            $defaultContext
        );
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return parent::supportsNormalization($data, $format) && $this->supports(\get_class($data));
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, string $type, string $format = null): bool
    {
        return parent::supportsDenormalization($data, $type, $format) && $this->supports($type);
    }

    protected function extractAttributes(object $object, string $format = null, array $context = []): array
    {
        $class = \get_class($object);

        return array_filter(
            $this->metadataRegistry->getMetadataForClass($class)->getFieldNames(),
            fn ($fieldName) => $this->propertyAccessExtractor->isReadable($class, $fieldName),
        );
    }

    protected function getAttributeValue(object $object, string $attribute, string $format = null, array $context = [])
    {
        return $this->propertyAccessor->getValue($object, $attribute);
    }

    protected function setAttributeValue(object $object, string $attribute, $value, string $format = null, array $context = []): void
    {
        $this->propertyAccessor->setValue($object, $attribute, $value);
    }

    private function supports(string $class): bool
    {
        return null === $this->entityConverterRegistry->getConverterForClass($class)
         && null !== $this->metadataRegistry->getMetadataForClass($class);
    }
}
