<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Ошибка компиляции контейнера в PHP-класс (v2.0, #24).
 *
 * Типичные случаи:
 *
 * - контейнер не заморожен или содержит неподдерживаемые определения;
 * - невозможность записать сгенерированный файл на диск;
 * - некорректный FQCN целевого compiled-класса.
 *
 * Реализует {@see ContainerExceptionInterface} для совместимости с PSR-11.
 */
final class ContainerCompileException extends RuntimeException implements ContainerExceptionInterface
{
}
