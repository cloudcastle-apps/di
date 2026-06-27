<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Integration;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Compiler\CompileConstructorPlanner;
use CloudCastle\DI\Compiler\CompiledContainerPhpGenerator;
use CloudCastle\DI\Compiler\CompileParameterReferenceResolver;
use CloudCastle\DI\Compiler\CompileServiceBinding;
use CloudCastle\DI\Compiler\CompileServiceKind;
use CloudCastle\DI\Compiler\ContainerCompiler;
use CloudCastle\DI\Compiler\ContainerCompileSnapshot;
use CloudCastle\DI\Compiler\ContainerCompileSnapshotBuilder;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\NotFoundException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\FileLogger;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerInterface;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Fixtures\Autowire\SimpleService;
use CloudCastle\DI\Tests\Support\CompiledContainerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Проверяет parity runtime-контейнера и compiled-контейнера (#24).
 */
#[CoversClass(ContainerCompiler::class)]
#[CoversClass(AbstractCompiledContainer::class)]
#[CoversClass(ContainerCompileSnapshotBuilder::class)]
#[CoversClass(CompiledContainerPhpGenerator::class)]
#[CoversClass(CompileConstructorPlanner::class)]
#[CoversClass(CompileParameterReferenceResolver::class)]
#[CoversClass(ContainerCompileSnapshot::class)]
#[CoversClass(CompileServiceBinding::class)]
#[CoversClass(CompileServiceKind::class)]
final class CompiledContainerIntegrationTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/cloudcastle_di_integration_' . uniqid('', true) . '.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    public function testCompiledContainerMatchesRuntimeContainer(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\IntegrationContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);

        self::assertSame($className, $compiled->getCompiledClassName());
        self::assertTrue($compiled->isFrozen());
        self::assertTrue($compiled->has(Clock::class));
        self::assertTrue($compiled->has(LoggerInterface::class));
        self::assertTrue($compiled->has(LoggerUser::class));

        $runtimeUser = $runtime->get(LoggerUser::class);
        $compiledUser = $compiled->get(LoggerUser::class);

        self::assertInstanceOf(LoggerUser::class, $compiledUser);
        self::assertInstanceOf(LoggerUser::class, $runtimeUser);
        self::assertEquals($runtimeUser->clock, $compiledUser->clock);

        self::assertSame(
            $runtime->getTaggedIds('loggers'),
            $compiled->getTaggedIds('loggers'),
        );

        self::assertInstanceOf(
            FileLogger::class,
            $compiled->getTagged('loggers')[FileLogger::class],
        );
    }

    public function testGeneratedSourceContainsExpectedWiring(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\IntegrationContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $source = file_get_contents($this->outputPath);
        self::assertIsString($source);
        self::assertStringContainsString('compiledClassName: ' . var_export($className, true), $source);
        self::assertStringContainsString(
            '$this->get(' . var_export(Clock::class, true) . ')',
            $source,
        );
        self::assertStringContainsString('new \\' . SimpleService::class . '()', $source);
    }

    public function testCompiledContainerThrowsNotFoundForMissingService(): void
    {
        $runtime = $this->createRuntimeContainer();
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\IntegrationNotFoundContainer';

        (new ContainerCompiler())->compile($runtime, $this->outputPath, $className);

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);

        $this->expectException(NotFoundException::class);

        $compiled->get('missing.service');
    }

    private function createRuntimeContainer(): Container
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->autowire(SimpleService::class);
        $container->autowire(FileLogger::class);
        $container->autowire(LoggerUser::class);
        $container->alias(LoggerInterface::class, FileLogger::class);
        $container->tag(FileLogger::class, 'loggers');
        $container->freeze();

        return $container;
    }
}
