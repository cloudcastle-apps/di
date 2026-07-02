<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Exception\ContainerCompileException;
use ReflectionClass;

/**
 * Строит {@see CompileServiceBinding} для autowired-класса по конструктору.
 */
final class CompileConstructorPlanner
{
    /**
     * @param CompileParameterReferenceResolver $parameterResolver Преобразует параметры конструктора в PHP-выражения
     * @param AttributeServiceIdReader $attributeReader Читает PHP attributes для service id
     */
    public function __construct(
        private readonly CompileParameterReferenceResolver $parameterResolver = new CompileParameterReferenceResolver(),
        private readonly AttributeServiceIdReader $attributeReader = new AttributeServiceIdReader(),
    ) {
    }

    /**
     * Планирует autowired-привязку: `new Class(...)` с выражениями аргументов конструктора.
     *
     * @param Container $container Контейнер-источник для разрешения зависимостей
     * @param string $className FQCN autowired-класса
     *
     * @throws ContainerCompileException Если класс не найден, не instantiable, использует property/method injection
     *                                   или параметр конструктора не разрешается
     *
     * @return CompileServiceBinding Привязка вида {@see CompileServiceKind::Autowired}
     */
    public function plan(Container $container, string $className): CompileServiceBinding
    {
        if (!class_exists($className)) {
            throw new ContainerCompileException(\sprintf('Класс "%s" не найден.', $className));
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new ContainerCompileException(\sprintf(
                'Класс "%s" нельзя создать через autowiring.',
                $className,
            ));
        }

        $constructor = $reflection->getConstructor();
        $arguments = [];

        if ($constructor !== null) {
            foreach ($constructor->getParameters() as $parameter) {
                $arguments[] = $this->parameterResolver->resolveExpression($container, $parameter);
            }
        }

        $this->assertConstructorOnlyDependencies($reflection);

        return new CompileServiceBinding(
            id: $className,
            kind: CompileServiceKind::Autowired,
            className: $className,
            argumentExpressions: $arguments,
        );
    }

    /**
     * Запрещает property и method injection — compiled-контейнер поддерживает только конструктор.
     *
     * @param ReflectionClass<object> $reflection Reflection autowired-класса
     *
     * @throws ContainerCompileException Если найдены DI-attributes на свойствах или методах
     */
    private function assertConstructorOnlyDependencies(ReflectionClass $reflection): void
    {
        foreach ($reflection->getProperties() as $property) {
            if ($this->attributeReader->hasAny($property->getAttributes())) {
                throw new ContainerCompileException(\sprintf(
                    'Compiled container не поддерживает property injection в классе "%s".',
                    $reflection->getName(),
                ));
            }
        }

        foreach ($reflection->getMethods() as $method) {
            if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            if ($method->getName() === '__construct') {
                continue;
            }

            if ($this->attributeReader->hasAny($method->getAttributes())) {
                throw new ContainerCompileException(\sprintf(
                    'Compiled container не поддерживает method injection в классе "%s".',
                    $reflection->getName(),
                ));
            }

            foreach ($method->getParameters() as $parameter) {
                if ($this->attributeReader->hasAny($parameter->getAttributes())) {
                    throw new ContainerCompileException(\sprintf(
                        'Compiled container не поддерживает method injection в классе "%s".',
                        $reflection->getName(),
                    ));
                }
            }
        }
    }
}
