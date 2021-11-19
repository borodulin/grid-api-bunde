<?php

declare(strict_types=1);

namespace Borodulin\Bundle\GridApiBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;

class ApiExtension extends ConfigurableExtension implements PrependExtensionInterface
{
    /**
     * Configures the passed container according to the merged configuration.
     */
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../../config')
        );
        $loader->load('services.yml');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $yamlParser = new Parser();

        try {
            $doctrineConfig = $yamlParser->parse(
                file_get_contents(__DIR__ . '/../../config/doctrine.yml')
            );
        } catch (ParseException $e) {
            throw new \InvalidArgumentException(sprintf(
                'The file "%s" does not contain valid YAML.',
                __DIR__ . '/../../config/doctrine.yml'
            ), 0, $e);
        }

        $container->prependExtensionConfig('doctrine', $doctrineConfig['doctrine']);
    }
}
