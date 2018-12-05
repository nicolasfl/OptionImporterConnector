<?php

namespace Extensions\Bundle\OptionImporterConnectorBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class ExtensionsOptionImporterConnectorExtension
 *
 * @author                 Nicolas SOUFFLEUR, Akeneo Expert <contact@nicolas-souffleur.com>
 * @license                http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class ExtensionsOptionImporterConnectorExtension extends Extension
{

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('jobs.yml');
        $loader->load('job_parameters.yml');
        $loader->load('steps.yml');
        $loader->load('readers.yml');
        $loader->load('providers.yml');
        $loader->load('array_converters.yml');
    }
}