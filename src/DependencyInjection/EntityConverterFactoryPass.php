<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DependencyInjection;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class EntityConverterFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $converters = [];
        foreach ($container->findTaggedServiceIds('grid_api.entity_converter', true) as $serviceId => $tags) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass();
            $reflection = new \ReflectionClass($class);
            try {
                $method = $reflection->getMethod('__invoke');
            } catch (\ReflectionException $e) {
                throw new \RuntimeException(sprintf('Invalid converter handler: class "%s" must have an "__invoke()" method.', $reflection->getName()));
            }
            if (0 === $method->getNumberOfRequiredParameters()) {
                throw new \RuntimeException(sprintf('Invalid converter handler: method "%s::__invoke()" requires at least one argument, first one being the object it handles.', $reflection->getName()));
            }
            $parameters = $method->getParameters();
            if (!$type = $parameters[0]->getType()) {
                throw new \RuntimeException(sprintf('Invalid converter handler: argument "$%s" of method "%s::__invoke()" must have a type-hint corresponding to the object class it handles.', $parameters[0]->getName(), $reflection->getName()));
            }

            if ($type->isBuiltin()) {
                throw new \RuntimeException(sprintf('Invalid converter handler: type-hint of argument "$%s" in method "%s::__invoke()" must be a class , "%s" given.', $parameters[0]->getName(), $reflection->getName(), (string) $type));
            }

            $converters[(string) $type] = new Reference($reflection->getName());
        }

        if ($converters) {
            $commandDefinition = $container->getDefinition(EntityConverterRegistry::class);
            $commandDefinition->setArgument('$converters', $converters);
        }
    }
}
