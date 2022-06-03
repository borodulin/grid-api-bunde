<?php

declare(strict_types=1);

namespace Borodulin\GridApiBundle\GridApi;

use Borodulin\GridApiBundle\GridApi\Expand\ExpandInterface;

interface EntityApiInterface
{
    public function setContext(array $context): self;

    public function setExpand(?ExpandInterface $expand): self;

    /**
     * @return mixed
     */
    public function show(object $entity);
}
