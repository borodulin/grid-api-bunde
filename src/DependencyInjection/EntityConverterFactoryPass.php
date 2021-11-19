<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterInterface;

class EntityConverterFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $converters = [];
        foreach ($container->getDefinitions() as $definition) {
            if ($definition->isAbstract()) {
                continue;
            }
            $class = $definition->getClass();
            try {
                $classExist = $class && class_exists($class);
            } catch (\Throwable $exception) {
                $classExist = false;
            }
            if ($classExist) {
                $reflection = new \ReflectionClass($class);
                if ($reflection->implementsInterface(EntityConverterInterface::class)) {
                    /** @var EntityConverterInterface|string $class */
                    // TODO
                    $entityClass = $class::entityClass();
                    $converters[$entityClass] = new Reference($class);
                }
            }
        }

        if ($converters) {
            $commandDefinition = $container->getDefinition(EntityConverterRegistry::class);
            $commandDefinition->setArgument('$converters', $converters);
        }
    }
}
