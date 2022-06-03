<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Expand;

interface ExpandInterface
{
    public function getExpand(): array;
}
