<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DependencyInjection;

use Borodulin\Bundle\GridApiBundle\EntityConverter\EntityConverterInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;

class ApiExtension extends ConfigurableExtension
{
    /**
     * Configures the passed container according to the merged configuration.
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(EntityConverterInterface::class)
            ->addTag('grid_api.entity_converter');

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yml');
    }
}
