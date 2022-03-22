<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\GridApi;

use Borodulin\Bundle\GridApiBundle\EntityConverter\ScenarioInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Expand\ExpandInterface;

interface EntityApiInterface
{
    public function setScenario(ScenarioInterface $scenario): self;

    public function setExpand(?ExpandInterface $expand): self;

    /**
     * @return mixed
     */
    public function show(object $entity);
}
