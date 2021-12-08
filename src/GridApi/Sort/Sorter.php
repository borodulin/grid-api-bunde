<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\CustomSortInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\DataProvider\SortQueryBuilderInterface;

class Sorter
{
    public function sort(
        SortRequest $sortRequest,
        SortQueryBuilderInterface $sortQueryBuilder,
        ?CustomSortInterface $customSort
    ): void {
        $sortMap = $sortQueryBuilder->getSortMap($customSort);

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
