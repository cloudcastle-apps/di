<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Способ создания сервиса в compiled-контейнере.
 */
enum CompileServiceKind
{
    case Literal;
    case NewInstance;
    case Autowired;
}
