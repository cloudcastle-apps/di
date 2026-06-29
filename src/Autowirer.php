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
 *
 * @see Container::get() Точка входа autowiring в контейнере
 */
final class Autowirer
{
    /**
     * Разрешает зависимости параметров конструктора.
     */
    private readonly MemberResolver $memberResolver;

    /**
     * Внедряет зависимости в свойства после создания экземпляра.
     */
    private readonly PropertyInjector $propertyInjector;

    /**
     * Вызывает inject-методы экземпляра с autowiring параметров.
     */
    private readonly MethodInjector $methodInjector;

    /**
     * @param ContainerInterface $container Контейнер для разрешения зависимостей
     * @param AttributeServiceIdReader|null $attributeReader Читатель PHP attributes
     */
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
     * Создаёт экземпляр класса с autowiring конструктора, свойств и методов.
     *
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
     * Создаёт экземпляр через конструктор с autowiring параметров или без аргументов.
     *
     * @param ReflectionClass<object> $reflection Reflection целевого класса
     *
     * @throws ContainerException Если обязательный параметр конструктора не разрешается
     *
     * @return object Новый экземпляр без property/method injection
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
