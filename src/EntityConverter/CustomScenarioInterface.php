<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

interface CustomScenarioInterface
{
    public function getScenario(): ScenarioInterface;
}
