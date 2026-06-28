<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerException;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\AuditService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
final class ContainerContextualBindingTest extends TestCase
{
    public function testWhenNeedsGiveOverridesDependencyForConsumerOnly(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->set('default.logger', new FileLogger());
        $container->bind(LoggerInterface::class, 'default.logger');
        $container->set('memory.logger', new MemoryLogger());
        $container->when(ReportService::class)
            ->needs(LoggerInterface::class)
            ->give('memory.logger');

        $report = $container->get(ReportService::class);
        $audit = $container->get(AuditService::class);

        self::assertInstanceOf(ReportService::class, $report);
        self::assertInstanceOf(AuditService::class, $audit);
        self::assertInstanceOf(MemoryLogger::class, $report->logger);
        self::assertInstanceOf(FileLogger::class, $audit->logger);
    }

    public function testContextualGiveReturnsRegisteredServiceId(): void
    {
        $container = new Container();
        $container->when(ReportService::class)
            ->needs(LoggerInterface::class)
            ->give('memory.logger');

        self::assertSame(
            'memory.logger',
            $container->contextualGive(ReportService::class, LoggerInterface::class),
        );
        self::assertNull($container->contextualGive(AuditService::class, LoggerInterface::class));
    }

    public function testWhenFailsAfterFreeze(): void
    {
        $container = new Container();
        $container->freeze();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('заморожен');

        $container->when(ReportService::class);
    }

    public function testGiveFailsAfterFreeze(): void
    {
        $container = new Container();
        $needs = $container->when(ReportService::class);
        $container->freeze();

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('заморожен');

        $needs->needs(LoggerInterface::class)->give('memory.logger');
    }
}
