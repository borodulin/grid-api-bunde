<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi\Expand;

class Expand implements ExpandInterface
{
    private array $expand;

    public function __construct(
        array $expand
    ) {
        $this->expand = $expand;
    }

    public function getExpand(): array
    {
        return $this->expand;
    }
}
