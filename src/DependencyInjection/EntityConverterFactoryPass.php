<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DependencyInjection;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterInterface;
use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

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
                    /* @var EntityConverterInterface|string $class */
                    if ($reflection->hasMethod('__invoke')) {
                        $method = $reflection->getMethod('__invoke');
                        if (0 === $method->getNumberOfRequiredParameters()) {
                            throw new \RuntimeException(sprintf('Invalid converter handler: method "%s::__invoke()" requires at least one argument, first one being the object it handles.', $reflection->getName()));
                        }
                        $parameters = $method->getParameters();
                        if (!$type = $parameters[0]->getType()) {
                            throw new \RuntimeException(sprintf('Invalid converter handler: argument "$%s" of method "%s::__invoke()" must have a type-hint corresponding to the object class it handles.', $parameters[0]->getName(), $reflection->getName()));
                        }
                        $converters[$type] = new Reference($class);
                    } else {
                        throw new \RuntimeException(sprintf('Invalid converter handler: method "%s::__invoke()" should be implemented. ', $reflection->getName()));
                    }
                }
            }
        }

        if ($converters) {
            $commandDefinition = $container->getDefinition(EntityConverterRegistry::class);
            $commandDefinition->setArgument('$converters', $converters);
        }
    }
}
