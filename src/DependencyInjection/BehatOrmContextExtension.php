<?php

declare(strict_types=1);

namespace BehatOrmContext\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class BehatOrmContextExtension extends Extension
{
    /**
     * @param array<array<mixed>> $configs
     *
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $locator = new FileLocator(__DIR__ . '/../Resources/config');
        if (\class_exists(XmlFileLoader::class)) {
            (new XmlFileLoader($container, $locator))->load('orm_context.xml');

            return;
        }

        (new YamlFileLoader($container, $locator))->load('orm_context.yaml');
    }
}
