<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

use Borodulin\Bundle\GridApiBundle\DoctrineInteraction\QueryBuilderEntityIterator;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\Serializer\LowerCaseNameConverter;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Serializer\NameConverter\NameConverterInterface;

class Sorter
{
    private NameConverterInterface $nameConverter;

    public function __construct(
    ) {
        $this->nameConverter = new LowerCaseNameConverter();
    }

    public function sort(
        SortRequest $sortRequest,
        QueryBuilder $queryBuilder,
        ?CustomSortInterface $customSort
    ): void {
        $sortMap = $this->getSortMap($queryBuilder, $customSort);

        $orderByArray = [];

        foreach ($sortRequest->getSortOrders() as $name => $sortOrder) {
            $name = $this->nameConverter->normalize($name);
            if (isset($sortMap[$name])) {
                $orderByArray[$sortMap[$name]] = $sortOrder;
            }
        }
        if (\count($orderByArray)) {
            $queryBuilder->resetDQLPart('orderBy');
            foreach ($orderByArray as $sort => $order) {
                $queryBuilder->addOrderBy($sort, $order);
            }
        }
    }

    private function getSortMap(QueryBuilder $queryBuilder, ?CustomSortInterface $customSort): array
    {
        $result = [];

        $iterator = new QueryBuilderEntityIterator($this->nameConverter);

        foreach ($iterator->aliasIterate($queryBuilder) as $alias => $aliasItem) {
            if (null !== $customSort) {
                foreach ($customSort->getSortFields() as $sortName => $fieldName) {
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
