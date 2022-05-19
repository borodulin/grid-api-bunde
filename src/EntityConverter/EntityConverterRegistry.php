<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\EntityConverter;

use Doctrine\Persistence\Proxy;

class EntityConverterRegistry
{
    private array $converters = [];

    /**
     * @param EntityConverterInterface[] $converters
     */
    public function __construct(
        array $converters = []
    ) {
        foreach ($converters as $class => $converter) {
            $this->converters[$class] = $converter;
        }
    }

    public function getConverterForClass(string $class): ?EntityConverterInterface
    {
        if (is_subclass_of($class, Proxy::class)) {
            $class = get_parent_class($class);
        }
        return $this->converters[$class] ?? null;
    }

    public function getCustomExpandFieldsForClass(string $class): ?CustomExpandInterface
    {
        $converter = $this->getConverterForClass($class);

        return $converter instanceof CustomExpandInterface ? $converter : null;
    }
}
