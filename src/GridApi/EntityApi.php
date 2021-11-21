<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\EntityRecursiveExpander;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestInterface;

class EntityApi implements EntityApiInterface
{
    private ?ScenarioInterface $scenario;
    private ?ExpandRequestInterface $expandRequest = null;
    private EntityRecursiveExpander $entityExpand;

    public function __construct(
        EntityRecursiveExpander $entityExpand,
        ScenarioInterface $scenario
    ) {
        $this->entityExpand = $entityExpand;
        $this->scenario = $scenario;
    }

    public function setScenario(ScenarioInterface $scenario): EntityApiInterface
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
