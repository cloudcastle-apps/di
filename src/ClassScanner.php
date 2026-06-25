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
 * Обходит дерево каталогов рекурсивно, парсит объявления `namespace` и `class` из текста файла
 * без его выполнения, затем проверяет класс через autoload (`class_exists`) и reflection.
 *
 * Используется {@see Container::scan()}. Не заменяет Composer autoload — PSR-4 должен быть настроен.
 */
final class ClassScanner
{
    /**
     * Возвращает FQCN всех instantiable-классов в каталоге.
     *
     * Файлы без класса, abstract-классы, интерфейсы и trait пропускаются.
     * Enum и несколько классов в одном файле не поддерживаются парсером.
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
            $className = $this->resolveInstantiableClass($file->getPathname(), $namespacePrefix);

            if ($className !== null) {
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

        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            if ($file->getExtension() !== 'php') {
                continue;
            }

            yield $file;
        }
    }

    /**
     * Извлекает FQCN из файла и проверяет, что класс instantiable и проходит фильтр namespace.
     *
     * @param string $path Абсолютный путь к `.php`-файлу
     * @param string|null $namespacePrefix Префикс FQCN или `null` без фильтра
     *
     * @return string|null FQCN класса или `null`, если класс не подходит
     */
    private function resolveInstantiableClass(string $path, ?string $namespacePrefix): ?string
    {
        $className = $this->extractClassName($path);

        if ($className === null) {
            return null;
        }

        if ($namespacePrefix !== null && !str_starts_with($className, $namespacePrefix)) {
            return null;
        }

        if (!class_exists($className)) {
            return null;
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            return null;
        }

        return $className;
    }

    /**
     * Извлекает полное имя класса из PHP-файла без выполнения кода файла.
     *
     * Читает файл как текст; поддерживает модификаторы `abstract`, `final`, `readonly` перед `class`.
     * Не обрабатывает `enum`, trait и несколько классов в одном файле.
     *
     * @param string $path Путь к файлу
     *
     * @return string|null FQCN или `null`, если файл пуст, нечитаем или не содержит `class`
     */
    private function extractClassName(string $path): ?string
    {
        if (!is_readable($path)) {
            return null;
        }

        $contents = file_get_contents($path);

        if ($contents === false || $contents === '') {
            return null;
        }

        $namespace = '';

        if (preg_match('/^\s*namespace\s+([^;]+);/m', $contents, $matches) === 1) {
            $namespace = trim($matches[1]) . '\\';
        }

        if (preg_match('/(?:^|[;\s])(?:abstract\s+|final\s+|readonly\s+)*class\s+(\w+)/', $contents, $matches) !== 1) {
            return null;
        }

        return $namespace . $matches[1];
    }
}
