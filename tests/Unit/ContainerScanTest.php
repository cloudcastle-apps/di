<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\ClassScanner;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\AbstractWorker;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedService;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use Override;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Интеграция {@see Container::scan()} с autowiring.
 */
#[CoversClass(Container::class)]
#[CoversClass(ClassScanner::class)]
final class ContainerScanTest extends TestCase
{
    private string $fixturesDirectory;

    #[Override]
    protected function setUp(): void
    {
        $this->fixturesDirectory = \dirname(__DIR__) . '/Fixtures/Autowire';
    }

    public function testScanRegistersInstantiableClasses(): void
    {
        $container = new Container();
        $container->scan($this->fixturesDirectory);

        self::assertTrue($container->hasDefinition(SimpleService::class));
        self::assertTrue($container->hasDefinition(Clock::class));
        self::assertFalse($container->hasDefinition(AbstractWorker::class));
        self::assertInstanceOf(SimpleService::class, $container->get(SimpleService::class));
    }

    public function testScanWithNamespaceFilter(): void
    {
        $container = new Container();
        $scanDirectory = $this->fixturesDirectory . '/Scan';
        $container->scan($scanDirectory, 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan');

        self::assertTrue($container->hasDefinition(ScannedService::class));
        self::assertFalse($container->hasDefinition(Clock::class));
    }

    public function testScanDoesNotOverrideExistingDefinitions(): void
    {
        $container = new Container();
        $replacement = new SimpleService();
        $container->set(SimpleService::class, $replacement);
        $container->scan($this->fixturesDirectory);

        self::assertSame($replacement, $container->get(SimpleService::class));
    }

    public function testScanThrowsForMissingDirectory(): void
    {
        $container = new Container();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('не найден');

        $container->scan('/tmp/cloudcastle-di-missing-directory');
    }
}
