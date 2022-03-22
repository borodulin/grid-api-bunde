<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Expand;

interface ExpandInterface
{
    public function getExpand(): array;
}
