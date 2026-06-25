<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Ошибка контейнера при разрешении или регистрации зависимости.
 *
 * Типичные случаи:
 *
 * - autowiring: класс не найден, не instantiable, неразрешимый параметр конструктора;
 * - autowiring: циклическая зависимость между сервисами;
 * - {@see \CloudCastle\DI\ClassScanner::scan()} / {@see \CloudCastle\DI\Container::scan()}: каталог не найден;
 * - {@see \CloudCastle\DI\ContainerRegistry::get()}: глобальный контейнер не инициализирован.
 *
 * Реализует {@see ContainerExceptionInterface} для совместимости с PSR-11.
 */
final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
