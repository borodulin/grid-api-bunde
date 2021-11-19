<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\GridApi\Expand\EntityExpand;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestInterface;

class EntityApi implements EntityApiInterface
{
    private ?string $scenario = null;
    private ?ExpandRequestInterface $expandRequest = null;
    private EntityExpand $entityExpand;

    public function __construct(
        EntityExpand $entityExpand
    ) {
        $this->entityExpand = $entityExpand;
    }

    public function setScenario(?string $scenario): EntityApiInterface
    {
        $this->scenario = $scenario;

        return $this;
    }

    public function setExpandRequest(?ExpandRequestInterface $expandRequest): EntityApiInterface
    {
        $this->expandRequest = $expandRequest;

        return $this;
    }

    public function show(object $entity)
    {
        $expand = $this->expandRequest ? $this->expandRequest->getExpand() : [];

        return $this->entityExpand->expand($entity, $expand, $this->scenario);
    }
}
