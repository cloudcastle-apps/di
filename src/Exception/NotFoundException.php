<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Сервис с указанным идентификатором не найден в контейнере.
 *
 * Реализует {@see NotFoundExceptionInterface} для совместимости с PSR-11.
 */
final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
