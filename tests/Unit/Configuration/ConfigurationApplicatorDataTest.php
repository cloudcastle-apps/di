<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
final class ConfigurationApplicatorDataTest extends TestCase
{
    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('autowiringFlagProvider')]
    public function testAutowiringFlagsAreAppliedIndependently(array $config, string $checker, bool $expected): void
    {
        $container = new Container();
        (new ConfigurationApplicator())->apply($container, $config);

        $actual = match ($checker) {
            'isAutowiringEnabled' => $container->isAutowiringEnabled(),
            'isParameterNameAutowiringEnabled' => $container->isParameterNameAutowiringEnabled(),
            'isPropertyAutowiringEnabled' => $container->isPropertyAutowiringEnabled(),
            'isMethodAutowiringEnabled' => $container->isMethodAutowiringEnabled(),
            default => throw new InvalidArgumentException('Unknown checker: ' . $checker),
        };

        self::assertSame($expected, $actual);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string, bool}>
     */
    public static function autowiringFlagProvider(): iterable
    {
        yield 'enabled' => [
            ['autowiring' => ['enabled' => true]],
            'isAutowiringEnabled',
            true,
        ];

        yield 'parameter' => [
            ['autowiring' => ['parameter_name' => true]],
            'isParameterNameAutowiringEnabled',
            true,
        ];

        yield 'property' => [
            ['autowiring' => ['property' => true]],
            'isPropertyAutowiringEnabled',
            true,
        ];

        yield 'method' => [
            ['autowiring' => ['method' => true]],
            'isMethodAutowiringEnabled',
            true,
        ];
    }

    public function testRegisterAttributeCallIsRequiredForCustomAttribute(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->enablePropertyAutowiring();

        $expectedClock = new Clock();
        $container->set('app.clock', $expectedClock);

        (new ConfigurationApplicator())->apply($container, [
            'register_attributes' => [CustomServiceIdAttribute::class],
            'autowire' => [CustomAttributePropertyService::class],
        ]);

        $service = $container->get(CustomAttributePropertyService::class);
        self::assertInstanceOf(CustomAttributePropertyService::class, $service);
        self::assertSame($expectedClock, $service->getClock());
    }
}
