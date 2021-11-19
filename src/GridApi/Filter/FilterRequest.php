<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

class FilterRequest implements FilterRequestInterface
{
    private array $filters;

    public function __construct(
        array $filters
    ) {
        $this->filters = $filters;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }
}
