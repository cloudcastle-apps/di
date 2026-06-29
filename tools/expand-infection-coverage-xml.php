<?php

declare(strict_types=1);

/**
 * Расширяет PHPUnit coverage-xml для Infection.
 *
 * 1. Union всех {@code <covered by=>} по каждому src-файлу (sibling-тесты).
 * 2. Добавляет все тесты с {@see CoversClass} для класса из XML и связанных классов.
 *
 * На CI xdebug иногда не привязывает строку к тесту, который реально убивает мутант;
 * CoversClass-union подмешивает тесты по декларации покрытия, а не только по line hit.
 *
 * @param list<string> $argv
 */
$coverageXmlDir = $argv[1] ?? dirname(__DIR__) . '/var/coverage/coverage-xml';
$testsRoot = $argv[2] ?? dirname(__DIR__) . '/tests';

if (!is_dir($coverageXmlDir)) {
    fwrite(STDERR, "Каталог coverage-xml не найден: {$coverageXmlDir}\n");

    exit(1);
}

/** @var array<string, list<string>> $relatedCoverageClasses */
$relatedCoverageClasses = [
    'CloudCastle\\DI\\Container' => [
        'CloudCastle\\DI\\ContainerSmartCacheSupport',
        'CloudCastle\\DI\\ContainerSmartCacheApi',
        'CloudCastle\\DI\\ContainerProfilingSupport',
        'CloudCastle\\DI\\ContainerProfilingApi',
        'CloudCastle\\DI\\ContainerMemoryPoolSupport',
        'CloudCastle\\DI\\ContainerMemoryPoolApi',
        'CloudCastle\\DI\\ServiceAliasResolver',
        'CloudCastle\\DI\\ContextualBindingSupport',
    ],
];

/**
 * @return array<string, list<string>>
 */
function buildCoversClassTestMap(string $testsRoot): array
{
    /** @var array<string, list<string>> $map */
    $map = [];

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($testsRoot, FilesystemIterator::SKIP_DOTS),
    );

    foreach ($iterator as $fileInfo) {
        if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'php') {
            continue;
        }

        $content = file_get_contents($fileInfo->getPathname());

        if ($content === false) {
            continue;
        }

        if (!preg_match('/namespace\s+([^;]+);/', $content, $namespaceMatch)) {
            continue;
        }

        if (!preg_match('/(?:final\s+)?class\s+(\w+)/', $content, $classMatch)) {
            continue;
        }

        $namespace = $namespaceMatch[1];
        $testClass = $namespace . '\\' . $classMatch[1];

        if (!preg_match_all('/#\[CoversClass\(([^)]+)\)\]/', $content, $coversMatches)) {
            continue;
        }

        /** @var list<string> $coveredClasses */
        $coveredClasses = [];

        foreach ($coversMatches[1] as $coversArgument) {
            $coveredClasses[] = resolveCoversClassName(trim($coversArgument), $content, $namespace);
        }

        if (!preg_match_all('/function\s+(test\w+)\s*\(/', $content, $methodMatches)) {
            continue;
        }

        foreach ($coveredClasses as $coveredClass) {
            foreach ($methodMatches[1] as $methodName) {
                $map[$coveredClass][] = $testClass . '::' . $methodName;
            }
        }
    }

    foreach ($map as $class => $tests) {
        $map[$class] = array_values(array_unique($tests));
    }

    return $map;
}

function resolveCoversClassName(string $argument, string $content, string $namespace): string
{
    if (!str_ends_with($argument, '::class')) {
        return $argument;
    }

    $classRef = substr($argument, 0, -strlen('::class'));

    if (str_starts_with($classRef, '\\')) {
        return ltrim($classRef, '\\');
    }

    if (str_contains($classRef, '\\')) {
        return $classRef;
    }

    if (preg_match('/^use\s+' . preg_quote($classRef, '/') . '\s*;/m', $content)) {
        if (preg_match('/^use\s+([\w\\\\]+\\\\' . preg_quote($classRef, '/') . ')\s*;/m', $content, $useMatch)) {
            return $useMatch[1];
        }
    }

    return $namespace . '\\' . $classRef;
}

/**
 * @param array<string, list<string>> $coversClassTestMap
 * @param array<string, list<string>> $relatedCoverageClasses
 *
 * @return list<string>
 */
function testsForSourceClass(
    string $sourceClass,
    array $coversClassTestMap,
    array $relatedCoverageClasses,
): array {
    /** @var array<string, true> $tests */
    $tests = [];

    foreach ([$sourceClass, ...($relatedCoverageClasses[$sourceClass] ?? [])] as $className) {
        foreach ($coversClassTestMap[$className] ?? [] as $testName) {
            $tests[$testName] = true;
        }
    }

    return array_keys($tests);
}

$coversClassTestMap = buildCoversClassTestMap($testsRoot);

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($coverageXmlDir, FilesystemIterator::SKIP_DOTS),
);

$expandedFiles = 0;
$addedCoversClassLinks = 0;

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

    $classNode = $xpath->query('//c:class')->item(0);
    $sourceClass = $classNode instanceof DOMElement ? $classNode->getAttribute('name') : '';

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

    if ($sourceClass !== '') {
        foreach (testsForSourceClass($sourceClass, $coversClassTestMap, $relatedCoverageClasses) as $testName) {
            $allTests[$testName] = true;
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
            ++$addedCoversClassLinks;
        }
    }

    if ($changed) {
        $dom->save($path);
        ++$expandedFiles;
    }
}

fwrite(
    STDOUT,
    'expand-infection-coverage-xml: расширено файлов '
    . $expandedFiles
    . ', добавлено CoversClass-связей '
    . $addedCoversClassLinks
    . ', классов в карте '
    . \count($coversClassTestMap)
    . "\n",
);

exit(0);
