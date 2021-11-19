<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Borodulin\Bundle\GridApiBundle\DependencyInjection\ApiExtension;
use Borodulin\Bundle\GridApiBundle\DependencyInjection\EntityConverterFactoryPass;

class GridApiBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityConverterFactoryPass());
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new ApiExtension();
    }
}
