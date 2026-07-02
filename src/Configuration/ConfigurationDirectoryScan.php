<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Режим обхода файлов в {@see ConfigurationDirectorySource}.
 */
enum ConfigurationDirectoryScan
{
    /** Только файлы непосредственно в указанном каталоге */
    case Flat;

    /** Рекурсивный обход подкаталогов */
    case Recursive;
}
