<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use ReflectionClass;

/**
 * Собирает {@see ContainerCompileSnapshot} из замороженного {@see Container}.
 */
final class ContainerCompileSnapshotBuilder
{
    public function __construct(
        private readonly CompileConstructorPlanner $constructorPlanner = new CompileConstructorPlanner(),
    ) {
    }

    public function build(Container $container): ContainerCompileSnapshot
    {
        $dump = $container->dump();
        $autowiring = $dump['autowiring'];

        $this->assertCompilableState(
            $container,
            $dump['decorators'],
            $autowiring['enabled'],
            $autowiring['property'],
            $autowiring['method'],
        );

        return new ContainerCompileSnapshot(
            aliases: $dump['aliases'],
            tags: $dump['tags'],
            bindings: $this->collectBindings($container, $dump['autowired']),
        );
    }

    /**
     * @param list<string> $decorators
     */
    private function assertCompilableState(
        Container $container,
        array $decorators,
        bool $globalAutowire,
        bool $propertyAutowire,
        bool $methodAutowire,
    ): void {
        if (!$container->isFrozen()) {
            throw new ContainerCompileException(
                'Компиляция возможна только для замороженного контейнера: вызовите freeze().',
            );
        }

        if ($decorators !== []) {
            throw new ContainerCompileException(
                'Compiled container не поддерживает декораторы.',
            );
        }

        if ($globalAutowire) {
            throw new ContainerCompileException(
                'Compiled container не поддерживает глобальный autowiring: регистрируйте классы через autowire().',
            );
        }

        if ($propertyAutowire || $methodAutowire) {
            throw new ContainerCompileException(
                'Compiled container поддерживает только constructor autowiring.',
            );
        }

        if ($container->hasAfterResolvingCallbacks()) {
            throw new ContainerCompileException(
                'Compiled container не поддерживает afterResolving().',
            );
        }
    }

    /**
     * @param list<string> $autowiredClassNames
     *
     * @return list<CompileServiceBinding>
     */
    private function collectBindings(Container $container, array $autowiredClassNames): array
    {
        $bindings = [];

        /** @psalm-suppress MixedAssignment */
        foreach ($container->exportDefinitions() as $id => $concrete) {
            $bindings[] = $this->bindingForDefinition($id, $concrete);
        }

        foreach ($autowiredClassNames as $className) {
            $bindings[] = $this->constructorPlanner->plan($container, $className);
        }

        return $bindings;
    }

    private function bindingForDefinition(string $id, mixed $concrete): CompileServiceBinding
    {
        if (\is_callable($concrete) && !\is_string($concrete)) {
            throw new ContainerCompileException(\sprintf(
                'Compiled container не поддерживает фабрики для сервиса "%s".',
                $id,
            ));
        }

        if (\is_object($concrete)) {
            return $this->bindingForPrebuiltObject($id, $concrete);
        }

        return new CompileServiceBinding(
            id: $id,
            kind: CompileServiceKind::Literal,
            literalValue: $concrete,
        );
    }

    private function bindingForPrebuiltObject(string $id, object $instance): CompileServiceBinding
    {
        $reflection = new ReflectionClass($instance);
        $constructor = $reflection->getConstructor();

        if ($constructor !== null && $constructor->getNumberOfParameters() > 0) {
            throw new ContainerCompileException(\sprintf(
                'Сервис "%s": готовый экземпляр с параметрами конструктора не поддерживается, используйте autowire().',
                $id,
            ));
        }

        return new CompileServiceBinding(
            id: $id,
            kind: CompileServiceKind::NewInstance,
            className: $reflection->getName(),
        );
    }
}
