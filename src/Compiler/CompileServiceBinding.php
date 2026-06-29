<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Описание одного сервиса для компиляции.
 */
final class CompileServiceBinding
{
    /**
     * Описание одного сервиса для генерации метода `create()` в compiled-контейнере.
     *
     * @param string $id Идентификатор сервиса
     * @param CompileServiceKind $kind Способ создания экземпляра
     * @param string|null $className FQCN класса для {@see CompileServiceKind::NewInstance} и {@see CompileServiceKind::Autowired}
     * @param mixed $literalValue Готовое значение для {@see CompileServiceKind::Literal}
     * @param list<string> $argumentExpressions PHP-выражения аргументов конструктора (для NewInstance)
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
