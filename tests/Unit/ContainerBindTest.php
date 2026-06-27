<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
final class ContainerBindTest extends TestCase
{
    public function testBindAutowiresClassAndAliasesAbstract(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->bind(LoggerInterface::class, FileLogger::class);

        self::assertInstanceOf(FileLogger::class, $container->get(LoggerInterface::class));
        self::assertTrue($container->hasDefinition(FileLogger::class));
    }

    public function testBindAliasesExistingServiceId(): void
    {
        $service = new stdClass();
        $container = new Container();
        $container->set('app.service', $service);
        $container->bind('alias', 'app.service');

        self::assertSame($service, $container->get('alias'));
    }

    public function testBindThrowsWhenConcreteClassIsNotInstantiable(): void
    {
        $container = new Container();

        $this->expectException(\CloudCastle\DI\Exception\ContainerException::class);

        $container->bind('worker', AbstractWorker::class);
    }

    public function testBindThrowsWhenConcreteIsUnknown(): void
    {
        $container = new Container();

        $this->expectException(\CloudCastle\DI\Exception\ContainerException::class);
        $this->expectExceptionMessage('Нельзя привязать');

        $container->bind('abstract', 'missing.service');
    }
}
