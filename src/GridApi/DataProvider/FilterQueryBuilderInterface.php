<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\DataProvider;

interface FilterQueryBuilderInterface
{
    public function getFilterMap(?CustomFilterInterface $customFilter): array;

    public function addFilter(string $fieldName, ?string $fieldType, $filterValue): void;
}
