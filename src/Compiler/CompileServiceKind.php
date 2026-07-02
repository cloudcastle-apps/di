<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Способ создания сервиса в compiled-контейнере.
 */
enum CompileServiceKind
{
    /** Готовое скалярное значение или экземпляр без autowiring */
    case Literal;

    /** Создание через `new ClassName(...)` с фиксированными аргументами */
    case NewInstance;

    /** Autowiring конструктора по снимку контейнера на момент компиляции */
    case Autowired;
}
