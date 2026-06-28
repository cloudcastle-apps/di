<?php

declare(strict_types=1);

namespace CloudCastle\DI\Tests\Unit\Configuration;

use CloudCastle\DI\Configuration\ConfigurationApplicator;
use CloudCastle\DI\Container;
use CloudCastle\DI\Tests\Fixtures\Autowire\Clock;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomAttributePropertyService;
use CloudCastle\DI\Tests\Fixtures\Autowire\CustomServiceIdAttribute;
use CloudCastle\DI\Tests\Fixtures\Autowire\Scan\ScannedService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigurationApplicator::class)]
final class ConfigurationApplicatorMutationTest extends TestCase
{
    public function testAutowiringEnabledOnlyWhenFlagStrictlyTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['enabled' => 1],
        ]);

        self::assertFalse($container->isAutowiringEnabled());

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['enabled' => true],
        ]);

        self::assertTrue($container->isAutowiringEnabled());
    }

    public function testAutowiringEnablesOnlySpecifiedFlags(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['enabled' => true],
        ]);

        self::assertTrue($container->isAutowiringEnabled());
        self::assertFalse($container->isParameterNameAutowiringEnabled());
        self::assertFalse($container->isPropertyAutowiringEnabled());
        self::assertFalse($container->isMethodAutowiringEnabled());
    }

    public function testAutowiringInvalidSectionDoesNotEnableFlags(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => 'invalid',
        ]);

        self::assertFalse($container->isAutowiringEnabled());
        self::assertFalse($container->isParameterNameAutowiringEnabled());
    }

    public function testAutowiringNullSectionDoesNotEnableFlags(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => null,
        ]);

        self::assertFalse($container->isAutowiringEnabled());
    }

    public function testParameterNameAutowiringEnabledOnlyWhenStrictlyTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['parameter_name' => true],
        ]);

        self::assertTrue($container->isParameterNameAutowiringEnabled());
    }

    public function testPropertyAutowiringEnabledOnlyWhenStrictlyTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['property' => true],
        ]);

        self::assertTrue($container->isPropertyAutowiringEnabled());
    }

    public function testMethodAutowiringEnabledOnlyWhenStrictlyTrue(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'autowiring' => ['method' => true],
        ]);

        self::assertTrue($container->isMethodAutowiringEnabled());
    }

    public function testScanWithNonStringNamespacePassesNullToScanner(): void
    {
        $container = new Container();
        $scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire';

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                ['directory' => $scanDirectory, 'namespace' => 123],
            ],
        ]);

        self::assertTrue($container->hasDefinition(Clock::class));
    }

    public function testServiceArrayWithoutClassUsesSet(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'services' => [
                'config.array' => ['mode' => 'test'],
            ],
        ]);

        self::assertSame(['mode' => 'test'], $container->get('config.array'));
    }

    public function testServiceArrayWithNonStringClassUsesSet(): void
    {
        $container = new Container();

        (new ConfigurationApplicator())->apply($container, [
            'services' => [
                'broken' => ['class' => 123],
            ],
        ]);

        self::assertSame(['class' => 123], $container->get('broken'));
    }

    public function testRegisterAttributeSkipsNonStringValues(): void
    {
        $container = new Container();
        $container->enableAutowiring();
        $container->enablePropertyAutowiring();
        $container->set('app.clock', new Clock());

        (new ConfigurationApplicator())->apply($container, [
            'register_attributes' => [123, CustomServiceIdAttribute::class],
            'autowire' => [CustomAttributePropertyService::class],
        ]);

        $service = $container->get(CustomAttributePropertyService::class);
        self::assertInstanceOf(CustomAttributePropertyService::class, $service);
        self::assertSame($container->get('app.clock'), $service->getClock());
    }

    public function testApplySkipsNonArrayScanEntryButProcessesFollowingDirectory(): void
    {
        $container = new Container();
        $scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire/Scan';

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                'invalid-entry',
                [
                    'directory' => $scanDirectory,
                    'namespace' => 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
                ],
            ],
        ]);

        self::assertTrue($container->hasDefinition(ScannedService::class));
    }

    public function testApplySkipsScanWithoutDirectoryButProcessesFollowingEntry(): void
    {
        $container = new Container();
        $scanDirectory = \dirname(__DIR__, 2) . '/Fixtures/Autowire/Scan';

        (new ConfigurationApplicator())->apply($container, [
            'scan' => [
                ['namespace' => 'App\\Only'],
                [
                    'directory' => $scanDirectory,
                    'namespace' => 'CloudCastle\\DI\\Tests\\Fixtures\\Autowire\\Scan\\',
                ],
            ],
        ]);

        self::assertTrue($container->hasDefinition(ScannedService::class));
    }
}
