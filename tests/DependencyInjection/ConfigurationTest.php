<?php

declare(strict_types=1);

namespace BehatOrmContext\Tests\DependencyInjection;

use BehatOrmContext\DependencyInjection\Configuration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Processor;

final class ConfigurationTest extends TestCase
{
    public function testConfiguration(): void
    {
        $processor = new Processor();
        $configuration = new Configuration();

        self::assertInstanceOf(ConfigurationInterface::class, $configuration);

        $configs = $processor->processConfiguration($configuration, []);

        self::assertSame([], $configs);
    }
}
