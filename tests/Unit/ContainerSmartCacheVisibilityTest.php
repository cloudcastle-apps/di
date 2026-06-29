<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerSmartCacheApi;
use CloudCastle\DI\ContainerSmartCacheSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(ContainerSmartCacheApi::class)]
#[CoversClass(ContainerSmartCacheSupport::class)]
final class ContainerSmartCacheVisibilityTest extends TestCase
{
    public function testCacheForMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'cacheFor');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->cacheFor('svc', ttlSeconds: 30);
        self::assertTrue($container->cacheStats('svc')['configured']);
    }

    public function testCacheTagForMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'cacheTagFor');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->tag('svc', 'group');
        $container->cacheTagFor('group', ttlSeconds: 60);
        self::assertTrue($container->cacheStats('svc')['configured']);
    }

    public function testForgetMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'forget');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->get('svc');
        $container->forget('svc');
        self::assertFalse($container->cacheStats('svc')['cached']);
    }

    public function testForgetTagMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'forgetTag');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->tag('svc', 'batch');
        $container->get('svc');
        $container->forgetTag('batch');
        self::assertFalse($container->cacheStats('svc')['cached']);
    }

    public function testForgetAllMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'forgetAll');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->get('svc');
        $container->forgetAll();
        self::assertFalse($container->cacheStats('svc')['cached']);
    }

    public function testCacheStatsMethodIsPublic(): void
    {
        $method = new ReflectionMethod(Container::class, 'cacheStats');

        self::assertTrue($method->isPublic());

        $container = $this->createContainer();
        $container->cacheFor('svc', ttlSeconds: 45);

        self::assertSame(
            ['configured' => true, 'ttl_seconds' => 45, 'cached' => false, 'expires_at' => null, 'expired' => false],
            $container->cacheStats('svc'),
        );
    }

    private function createContainer(): Container
    {
        $container = new Container();
        $container->set('svc', static fn (): stdClass => new stdClass());

        return $container;
    }
}
