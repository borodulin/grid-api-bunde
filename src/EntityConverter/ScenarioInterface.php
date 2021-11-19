<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

interface ScenarioInterface
{
    public const SCENARIO_DEFAULT = 'default';

    public static function getScenario(): string;
}
