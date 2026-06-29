<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerProfilingApi;
use CloudCastle\DI\ContainerProfilingSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(ContainerProfilingApi::class)]
#[CoversClass(ContainerProfilingSupport::class)]
final class ContainerProfilingVisibilityTest extends TestCase
{
    public function testEnableProfilingMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'enableProfiling');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->enableProfiling();
        self::assertTrue($container->isProfilingEnabled());
    }

    public function testDisableProfilingMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'disableProfiling');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->enableProfiling();
        $container->disableProfiling();
        self::assertFalse($container->isProfilingEnabled());
    }

    public function testIsProfilingEnabledMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'isProfilingEnabled');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        self::assertFalse($container->isProfilingEnabled());
        $container->enableProfiling();
        self::assertTrue($container->isProfilingEnabled());
    }

    public function testResetProfileMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'resetProfile');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->enableProfiling();
        $container->get('svc');
        $container->resetProfile();
        self::assertSame(0, $container->profileReport()['sample_count']);
    }

    public function testProfileReportMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'profileReport');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->enableProfiling();
        $container->get('svc');

        self::assertSame(1, $container->profileReport()['sample_count']);
    }

    private function createContainer(): Container
    {
        $container = new Container();
        $container->set('svc', static fn (): stdClass => new stdClass());

        return $container;
    }
}
