<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

/**
 * Сервис с указанным идентификатором не найден в контейнере.
 *
 * Бросается из {@see \CloudCastle\DI\Container::get()}, когда id не зарегистрирован через
 * {@see \CloudCastle\DI\Contract\ContainerInterface::set()}, недоступен через autowiring
 * и отсутствует в singleton-кэше.
 *
 * Реализует {@see NotFoundExceptionInterface} для совместимости с PSR-11.
 */
final class NotFoundException extends RuntimeException implements NotFoundExceptionInterface
{
}
