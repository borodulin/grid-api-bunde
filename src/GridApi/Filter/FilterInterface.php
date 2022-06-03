<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Filter;

interface FilterInterface
{
    public function getFilters(): array;
}
