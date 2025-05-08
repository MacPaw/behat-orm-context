<?php

declare(strict_types=1);

namespace BehatOrmContext\DependencyInjection;

use BehatOrmContext\Context\ORMContext;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class BehatOrmContextExtension extends Extension
{
    /**
     * @param array<array> $configs
     *
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('orm_context.xml');

        if (!empty($config['kernel_reset_managers'])) {
            $contextDefinition = $container->findDefinition(ORMContext::class);

            foreach ($config['kernel_reset_managers'] as $resetManager) {
                $contextDefinition->addMethodCall('addKernelResetManager', [
                    $container->findDefinition($resetManager)
                ]);
            }
        }
    }
}
