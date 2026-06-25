<?php

declare(strict_types=1);

$docsDirectory = $argv[1] ?? null;

if (!\is_string($docsDirectory) || $docsDirectory === '') {
    fwrite(STDERR, "Использование: php tools/docs-check.php <каталог-документации>\n");
    exit(1);
}

$indexPath = $docsDirectory . '/index.html';
$classesPath = $docsDirectory . '/classes/CloudCastle-DI-Container.html';

foreach ([$indexPath, $classesPath] as $requiredFile) {
    if (!is_file($requiredFile)) {
        fwrite(STDERR, \sprintf("Отсутствует обязательный файл документации: %s\n", $requiredFile));
        exit(1);
    }
}

$fileCount = iterator_count(
    new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($docsDirectory)),
);

echo \sprintf("Документация собрана: %d файлов в %s.\n", $fileCount, $docsDirectory);
