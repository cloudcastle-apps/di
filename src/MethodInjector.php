<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;
use ReflectionMethod;

/**
 * Вызывает методы экземпляра с autowiring параметров (setter и прочие inject-методы).
 */
final readonly class MethodInjector
{
    private AttributeServiceIdReader $attributeReader;

    private MemberResolver $memberResolver;

    public function __construct(
        private ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $reader = $attributeReader ?? new AttributeServiceIdReader();
        $this->attributeReader = $reader;
        $this->memberResolver = new MemberResolver($container, $reader);
    }

    /**
     *
     * @param ReflectionClass<object> $reflection
     *
     * @throws ContainerException Если параметр метода не разрешается
     */
    public function inject(object $instance, ReflectionClass $reflection): void
    {
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED) as $method) {
            if (!$this->shouldInjectMethod($method, $reflection)) {
                continue;
            }

            /** @var list<mixed> $arguments */
            $arguments = [];

            foreach ($method->getParameters() as $parameter) {
                /** @psalm-suppress MixedAssignment */
                $arguments[] = $this->memberResolver->resolveParameter($parameter);
            }

            $method->invoke($instance, ...$arguments);
        }
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function shouldInjectMethod(ReflectionMethod $method, ReflectionClass $reflection): bool
    {
        if ($method->isStatic() || $method->isConstructor() || $method->isDestructor()) {
            return false;
        }

        if ($method->getDeclaringClass()->getName() !== $reflection->getName()) {
            return false;
        }

        if ($method->getParameters() === []) {
            return false;
        }

        if (str_starts_with($method->getName(), '__')) {
            return false;
        }

        if ($this->hasInjectAttributes($method)) {
            return true;
        }

        return $this->container->isMethodAutowiringEnabled();
    }

    private function hasInjectAttributes(ReflectionMethod $method): bool
    {
        if ($this->attributeReader->hasAny($method->getAttributes())) {
            return true;
        }

        foreach ($method->getParameters() as $parameter) {
            if ($this->attributeReader->hasAny($parameter->getAttributes())) {
                return true;
            }
        }

        return false;
    }
}
