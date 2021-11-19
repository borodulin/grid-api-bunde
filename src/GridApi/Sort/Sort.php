<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\QueryBuilderEntityIterator;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\Serializer\LowerCaseNameConverter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class Sort
{
    private NameConverterInterface $nameConverter;
    private EntityConverterRegistry $entityConverterRegistry;

    public function __construct(
        EntityConverterRegistry $entityConverterRegistry
    ) {
        $this->entityConverterRegistry = $entityConverterRegistry;
        $this->nameConverter = new LowerCaseNameConverter();
    }

    public function sort(SortRequest $sortRequest, QueryBuilder $queryBuilder): QueryBuilder
    {
        $queryBuilder = clone $queryBuilder;
        $queryBuilder->resetDQLPart('orderBy');

        $sortMap = $this->getSortMap($queryBuilder);

        foreach ($sortRequest->getSortOrders() as $name => $sortOrder) {
            $name = $this->nameConverter->normalize($name);
            if (isset($sortMap[$name])) {
                $queryBuilder->addOrderBy($sortMap[$name], $sortOrder);
            }
        }

        return $queryBuilder;
    }

    private function getSortMap(QueryBuilder $queryBuilder): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator($this->nameConverter);

        foreach ($iterator->aliasIterate($queryBuilder) as $alias => $aliasItem) {
            $metadata = array_values($aliasItem)[0];

            $sortableFields = $this->entityConverterRegistry
                ->getCustomSortFieldsForClass($metadata->getReflectionClass()->getName());

            if ($sortableFields) {
                foreach ($sortableFields->getSortFields() as $sortName => $fieldName) {
                    $result[$sortName] = $fieldName;
                }
            } else {
                foreach ($iterator->fieldsIterate($alias, $aliasItem) as $sortName => $fieldName) {
                    $result[$sortName] = $fieldName;
                }
            }
        }

        return $result;
    }
}
