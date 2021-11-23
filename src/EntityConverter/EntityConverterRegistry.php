<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

use Borodulin\Bundle\GridApiBundle\GridApi\Filter\CustomFilterInterface;
use Borodulin\Bundle\GridApiBundle\GridApi\Sort\CustomSortInterface;
use Doctrine\Persistence\Proxy;

class EntityConverterRegistry
{
    private array $converters = [];
    private ScenarioInterface $defaultScenario;

    /**
     * @param EntityConverterInterface[]|CustomScenarioInterface[] $diConverters
     */
    public function __construct(
        ScenarioInterface $defaultScenario,
        array $diConverters = []
    ) {
        $this->defaultScenario = $defaultScenario;
        foreach ($diConverters as $class => $converter) {
            $scenario = is_subclass_of($converter, CustomScenarioInterface::class) ?
                $converter->getScenario() : $this->defaultScenario;
            $this->converters[$scenario->getName()][$class] = $converter;
        }
    }

    public function getConverterForClass(string $class, ?ScenarioInterface $scenario = null): ?EntityConverterInterface
    {
        if (is_subclass_of($class, Proxy::class)) {
            $class = get_parent_class($class);
        }
        return null === $scenario
            ? $this->converters[$this->defaultScenario->getName()][$class] ?? null
            : $this->converters[$scenario->getName()][$class]
                ?? $this->converters[$this->defaultScenario->getName()][$class]
                ?? null;
    }

    public function getCustomSortFieldsForClass(string $class, ?ScenarioInterface $scenario = null): ?CustomSortInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomSortInterface ? $converter : null;
    }

    public function getCustomFilterFieldsForClass(string $class, ?ScenarioInterface $scenario = null): ?CustomFilterInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomFilterInterface ? $converter : null;
    }

    public function getCustomExpandFieldsForClass(string $class, ?ScenarioInterface $scenario = null): ?CustomExpandInterface
    {
        $converter = $this->getConverterForClass($class, $scenario);

        return $converter && $converter instanceof CustomExpandInterface ? $converter : null;
    }
}
