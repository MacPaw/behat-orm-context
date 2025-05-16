<?php

declare(strict_types=1);

namespace BehatOrmContext\Tests\DependencyInjection;

use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use BehatOrmContext\Context\ORMContext;
use BehatOrmContext\DependencyInjection\BehatOrmContextExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BehatOrmContextExtensionTest extends TestCase
{
    public function testHasServices(): void
    {
        $extension = new BehatOrmContextExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        self::assertInstanceOf(Extension::class, $extension);

        self::assertTrue($container->has(OrmContext::class));
    }

    public function testOrmContextIsCorrectlyDefined(): void
    {
        $extension = new BehatOrmContextExtension();
        $container = new ContainerBuilder();
        $extension->load([], $container);

        $definition = $container->getDefinition(OrmContext::class);
        self::assertSame(OrmContext::class, $definition->getClass());
    }
}
