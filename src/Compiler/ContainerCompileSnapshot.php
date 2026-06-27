<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Снимок замороженного контейнера для генерации PHP-класса.
 */
final class ContainerCompileSnapshot
{
    /**
     * @param array<string, string> $aliases
     * @param array<string, list<string>> $tags
     * @param list<CompileServiceBinding> $bindings
     */
    public function __construct(
        public readonly array $aliases,
        public readonly array $tags,
        public readonly array $bindings,
    ) {
    }
}
