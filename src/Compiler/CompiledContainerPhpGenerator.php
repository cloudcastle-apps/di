<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

/**
 * Генерирует PHP-класс compiled-контейнера.
 */
final class CompiledContainerPhpGenerator
{
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

    private function creationExpression(CompileServiceBinding $binding): string
    {
        return match ($binding->kind) {
            CompileServiceKind::Literal => var_export($binding->literalValue, true),
            CompileServiceKind::NewInstance => 'new ' . $this->qualifiedClass($binding->className) . '()',
            CompileServiceKind::Autowired => $this->autowiredExpression($binding),
        };
    }

    private function autowiredExpression(CompileServiceBinding $binding): string
    {
        $class = $this->qualifiedClass($binding->className);

        if ($binding->argumentExpressions === []) {
            return 'new ' . $class . '()';
        }

        return 'new ' . $class . '(' . implode(', ', $binding->argumentExpressions) . ')';
    }

    private function qualifiedClass(?string $className): string
    {
        return '\\' . ltrim((string) $className, '\\');
    }

    /**
     * @param array<mixed> $value
     */
    private function exportArray(array $value): string
    {
        return var_export($value, true);
    }

    private function extractNamespace(string $className): string
    {
        if (!str_contains($className, '\\')) {
            return '';
        }

        return substr($className, 0, (int) strrpos($className, '\\'));
    }

    private function extractShortName(string $className): string
    {
        if (!str_contains($className, '\\')) {
            return $className;
        }

        return substr($className, (int) strrpos($className, '\\') + 1);
    }
}
