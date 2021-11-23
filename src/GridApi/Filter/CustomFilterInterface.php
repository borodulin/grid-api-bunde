<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

interface CustomFilterInterface
{
    /**
     * ['filterName' => function (QueryBuilder $queryBuilder, string $filterValue)].
     *
     * @example ['customer' => function (QueryBuilder $queryBuilder, string $filterValue) {
     *      $queryBuilder
     *        ->andWhere('alias.Customer = :customer')
     *        ->setParameter('customer', $filterValue);
     * }]
     *
     * @return callable[]
     */
    public function getFilterFields(): array;
}
