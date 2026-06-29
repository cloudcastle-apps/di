<?php

declare(strict_types=1);

/**
 * Расширяет PHPUnit coverage-xml для Infection: на каждой строке файла
 * перечисляет все тесты, которые покрывают хотя бы одну строку этого же файла.
 *
 * Infection запускает только covering-тесты; на CI xdebug иногда отдаёт узкий
 * набор {@code <covered by=>} на строку — мутанты «убегают». Union по файлу
 * добавляет sibling-тесты из того же *.php.xml без смены line coverage.
 *
 * @param list<string> $argv
 */
$coverageXmlDir = $argv[1] ?? dirname(__DIR__) . '/var/coverage/coverage-xml';

if (!is_dir($coverageXmlDir)) {
    fwrite(STDERR, "Каталог coverage-xml не найден: {$coverageXmlDir}\n");

    exit(1);
}

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($coverageXmlDir, FilesystemIterator::SKIP_DOTS),
);

$expandedFiles = 0;

foreach ($iterator as $fileInfo) {
    if (!$fileInfo instanceof SplFileInfo || $fileInfo->getExtension() !== 'xml') {
        continue;
    }

    if ($fileInfo->getFilename() === 'index.xml') {
        continue;
    }

    $path = $fileInfo->getPathname();
    $dom = new DOMDocument();
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;

    if (!@$dom->load($path)) {
        continue;
    }

    $xpath = new DOMXPath($dom);
    $xpath->registerNamespace('c', 'https://schema.phpunit.de/coverage/1.0');

    $lineNodes = $xpath->query('//c:coverage/c:line');

    if ($lineNodes === false || $lineNodes->length === 0) {
        continue;
    }

    /** @var array<string, true> $allTests */
    $allTests = [];

    foreach ($lineNodes as $lineNode) {
        if (!$lineNode instanceof DOMElement) {
            continue;
        }

        foreach ($lineNode->getElementsByTagName('covered') as $coveredNode) {
            if (!$coveredNode instanceof DOMElement) {
                continue;
            }

            $testName = $coveredNode->getAttribute('by');

            if ($testName !== '') {
                $allTests[$testName] = true;
            }
        }
    }

    if ($allTests === []) {
        continue;
    }

    $changed = false;

    foreach ($lineNodes as $lineNode) {
        if (!$lineNode instanceof DOMElement) {
            continue;
        }

        /** @var array<string, true> $present */
        $present = [];

        foreach ($lineNode->getElementsByTagName('covered') as $coveredNode) {
            if (!$coveredNode instanceof DOMElement) {
                continue;
            }

            $testName = $coveredNode->getAttribute('by');

            if ($testName !== '') {
                $present[$testName] = true;
            }
        }

        foreach (array_keys($allTests) as $testName) {
            if (isset($present[$testName])) {
                continue;
            }

            $covered = $dom->createElementNS('https://schema.phpunit.de/coverage/1.0', 'covered');
            $covered->setAttribute('by', $testName);
            $lineNode->appendChild($covered);
            $changed = true;
        }
    }

    if ($changed) {
        $dom->save($path);
        ++$expandedFiles;
    }
}

fwrite(STDOUT, "expand-infection-coverage-xml: расширено файлов {$expandedFiles}\n");

exit(0);
