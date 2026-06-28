<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Integration;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Compiler\ContainerCompiler;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\AuditService;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\MemoryLogger;
use CloudCastle\DI\Tests\Fixtures\ContextualBinding\ReportService;
use CloudCastle\DI\Tests\Support\CompiledContainerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerCompiler::class)]
#[CoversClass(AbstractCompiledContainer::class)]
final class CompiledContainerContextualBindingTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/cloudcastle_di_contextual_' . uniqid('', true) . '.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    public function testCompiledContainerMatchesRuntimeContextualWiring(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\ContextualBindingContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);

        $runtimeReport = $runtime->get(ReportService::class);
        $compiledReport = $compiled->get(ReportService::class);
        $runtimeAudit = $runtime->get(AuditService::class);
        $compiledAudit = $compiled->get(AuditService::class);

        self::assertInstanceOf(ReportService::class, $compiledReport);
        self::assertInstanceOf(AuditService::class, $compiledAudit);
        self::assertInstanceOf(ReportService::class, $runtimeReport);
        self::assertInstanceOf(AuditService::class, $runtimeAudit);
        self::assertInstanceOf(MemoryLogger::class, $compiledReport->logger);
        self::assertInstanceOf(FileLogger::class, $compiledAudit->logger);
        self::assertInstanceOf(MemoryLogger::class, $runtimeReport->logger);
        self::assertInstanceOf(FileLogger::class, $runtimeAudit->logger);
    }

    public function testCompiledContainerExposesContextualGiveMap(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\ContextualBindingMapContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);

        self::assertSame(
            'memory.logger',
            $compiled->contextualGive(ReportService::class, LoggerInterface::class),
        );
        self::assertNull($compiled->contextualGive(AuditService::class, LoggerInterface::class));
    }

    public function testGeneratedSourceUsesContextualServiceId(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\ContextualBindingSourceContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $source = file_get_contents($this->outputPath);
        self::assertIsString($source);
        self::assertStringContainsString('$this->get(\'memory.logger\')', $source);
        self::assertStringContainsString('contextual:', $source);
    }

    private function createRuntimeContainer(): Container
    {
        $container = new Container();
        $container->set('memory.logger', new MemoryLogger());
        $container->set('default.logger', new FileLogger());
        $container->bind(LoggerInterface::class, 'default.logger');
        $container->when(ReportService::class)
            ->needs(LoggerInterface::class)
            ->give('memory.logger');
        $container->autowire(ReportService::class);
        $container->autowire(AuditService::class);
        $container->freeze();

        return $container;
    }
}
