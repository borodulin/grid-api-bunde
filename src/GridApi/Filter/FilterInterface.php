<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

interface FilterInterface
{
    public function getFilters(): array;
}
