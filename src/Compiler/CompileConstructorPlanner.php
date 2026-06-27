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
    public function __construct(
        private readonly CompileParameterReferenceResolver $parameterResolver = new CompileParameterReferenceResolver(),
        private readonly AttributeServiceIdReader $attributeReader = new AttributeServiceIdReader(),
    ) {
    }

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
     * @param ReflectionClass<object> $reflection
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
