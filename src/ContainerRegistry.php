<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;

/**
 * Глобальный singleton-реестр контейнера приложения.
 *
 * Хранит один экземпляр {@see ContainerInterface}, установленный в точке входа (bootstrap).
 * Предназначен для доступа к DI из legacy-кода и procedural scripts; в новом коде предпочтительна
 * явная передача контейнера или зависимостей через конструктор.
 *
 * {@see reset()} сбрасывает состояние — используйте в PHPUnit `tearDown` для изоляции тестов.
 *
 * @see Container Реализация контейнера по умолчанию
 */
final class ContainerRegistry
{
    /** @var ContainerInterface|null Текущий глобальный контейнер или `null` до {@see set()} */
    private static ?ContainerInterface $container = null;

    /**
     * Регистрирует глобальный экземпляр контейнера.
     *
     * Повторный вызов заменяет предыдущий контейнер; старые ссылки на старый экземпляр остаются валидными.
     *
     * @param ContainerInterface $container Настроенный контейнер приложения
     */
    public static function set(ContainerInterface $container): void
    {
        self::$container = $container;
    }

    /**
     * Возвращает глобальный контейнер, установленный через {@see set()}.
     *
     * @throws ContainerException Если {@see set()} ещё не вызывался (см. {@see has()})
     *
     * @return ContainerInterface Текущий контейнер приложения
     */
    public static function get(): ContainerInterface
    {
        if (!(self::$container instanceof ContainerInterface)) {
            throw new ContainerException(
                'Глобальный контейнер не инициализирован. Вызовите ContainerRegistry::set().',
            );
        }

        return self::$container;
    }

    /**
     * Проверяет, был ли зарегистрирован глобальный контейнер.
     *
     * @return bool `true`, если после последнего {@see set()} не вызывался {@see reset()}
     */
    public static function has(): bool
    {
        return self::$container instanceof ContainerInterface;
    }

    /**
     * Сбрасывает глобальный контейнер.
     *
     * После вызова {@see get()} бросит {@see ContainerException} до следующего {@see set()}.
     * Используйте для изоляции unit/integration-тестов.
     */
    public static function reset(): void
    {
        self::$container = null;
    }
}
