<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Ошибка контейнера при разрешении или регистрации зависимости.
 *
 * Реализует {@see ContainerExceptionInterface} для совместимости с PSR-11.
 */
final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
