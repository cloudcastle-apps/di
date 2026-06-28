<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;
use Symfony\Component\VarExporter\Exception\LogicException;
use Symfony\Component\VarExporter\LazyObjectInterface;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Создаёт lazy ghost/proxy через symfony/var-exporter (#34).
 *
 * Требует opt-in зависимость {@see ProxyHelper} — runtime deps контейнера не меняются.
 */
final class LazyGhostProxyFactory
{
    /** @var array<string, class-string> */
    private static array $proxyClasses = [];

    /**
     * Проверяет, доступен ли symfony/var-exporter для {@see ContainerInterface::lazyGhost()}.
     */
    public static function isAvailable(): bool
    {
        return class_exists(ProxyHelper::class);
    }

    /**
     * Создаёт lazy proxy для интерфейса; реализация загружается при первом вызове метода.
     *
     * @param class-string $type Интерфейс, который должен реализовать proxy
     *
     * @throws ContainerException Если пакет не установлен, тип не интерфейс или proxy несовместим
     */
    public static function create(ContainerInterface $container, string $type, string $serviceId): object
    {
        if (!self::isAvailable()) {
            throw new ContainerException('lazyGhost() требует symfony/var-exporter.'); // @codeCoverageIgnore
        }

        $reflection = new ReflectionClass($type);

        if (!$reflection->isInterface()) {
            throw new ContainerException(\sprintf(
                'lazyGhost() принимает только interface class-string, получено "%s".',
                $type,
            ));
        }

        $proxyClass = self::resolveProxyClass($reflection);

        /** @psalm-suppress MixedMethodCall — class-string из eval(ProxyHelper::generateLazyProxy()) */
        $proxy = $proxyClass::createLazyProxy(
            initializer: static function () use ($container, $serviceId): object {
                $instance = $container->get($serviceId);

                if (!\is_object($instance)) {
                    throw new ContainerException(\sprintf(
                        'lazyGhost(): сервис "%s" должен быть объектом.',
                        $serviceId,
                    ));
                }

                return $instance;
            },
        );

        \assert(\is_object($proxy));

        return $proxy;
    }

    /**
     * @param ReflectionClass<object> $interface
     *
     * @return class-string
     */
    private static function resolveProxyClass(ReflectionClass $interface): string
    {
        $interfaceName = $interface->name;

        if (isset(self::$proxyClasses[$interfaceName])) {
            return self::$proxyClasses[$interfaceName];
        }

        try {
            $proxyBody = ProxyHelper::generateLazyProxy(null, [$interface]);
        } catch (LogicException $logicException) {
            throw new ContainerException(\sprintf(
                'Не удалось создать lazy ghost для "%s": %s',
                $interfaceName,
                $logicException->getMessage(),
            ), 0, $logicException);
        }

        $shortName = 'LazyGhostProxy_' . strtr($interfaceName, ['\\' => '_']);
        $namespace = __NAMESPACE__ . '\\Internal';
        $fqn = $namespace . '\\' . $shortName;

        if (!class_exists($fqn, false)) {
            eval('namespace ' . $namespace . '; class ' . $shortName . ' ' . $proxyBody);
        }

        /** @var class-string<LazyObjectInterface> $fqn */
        return self::$proxyClasses[$interfaceName] = $fqn;
    }
}
