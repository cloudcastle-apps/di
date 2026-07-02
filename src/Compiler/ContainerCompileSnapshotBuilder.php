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
    /**
     * @param CompileConstructorPlanner $constructorPlanner Планировщик autowired-привязок по конструктору
     */
    public function __construct(
        private readonly CompileConstructorPlanner $constructorPlanner = new CompileConstructorPlanner(),
    ) {
    }

    /**
     * Формирует неизменяемый снимок определений для генерации compiled-контейнера.
     *
     * @param Container $container Замороженный runtime-контейнер-источник
     *
     * @throws ContainerCompileException Если состояние контейнера несовместимо с компиляцией
     *                                   или определение сервиса не поддерживается
     *
     * @return ContainerCompileSnapshot Снимок aliases, tags, bindings и contextual-правил
     */
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
            contextual: $container->exportContextualBindings(),
        );
    }

    /**
     * Проверяет, что контейнер можно скомпилировать без runtime-only возможностей.
     *
     * @param Container $container Контейнер-источник
     * @param list<string> $decorators Зарегистрированные декораторы из {@see Container::dump()}
     * @param bool $globalAutowire Включён ли глобальный autowiring
     * @param bool $propertyAutowire Включён ли property autowiring
     * @param bool $methodAutowire Включён ли method autowiring
     *
     * @throws ContainerCompileException Если хотя бы одно ограничение compiled-контейнера нарушено
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
     * Собирает список привязок из явных определений и autowired-классов.
     *
     * @param Container $container Контейнер-источник
     * @param list<string> $autowiredClassNames FQCN, зарегистрированные через {@see Container::autowire()}
     *
     * @return list<CompileServiceBinding> Привязки для генерации метода `create()`
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

    /**
     * Преобразует одно runtime-определение в {@see CompileServiceBinding}.
     *
     * @param string $id Идентификатор сервиса
     * @param mixed $concrete Экземпляр, скаляр или class-string из {@see Container::set()}
     *
     * @throws ContainerCompileException Если concrete — callable-фабрика или объект с DI-конструктором
     *
     * @return CompileServiceBinding Привязка literal или `new Class()`
     */
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

    /**
     * Строит привязку для готового экземпляра, зарегистрированного через {@see Container::set()}.
     *
     * @param string $id Идентификатор сервиса
     * @param object $instance Готовый объект
     *
     * @throws ContainerCompileException Если у класса экземпляра есть параметризованный конструктор
     *
     * @return CompileServiceBinding Привязка `new Class()` без аргументов
     */
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
