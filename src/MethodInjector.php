<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;

/**
 * Вызывает методы экземпляра с autowiring параметров (setter и прочие inject-методы).
 *
 * Обрабатывает public/protected методы с inject-attributes или при включённом method autowiring.
 */
final class MethodInjector
{
    /**
     * Проверяет наличие inject-attributes на методах и их параметрах.
     */
    private readonly AttributeServiceIdReader $attributeReader;

    /**
     * Разрешает значения параметров inject-методов.
     */
    private readonly MemberResolver $memberResolver;

    /**
     * @param ContainerInterface $container Контейнер для разрешения зависимостей
     * @param AttributeServiceIdReader|null $attributeReader Читатель id из attributes (по умолчанию новый экземпляр)
     */
    public function __construct(
        private readonly ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $reader = $attributeReader ?? new AttributeServiceIdReader();
        $this->attributeReader = $reader;
        $this->memberResolver = new MemberResolver($container, $reader);
    }

    /**
     * Вызывает все подходящие inject-методы экземпляра с autowiring параметров.
     *
     * @param object $instance Целевой экземпляр
     * @param ReflectionClass<object> $reflection Reflection класса экземпляра
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

            try {
                $method->invoke($instance, ...$arguments);
            } catch (ReflectionException $e) {
                throw new ContainerException(\sprintf(
                    'Ошибка вызова inject-метода %s::%s: %s',
                    $reflection->getName(),
                    $method->getName(),
                    $e->getMessage(),
                ), 0, $e);
            }
        }
    }

    /**
     * Определяет, нужно ли вызывать метод как inject-метод.
     *
     * @param ReflectionMethod $method Reflection метода
     * @param ReflectionClass<object> $reflection Reflection класса экземпляра
     *
     * @return bool `true`, если метод подлежит вызову с autowiring
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

    /**
     * Проверяет наличие inject-attributes на методе или его параметрах.
     *
     * @param ReflectionMethod $method Reflection метода
     *
     * @return bool `true`, если найден известный inject-attribute
     */
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
