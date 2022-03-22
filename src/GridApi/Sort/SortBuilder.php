<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\SortQueryBuilderInterface;

class SortBuilder
{
    public function sort(
        SortInterface $sortRequest,
        SortQueryBuilderInterface $sortQueryBuilder,
        ?CustomSortInterface $customSort
    ): void {
        $sortMap = (null !== $customSort) ? $customSort->getSortFields() : $sortQueryBuilder->getSortMap();

        $orderByArray = [];

        foreach ($sortRequest->getSortOrders() as $name => $sortOrder) {
            if (isset($sortMap[$name])) {
                $orderByArray[$sortMap[$name]] = $sortOrder;
            }
        }
        if (\count($orderByArray)) {
            $sortQueryBuilder->resetOrder();
            foreach ($orderByArray as $sort => $order) {
                $sortQueryBuilder->addOrderBy($sort, $order);
            }
        }
    }
}
