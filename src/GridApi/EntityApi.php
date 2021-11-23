<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandRequestInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityApi implements EntityApiInterface
{
    private ?ScenarioInterface $scenario;
    private ?ExpandRequestInterface $expandRequest = null;
    private NormalizerInterface $normalizer;

    public function __construct(
        NormalizerInterface $normalizer,
        ScenarioInterface $scenario
    ) {
        $this->scenario = $scenario;
        $this->normalizer = $normalizer;
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

        return $this->normalizer->normalize($entity, null, [
            'expand' => $expand,
            'scenario' => $this->scenario,
        ]);
    }
}
