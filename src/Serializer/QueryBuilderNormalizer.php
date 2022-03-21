<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\Serializer;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\QueryBuilderProxy;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\QueryBuilderInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\GridApiInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class QueryBuilderNormalizer implements NormalizerInterface
{
    private GridApiInterface $gridApi;
    private NormalizerInterface $normalizer;

    public function __construct(
        GridApiInterface $gridApi,
        NormalizerInterface $normalizer
    ) {
        $this->gridApi = $gridApi;
        $this->normalizer = $normalizer;
    }

    public function normalize($object, string $format = null, array $context = []): array
    {
        $queryBuilder = ($object instanceof QueryBuilder) ? new QueryBuilderProxy($object) : $object;

        return $this->normalizer->normalize($this->gridApi->paginate($queryBuilder));
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof QueryBuilderInterface || $data instanceof QueryBuilder;
    }
}
