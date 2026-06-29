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
 * @see ContainerCompilerInterface
 */
interface CompiledContainerInterface extends ContainerInterface
{
    /**
     * Возвращает FQCN сгенерированного класса compiled-контейнера.
     *
     * @return string Полное имя класса, созданного {@see ContainerCompilerInterface::compile()}
     */
    public function getCompiledClassName(): string;
}
