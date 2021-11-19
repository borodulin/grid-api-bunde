<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

interface CustomFilterInterface
{
    public function getFilterFields(): array;
}
