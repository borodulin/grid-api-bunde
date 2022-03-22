<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Sort;

interface SortInterface
{
    public function getSortOrders(): array;
}
