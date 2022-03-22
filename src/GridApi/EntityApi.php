<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class EntityApi implements EntityApiInterface
{
    private ScenarioInterface $scenario;
    private ?ExpandInterface $expand = null;
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

    public function setExpand(?ExpandInterface $expand): EntityApiInterface
    {
        $this->expand = $expand;

        return $this;
    }

    public function show(object $entity)
    {
        $expand = $this->expand ? $this->expand->getExpand() : [];

        return $this->normalizer->normalize($entity, null, [
            'expand' => $expand,
            'scenario' => $this->scenario,
        ]);
    }
}
