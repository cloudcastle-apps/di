<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Описание одного сервиса для компиляции.
 */
final class CompileServiceBinding
{
    /**
     * @param list<string> $argumentExpressions PHP-выражения для аргументов конструктора
     */
    public function __construct(
        public readonly string $id,
        public readonly CompileServiceKind $kind,
        public readonly ?string $className = null,
        public readonly mixed $literalValue = null,
        public readonly array $argumentExpressions = [],
    ) {
    }
}
