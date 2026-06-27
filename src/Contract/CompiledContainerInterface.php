<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Маркер контейнера, собранного компилятором без reflection на hot path.
 *
 * Реализации генерируются {@see ContainerCompilerInterface} на этапе deploy/build.
 * Публичный API совпадает с {@see ContainerInterface}; поведение `get()`/`make()`/`call()`
 * должно быть эквивалентно runtime-контейнеру на момент компиляции.
 *
 * @psalm-suppress PossiblyUnusedMethod Реализация появится в ContainerCompiler (#24)
 *
 * @see ContainerCompilerInterface
 */
interface CompiledContainerInterface extends ContainerInterface
{
    /**
     * FQCN сгенерированного класса compiled-контейнера.
     *
     * @psalm-suppress PossiblyUnusedMethod
     */
    public function getCompiledClassName(): string;
}
