<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderDecorator;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\DataProviderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Pagination\Paginator;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class DataProviderNormalizer implements NormalizerInterface
{
    private NormalizerInterface $normalizer;

    public function __construct(
        NormalizerInterface $normalizer
    ) {
        $this->normalizer = $normalizer;
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof DataProviderInterface) {
            throw new \InvalidArgumentException();
        }
        if ($object instanceof DataProviderDecorator) {
            if (!isset($context['scenario']) && null !== $object->getScenario()) {
                $context['scenario'] = $object->getScenario();
            }
            if (!isset($context['expand']) && null !== $object->getExpand()) {
                $context['expand'] = $object->getExpand();
            }
            if (null !== $object->getPagination()) {
                $paginator = (new Paginator())
                    ->paginate(
                        $object->getPagination(),
                        $object->getQueryBuilder(),
                        fn ($entity) => $this->normalizer->normalize($entity, $format, $context)
                    );

                return $this->normalizer->normalize($paginator, $format, $context);
            }
        }

        return array_map(
            fn ($entity) => $this->normalizer->normalize($entity, $format, $context),
            $object->getQueryBuilder()->fetchAll()
        );
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data  instanceof DataProviderInterface;
    }
}
