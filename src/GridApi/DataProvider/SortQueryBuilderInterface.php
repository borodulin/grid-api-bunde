<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

interface SortQueryBuilderInterface
{
    public function getSortMap(): array;

    public function addOrderBy(string $sort, string $order);

    public function resetOrder(): void;
}
