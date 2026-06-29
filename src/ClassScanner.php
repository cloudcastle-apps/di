<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Exception\ContainerException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;

/**
 * Находит instantiable-классы в каталоге для регистрации через autowiring.
 *
 * Обходит дерево каталогов рекурсивно, парсит объявления `namespace`, `class` и `enum` из текста файла
 * без его выполнения, затем проверяет тип через autoload (`class_exists`) и reflection.
 *
 * Используется {@see Container::scan()}. Не заменяет Composer autoload — PSR-4 должен быть настроен.
 */
final class ClassScanner
{
    /**
     * Возвращает FQCN всех instantiable-классов в каталоге.
     *
     * Файлы без типов, abstract-классы, интерфейсы, enum и trait пропускаются.
     *
     * @param string $directory Абсолютный или относительный путь к корню обхода
     * @param string|null $namespace Необязательный фильтр: только классы с FQCN, начинающимся с префикса
     *                               (trailing `\` добавляется автоматически)
     *
     * @throws ContainerException Если `$directory` не существует или не является каталогом
     *
     * @return list<string> Список полных имён классов в порядке обхода файлов
     */
    public function scan(string $directory, ?string $namespace = null): array
    {
        if (!is_dir($directory)) {
            throw new ContainerException(\sprintf('Каталог "%s" не найден.', $directory));
        }

        $namespacePrefix = $namespace !== null ? rtrim($namespace, '\\') . '\\' : null;

        /** @var list<string> $classNames */
        $classNames = [];

        foreach ($this->iteratePhpFiles($directory) as $file) {
            foreach ($this->resolveInstantiableClasses($file->getPathname(), $namespacePrefix) as $className) {
                $classNames[] = $className;
            }
        }

        return $classNames;
    }

    /**
     * Рекурсивно перебирает `.php`-файлы в каталоге.
     *
     * @param string $directory Корень обхода
     *
     * @return iterable<SplFileInfo> Файлы с расширением `php`
     *
     * @psalm-suppress MixedAssignment
     */
    private function iteratePhpFiles(string $directory): iterable
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
        );

        /** @var list<SplFileInfo> $allFiles */
        $allFiles = [];

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $allFiles[] = $file;
        }

        usort($allFiles, $this->compareSplFileInfoByPath(...));

        foreach ($allFiles as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Сравнивает файлы по полному пути для стабильной лексикографической сортировки.
     *
     * @param SplFileInfo $left Левый элемент сравнения
     * @param SplFileInfo $right Правый элемент сравнения
     *
     * @return int Результат strcmp для путей файлов
     */
    private function compareSplFileInfoByPath(SplFileInfo $left, SplFileInfo $right): int
    {
        return strcmp($left->getPathname(), $right->getPathname());
    }

    /**
     * Извлекает instantiable FQCN из файла.
     *
     * @param string $path Абсолютный путь к `.php`-файлу
     * @param string|null $namespacePrefix Префикс FQCN или `null` без фильтра
     *
     * @return list<string>
     */
    private function resolveInstantiableClasses(string $path, ?string $namespacePrefix): array
    {
        /** @var list<string> $classNames */
        $classNames = [];

        foreach ($this->extractDeclaredTypeNames($path) as $className) {
            if ($namespacePrefix !== null && !str_starts_with($className, $namespacePrefix)) {
                continue;
            }

            if (!class_exists($className)) {
                continue;
            }

            $reflection = new ReflectionClass($className);

            if (!$reflection->isInstantiable()) {
                continue;
            }

            $classNames[] = $className;
        }

        return $classNames;
    }

    /**
     * Извлекает FQCN объявленных `class` и `enum` из PHP-файла без выполнения кода.
     *
     * @param string $path Путь к файлу
     *
     * @return list<string>
     */
    private function extractDeclaredTypeNames(string $path): array
    {
        if (!is_readable($path)) {
            return [];
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return [];
        }

        $namespace = '';

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $matches) === 1) {
            $namespace = trim($matches[1]) . '\\';
        }

        if (
            preg_match_all(
                '/(?:^|[;\s])(?:abstract\s+|final\s+|readonly\s+)*(?:class|enum)\s+(\w+)/',
                $contents,
                $matches,
            ) === false
        ) {
            return [];
        }

        /** @var list<string> $typeNames */
        $typeNames = [];

        foreach ($matches[1] as $shortName) {
            $typeNames[] = $namespace . $shortName;
        }

        return $typeNames;
    }
}
