<?php

declare(strict_types=1);

namespace CloudCastle\DI;

use Closure;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;

/**
 * Вызывает callable с autowiring параметров через {@see MemberResolver}.
 *
 * Поддерживаемые формы callable:
 * - `Closure` и first-class callable (`$obj->method(...)`);
 * - массив `[object|class-string, string]` (метод экземпляра или static);
 * - объект с методом `__invoke`;
 * - строка с именем глобальной функции.
 *
 * Правила разрешения параметров совпадают с конструктором при {@see ContainerInterface::get()}:
 * PHP attributes, autowiring по имени (если включён), разрешение по типу.
 * Явные значения в `$parameters` переопределяют autowire по имени параметра.
 *
 * @see Container::call() Публичная точка входа в контейнере
 */
final class CallableInvoker
{
    /**
     * Разрешает зависимости параметров callable из контейнера.
     */
    private readonly MemberResolver $memberResolver;

    /**
     * @param ContainerInterface $container Контейнер для autowiring параметров callable
     */
    public function __construct(
        ContainerInterface $container,
        ?AttributeServiceIdReader $attributeReader = null,
    ) {
        $this->memberResolver = new MemberResolver($container, $attributeReader);
    }

    /**
     * Вызывает callable, подставляя аргументы через autowiring и явные `$parameters`.
     *
     * @param callable $callable Вызываемая функция, closure, метод или invokable-объект
     * @param array<string, mixed> $parameters Явные значения по имени параметра (переопределяют autowire)
     *
     * @throws ContainerException Если обязательный параметр не разрешается или callable некорректен
     *
     * @return mixed Результат вызова callable
     */
    public function invoke(callable $callable, array $parameters = []): mixed
    {
        $reflection = $this->reflectCallable($callable);
        /** @var list<mixed> $arguments */
        $arguments = [];

        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();

            if (\array_key_exists($name, $parameters)) {
                /** @psalm-suppress MixedAssignment */
                $arguments[] = $parameters[$name];

                continue;
            }

            /** @psalm-suppress MixedAssignment */
            $arguments[] = $this->memberResolver->resolveParameter($parameter);
        }

        return $this->invokeWithArguments($callable, $reflection, $arguments);
    }

    /**
     * Строит reflection для поддерживаемых форм callable.
     *
     * @param callable $callable Вызываемый callable
     *
     * @throws ContainerException Если тип callable не поддерживается
     */
    private function reflectCallable(callable $callable): ReflectionFunctionAbstract
    {
        return $this->reflectCallableValue($callable);
    }

    /**
     * @throws ContainerException Если значение не является поддерживаемым callable
     */
    private function reflectCallableValue(mixed $callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (\is_array($callable)) {
            $objectOrMethod = $callable[0] ?? null;
            $method = $callable[1] ?? null;

            if (!\is_string($method)) {
                throw new ContainerException('Неподдерживаемый тип callable.');
            }

            if (!\is_object($objectOrMethod) && !\is_string($objectOrMethod)) {
                throw new ContainerException('Неподдерживаемый тип callable.');
            }

            /** @var class-string $objectOrMethod */
            return new ReflectionMethod($objectOrMethod, $method);
        }

        if (\is_object($callable)) {
            return new ReflectionMethod($callable, '__invoke');
        }

        if (\is_string($callable)) {
            /** @var callable-string $callable */
            return new ReflectionFunction($callable);
        }

        throw new ContainerException('Неподдерживаемый тип callable.');
    }

    /**
     * Выполняет callable с уже собранным списком аргументов.
     *
     * @param mixed $callable Исходный callable (для нестатического метода — массив `[object, string]`)
     * @param ReflectionFunctionAbstract $reflection Reflection параметров callable
     * @param list<mixed> $arguments Аргументы в порядке параметров
     *
     * @throws ContainerException Если для нестатического метода не найден объект-получатель
     *
     * @return mixed Результат вызова
     */
    private function invokeWithArguments(
        mixed $callable,
        ReflectionFunctionAbstract $reflection,
        array $arguments,
    ): mixed {
        if ($reflection instanceof ReflectionMethod) {
            $target = null;

            if (!$reflection->isStatic()) {
                if (\is_object($callable)) {
                    $target = $callable;
                } elseif (\is_array($callable) && \array_key_exists(0, $callable) && \is_object($callable[0])) {
                    $target = $callable[0];
                }

                if (!\is_object($target)) {
                    throw new ContainerException('Callable метода требует объект.');
                }
            }

            return $reflection->invokeArgs($target, $arguments);
        }

        if (!$reflection instanceof ReflectionFunction) {
            throw new ContainerException('Неподдерживаемый тип reflection callable.');
        }

        return $reflection->invokeArgs($arguments);
    }
}
