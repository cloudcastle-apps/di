<?php

declare(strict_types=1);

namespace CloudCastle\DI\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Ошибка компиляции контейнера в PHP-класс (v2.0, #24).
 *
 * Типичные случаи: контейнер не заморожен, неподдерживаемое определение,
 * невозможность записать файл, некорректный FQCN целевого класса.
 */
final class ContainerCompileException extends RuntimeException implements ContainerExceptionInterface
{
}
