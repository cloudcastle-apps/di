<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Генерирует PHP-класс compiled-контейнера, наследующий {@see AbstractCompiledContainer}.
 */
final class CompiledContainerPhpGenerator
{
    /**
     * Собирает исходный код final-класса с методом `create()` по снимку определений.
     *
     * @param string $className FQCN генерируемого класса
     * @param ContainerCompileSnapshot $snapshot Снимок aliases, tags, bindings и contextual-правил
     *
     * @return string Полный PHP-исходник файла compiled-контейнера
     */
    public function generate(string $className, ContainerCompileSnapshot $snapshot): string
    {
        $namespace = $this->extractNamespace($className);
        $shortName = $this->extractShortName($className);
        $definitionIds = array_map(
            static fn (CompileServiceBinding $binding): string => $binding->id,
            $snapshot->bindings,
        );

        $lines = [
            '<?php',
            '',
            'declare(strict_types=1);',
            '',
        ];

        if ($namespace !== '') {
            $lines[] = 'namespace ' . $namespace . ';';
            $lines[] = '';
        }

        $lines = [
            ...$lines,
            'use CloudCastle\\DI\\Compiler\\AbstractCompiledContainer;',
            'use CloudCastle\\DI\\Exception\\NotFoundException;',
            '',
            '/**',
            ' * Сгенерировано CloudCastle DI ContainerCompiler (#24).',
            ' *',
            ' * @generated',
            ' */',
            'final class ' . $shortName . ' extends AbstractCompiledContainer',
            '{',
            '    public function __construct()',
            '    {',
            '        parent::__construct(',
            '            compiledClassName: ' . var_export($className, true) . ',',
            '            aliases: ' . $this->exportArray($snapshot->aliases) . ',',
            '            tags: ' . $this->exportArray($snapshot->tags) . ',',
            '            definitionIds: ' . $this->exportArray($definitionIds) . ',',
            '            contextual: ' . $this->exportArray($snapshot->contextual) . ',',
            '        );',
            '    }',
            '',
            '    protected function create(string $id): mixed',
            '    {',
            '        return match ($id) {',
        ];

        foreach ($snapshot->bindings as $binding) {
            $lines[] = '            ' . var_export($binding->id, true)
                . ' => ' . $this->creationExpression($binding) . ',';
        }

        $lines = [
            ...$lines,
            "            default => throw new NotFoundException(\\sprintf('Сервис \"%s\" не зарегистрирован.', \$id)),",
            '        };',
            '    }',
            '}',
            '',
        ];

        return implode("\n", $lines);
    }

    /**
     * Возвращает правую часть `match`-ветки для создания экземпляра сервиса.
     *
     * @param CompileServiceBinding $binding Привязка сервиса из снимка
     *
     * @return string PHP-выражение: литерал, `new Class()` или `new Class($this->get(...), ...)`
     */
    private function creationExpression(CompileServiceBinding $binding): string
    {
        return match ($binding->kind) {
            CompileServiceKind::Literal => var_export($binding->literalValue, true),
            CompileServiceKind::NewInstance => 'new ' . $this->qualifiedClass($binding->className) . '()',
            CompileServiceKind::Autowired => $this->autowiredExpression($binding),
        };
    }

    /**
     * Формирует выражение `new Class(...)` для autowired-привязки.
     *
     * @param CompileServiceBinding $binding Autowired-привязка с выражениями аргументов конструктора
     *
     * @return string PHP-выражение создания экземпляра
     */
    private function autowiredExpression(CompileServiceBinding $binding): string
    {
        $class = $this->qualifiedClass($binding->className);

        if ($binding->argumentExpressions === []) {
            return 'new ' . $class . '()';
        }

        return 'new ' . $class . '(' . implode(', ', $binding->argumentExpressions) . ')';
    }

    /**
     * Возвращает FQCN с ведущим обратным слэшем для безопасного `new` в сгенерированном коде.
     *
     * @param string|null $className FQCN класса
     *
     * @return string Абсолютное имя класса вида `\Fully\Qualified\ClassName`
     */
    private function qualifiedClass(?string $className): string
    {
        return '\\' . ltrim((string) $className, '\\');
    }

    /**
     * Экспортирует массив в PHP-литерал для вставки в конструктор базового класса.
     *
     * @param array<mixed> $value Массив aliases, tags, definitionIds или contextual-правил
     *
     * @return string Строка из {@see var_export()}
     */
    private function exportArray(array $value): string
    {
        return var_export($value, true);
    }

    /**
     * Извлекает namespace из FQCN.
     *
     * @param string $className Полное имя класса
     *
     * @return string Namespace без ведущего слэша или пустая строка для глобального класса
     */
    private function extractNamespace(string $className): string
    {
        if (!str_contains($className, '\\')) {
            return '';
        }

        return substr($className, 0, (int) strrpos($className, '\\'));
    }

    /**
     * Извлекает короткое имя класса из FQCN.
     *
     * @param string $className Полное имя класса
     *
     * @return string Имя класса без namespace
     */
    private function extractShortName(string $className): string
    {
        if (!str_contains($className, '\\')) {
            return $className;
        }

        return substr($className, (int) strrpos($className, '\\') + 1);
    }
}
