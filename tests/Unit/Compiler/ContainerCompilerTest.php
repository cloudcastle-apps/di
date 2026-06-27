<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\AbstractCompiledContainer;
use CloudCastle\DI\Compiler\CompileConstructorPlanner;
use CloudCastle\DI\Compiler\CompileParameterReferenceResolver;
use CloudCastle\DI\Compiler\CompileServiceBinding;
use CloudCastle\DI\Compiler\CompileServiceKind;
use CloudCastle\DI\Compiler\CompiledContainerPhpGenerator;
use CloudCastle\DI\Compiler\ContainerCompileSnapshot;
use CloudCastle\DI\Compiler\ContainerCompileSnapshotBuilder;
use CloudCastle\DI\Compiler\ContainerCompiler;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\LoggerUser;
use CloudCastle\DI\Tests\Support\CompiledContainerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerCompiler::class)]
#[CoversClass(ContainerCompileSnapshotBuilder::class)]
#[CoversClass(CompiledContainerPhpGenerator::class)]
#[CoversClass(CompileConstructorPlanner::class)]
#[CoversClass(CompileParameterReferenceResolver::class)]
#[CoversClass(ContainerCompileSnapshot::class)]
#[CoversClass(CompileServiceBinding::class)]
#[CoversClass(CompileServiceKind::class)]
final class ContainerCompilerTest extends TestCase
{
    private string $outputPath;

    protected function setUp(): void
    {
        $this->outputPath = sys_get_temp_dir() . '/cloudcastle_di_compile_' . uniqid('', true) . '.php';
    }

    protected function tearDown(): void
    {
        if (is_file($this->outputPath)) {
            unlink($this->outputPath);
        }
    }

    public function testCompileRequiresFrozenContainer(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('замороженного');

        (new ContainerCompiler())->compile($container, $this->outputPath);
    }

    public function testCompileRejectsCallableDefinitions(): void
    {
        $container = new Container();
        $container->set('clock', static fn (): Clock => new Clock());
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('фабрики');

        (new ContainerCompiler())->compile($container, $this->outputPath);
    }

    public function testCompileRejectsGlobalAutowiring(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('глобальный autowiring');

        (new ContainerCompiler())->compile($container, $this->outputPath);
    }

    public function testCompileRejectsAfterResolving(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->afterResolving(Clock::class, static function (): void {
        });
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('afterResolving');

        (new ContainerCompiler())->compile($container, $this->outputPath);
    }

    public function testCompileGeneratesLoadableClass(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->autowire(LoggerUser::class);
        $container->freeze();

        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\GeneratedTestContainer';
        $result = (new ContainerCompiler())->compile($container, $this->outputPath, $className);

        self::assertSame($className, $result->className);
        self::assertSame($this->outputPath, $result->outputPath);
        self::assertFileExists($this->outputPath);

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);
        $user = $compiled->get(LoggerUser::class);

        self::assertInstanceOf(LoggerUser::class, $user);
        self::assertSame($compiled->get(Clock::class), $user->clock);
    }
}
