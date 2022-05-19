<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;

interface EntityApiInterface
{
    public function setContext(array $context): self;

    public function setExpand(?ExpandInterface $expand): self;

    /**
     * @return mixed
     */
    public function show(object $entity);
}
