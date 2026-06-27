<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Режим обхода файлов в {@see ConfigurationDirectorySource}.
 */
enum ConfigurationDirectoryScan
{
    case Flat;
    case Recursive;
}
