<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Compiler;

use CloudCastle\DI\Compiler\CompileConstructorPlanner;
use CloudCastle\DI\Compiler\CompiledContainerPhpGenerator;
use CloudCastle\DI\Compiler\CompileParameterReferenceResolver;
use CloudCastle\DI\Compiler\CompileServiceBinding;
use CloudCastle\DI\Compiler\CompileServiceKind;
use CloudCastle\DI\Compiler\ContainerCompiler;
use CloudCastle\DI\Compiler\ContainerCompileSnapshot;
use CloudCastle\DI\Compiler\ContainerCompileSnapshotBuilder;
use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
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

    public function testCompileGeneratesClassWithoutNamespaceSeparator(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->freeze();

        $className = 'RootLevelCompiledContainer';
        $result = (new ContainerCompiler())->compile($container, $this->outputPath, $className);

        self::assertSame($className, $result->className);
        self::assertStringContainsString('class RootLevelCompiledContainer extends', file_get_contents($this->outputPath) ?: '');

        $compiled = CompiledContainerLoader::load($this->outputPath, $className);

        self::assertInstanceOf(Clock::class, $compiled->get(Clock::class));
    }

    public function testCompileRejectsForeignContainerImplementation(): void
    {
        $container = $this->createMock(ContainerInterface::class);

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage(\CloudCastle\DI\Container::class);

        (new ContainerCompiler())->compile($container, $this->outputPath);
    }

    public function testCompileDerivesClassNameFromPath(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->freeze();

        $result = (new ContainerCompiler())->compile($container, $this->outputPath);

        self::assertSame(
            'CloudCastle\\DI\\Compiled\\' . basename($this->outputPath, '.php'),
            $result->className,
        );
    }

    public function testCompileRejectsPathWithoutPhpExtension(): void
    {
        $container = new Container();
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('.php');

        (new ContainerCompiler())->compile($container, '/tmp/compiled-container');
    }

    public function testCompileRejectsEmptyFileName(): void
    {
        $container = new Container();
        $container->freeze();

        $this->expectException(ContainerCompileException::class);
        $this->expectExceptionMessage('имя файла');

        (new ContainerCompiler())->compile($container, '/tmp/.php');
    }

    public function testCompileCreatesOutputDirectory(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->freeze();

        $directory = sys_get_temp_dir() . '/cloudcastle_di_nested_' . uniqid('', true);
        $outputPath = $directory . '/nested/CompiledContainer.php';
        $className = 'CloudCastle\\DI\\Tests\\Fixtures\\Compiled\\NestedGeneratedContainer';

        try {
            (new ContainerCompiler())->compile($container, $outputPath, $className);

            self::assertDirectoryExists($directory . '/nested');
            self::assertFileExists($outputPath);
        } finally {
            if (is_file($outputPath)) {
                unlink($outputPath);
            }

            if (is_dir($directory . '/nested')) {
                rmdir($directory . '/nested');
            }

            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }

    public function testCompileFailsWhenOutputDirectoryCannotBeCreated(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->freeze();

        $parentFile = tempnam(sys_get_temp_dir(), 'cloudcastle_di_file_');
        self::assertNotFalse($parentFile);

        $outputPath = $parentFile . '/CompiledContainer.php';

        try {
            $this->expectException(ContainerCompileException::class);
            $this->expectExceptionMessage('создать каталог');

            (new ContainerCompiler())->compile($container, $outputPath);
        } finally {
            if (is_file($parentFile)) {
                unlink($parentFile);
            }
        }
    }

    public function testCompileFailsWhenOutputPathIsDirectory(): void
    {
        $container = new Container();
        $container->set(Clock::class, new Clock());
        $container->freeze();

        $directory = sys_get_temp_dir() . '/cloudcastle_di_output_dir_' . uniqid('', true) . '.php';
        mkdir($directory);

        try {
            $this->expectException(ContainerCompileException::class);
            $this->expectExceptionMessage('записать compiled-контейнер');

            (new ContainerCompiler())->compile($container, $directory);
        } finally {
            if (is_dir($directory)) {
                rmdir($directory);
            }
        }
    }
}
