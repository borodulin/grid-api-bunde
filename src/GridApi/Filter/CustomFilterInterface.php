<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Filter;

interface CustomFilterInterface
{
    public function getFilterFields(): array;
}