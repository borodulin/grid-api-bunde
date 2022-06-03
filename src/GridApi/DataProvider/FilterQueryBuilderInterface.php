<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\DataProvider;

interface FilterQueryBuilderInterface
{
    public function getFilterMap(): array;

    public function addFilter(string $fieldName, ?string $fieldType, $filterValue): void;
}
