<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit;

use CloudCastle\DI\Container;
use CloudCastle\DI\ContainerSmartCacheSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(Container::class)]
#[CoversClass(ContainerSmartCacheSupport::class)]
final class ContainerSmartCacheTest extends TestCase
{
    public function testForgetForcesNewSingletonOnNextGet(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('svc', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $container->get('svc');
        $container->get('svc');
        $container->forget('svc');
        $container->get('svc');

        self::assertSame(2, $calls);
    }

    public function testSetInvalidatesSingletonCache(): void
    {
        $container = new Container();
        $container->set('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $cached = $container->get('svc'));
        $container->set('svc', static fn (): stdClass => new stdClass());
        self::assertInstanceOf(stdClass::class, $fresh = $container->get('svc'));

        self::assertNotSame($cached, $fresh);
    }

    public function testBindInvalidatesTargetSingletonCache(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('impl', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->get('impl');
        $container->bind('contract', 'impl');
        $container->get('contract');

        self::assertSame(2, $calls);
    }

    public function testCacheTagForAppliesToTaggedServices(): void
    {
        $container = new Container();
        $container->set('alpha', static fn (): stdClass => new stdClass());
        $container->set('beta', static fn (): stdClass => new stdClass());
        $container->tag('alpha', 'workers');
        $container->tag('beta', 'workers');
        $container->cacheTagFor('workers', ttlSeconds: 60);

        self::assertTrue($container->cacheStats('alpha')['configured']);
        self::assertTrue($container->cacheStats('beta')['configured']);
    }

    public function testForgetTagClearsTaggedSingletons(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('alpha', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->set('beta', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->tag('alpha', 'batch');
        $container->tag('beta', 'batch');

        $container->get('alpha');
        $container->get('beta');
        $container->forgetTag('batch');
        $container->get('alpha');
        $container->get('beta');

        self::assertSame(4, $calls);
    }

    public function testForgetAllClearsEverySingleton(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('first', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->set('second', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });

        $container->get('first');
        $container->get('second');
        $container->forgetAll();
        $container->get('first');
        $container->get('second');

        self::assertSame(4, $calls);
    }

    public function testMakeIgnoresSmartCache(): void
    {
        $container = new Container();
        $calls = 0;
        $container->set('proto', static function () use (&$calls): stdClass {
            ++$calls;

            return new stdClass();
        });
        $container->cacheFor('proto', ttlSeconds: 3600);

        $container->make('proto');
        $container->make('proto');

        self::assertSame(2, $calls);
    }
}
