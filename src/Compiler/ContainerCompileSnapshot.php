<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Снимок замороженного контейнера для генерации PHP-класса.
 */
final class ContainerCompileSnapshot
{
    /**
     * Снимок alias, тегов, bindings и contextual-правил замороженного контейнера.
     *
     * @param array<string, string> $aliases Карта alias → target id
     * @param array<string, list<string>> $tags Карта тег → список id сервисов
     * @param list<CompileServiceBinding> $bindings Описания сервисов для компиляции
     * @param array<string, array<string, string>> $contextual Contextual give: consumer FQCN → need → give
     */
    public function __construct(
        public readonly array $aliases,
        public readonly array $tags,
        public readonly array $bindings,
        public readonly array $contextual = [],
    ) {
    }
}
