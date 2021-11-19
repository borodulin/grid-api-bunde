<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi\Expand;

interface ExpandRequestInterface
{
    public function getExpand(): array;
}
