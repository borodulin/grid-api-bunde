<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

use Doctrine\Persistence\Proxy;

class EntityConverterRegistry
{
    private array $converters = [];

    /**
     * @param EntityConverterInterface[]|ScenarioInterface[] $diConverters
     */
    public function __construct(
        array $diConverters = []
    ) {
        foreach ($diConverters as $class => $converter) {
            $scenario = is_subclass_of($converter, ScenarioInterface::class) ?
                $converter::getScenario() : ScenarioInterface::SCENARIO_DEFAULT;
            $this->converters[$scenario][$class] = $converter;
        }
    }

    public function getConverterForClass(string $class, ?string $scenario = null): ?EntityConverterInterface
    {
        if (is_subclass_of($class, Proxy::class)) {
            $class = get_parent_class($class);
        }

        return null === $scenario ? $this->converters[ScenarioInterface::SCENARIO_DEFAULT][$class] :
            $this->converters[$scenario][$class] ?? $this->converters[ScenarioInterface::SCENARIO_DEFAULT][$class] ?? null;
    }

    public function getCustomSortFieldsForClass(string $class, ?string $scenario = null): ?CustomSortInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomSortInterface ? $converter : null;
    }

    public function getCustomFilterFieldsForClass(string $class, ?string $scenario = null): ?CustomFilterInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomFilterInterface ? $converter : null;
    }

    public function getCustomExpandFieldsForClass(string $class, ?string $scenario = null): ?CustomExpandInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomExpandInterface ? $converter : null;
    }
}
