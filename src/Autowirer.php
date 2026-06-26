<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionClass;

/**
 * Создаёт экземпляры классов с autowiring конструктора, свойств и методов.
 *
 * Порядок: конструктор → свойства → методы.
 */
final readonly class Autowirer
{
    private MemberResolver $memberResolver;

    private PropertyInjector $propertyInjector;

    private MethodInjector $methodInjector;

    public function __construct(
        ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $reader = $attributeReader ?? new AttributeServiceIdReader();
        $this->memberResolver = new MemberResolver($container, $reader);
        $this->propertyInjector = new PropertyInjector($container, $reader);
        $this->methodInjector = new MethodInjector($container, $reader);
    }

    /**
     * @param string $className Полное имя класса (class-string)
     *
     * @throws ContainerException Если класс не найден, не instantiable или зависимость не разрешается
     *
     * @return object Созданный экземпляр
     */
    public function instantiate(string $className): object
    {
        if (!class_exists($className)) {
            throw new ContainerException(\sprintf('Класс "%s" не найден.', $className));
        }

        $reflection = new ReflectionClass($className);

        if (!$reflection->isInstantiable()) {
            throw new ContainerException(\sprintf('Класс "%s" нельзя создать через autowiring.', $className));
        }

        $instance = $this->createInstance($reflection);
        $this->propertyInjector->inject($instance, $reflection);
        $this->methodInjector->inject($instance, $reflection);

        return $instance;
    }

    /**
     * @param ReflectionClass<object> $reflection
     */
    private function createInstance(ReflectionClass $reflection): object
    {
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return $reflection->newInstance();
        }

        /** @var list<mixed> $arguments */
        $arguments = [];

        foreach ($constructor->getParameters() as $parameter) {
            /** @psalm-suppress MixedAssignment */
            $arguments[] = $this->memberResolver->resolveParameter($parameter);
        }

        return $reflection->newInstanceArgs($arguments);
    }
}
