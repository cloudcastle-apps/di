<?php

declare(strict_types=1);

namespace CloudCastle\DI\Compiler;

use CloudCastle\DI\AttributeServiceIdReader;
use CloudCastle\DI\Container;
use CloudCastle\DI\Contract\ContainerInterface;
use CloudCastle\DI\Exception\ContainerCompileException;
use CloudCastle\DI\ParameterTypeResolver;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use Throwable;

/**
 * Преобразует параметр конструктора в PHP-выражение для compiled-контейнера.
 *
 * Выражения встраиваются в сгенерированный `new Class($this->get('id'), ...)` без reflection на hot path.
 */
final class CompileParameterReferenceResolver
{
    /**
     * @param AttributeServiceIdReader $attributeReader Читает PHP attributes с service id на параметре
     */
    public function __construct(
        private readonly AttributeServiceIdReader $attributeReader = new AttributeServiceIdReader(),
    ) {
    }

    /**
     * Возвращает PHP-выражение значения аргумента конструктора для вставки в сгенерированный код.
     *
     * Порядок разрешения: attribute → autowire по имени → тип (включая contextual give) → default value.
     *
     * @param Container $container Контейнер-источник определений
     * @param ReflectionParameter $parameter Параметр конструктора autowired-класса
     *
     * @throws ContainerCompileException Если обязательный параметр не разрешается при компиляции
     *
     * @return string PHP-выражение: `$this`, `$this->get('id')`, литерал или `var_export` default
     */
    public function resolveExpression(Container $container, ReflectionParameter $parameter): string
    {
        $attributeServiceId = $this->attributeReader->read($parameter->getAttributes());

        if ($attributeServiceId !== null) {
            return $this->serviceGetExpression($attributeServiceId);
        }

        if ($container->isParameterNameAutowiringEnabled() && $container->has($parameter->getName())) {
            return $this->serviceGetExpression($parameter->getName());
        }

        $type = $parameter->getType();

        if ($type === null) {
            return $this->defaultValueExpression($parameter);
        }

        if ($type instanceof ReflectionUnionType) {
            return $this->resolveUnionExpression($container, $parameter, $type);
        }

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return $this->defaultValueExpression($parameter);
        }

        return $this->resolveNamedTypeExpression($container, $parameter, $type);
    }

    /**
     * Разрешает параметр с именованным class/interface типом.
     *
     * @param Container $container Контейнер-источник
     * @param ReflectionParameter $parameter Параметр конструктора
     * @param ReflectionNamedType $type Именованный тип параметра
     *
     * @throws ContainerCompileException Если contextual give не зарегистрирован или тип не разрешается
     *
     * @return string PHP-выражение аргумента
     */
    private function resolveNamedTypeExpression(
        Container $container,
        ReflectionParameter $parameter,
        ReflectionNamedType $type,
    ): string {
        $typeName = $type->getName();

        if ($typeName === ContainerInterface::class || $typeName === PsrContainerInterface::class) {
            return '$this';
        }

        $consumerClass = $parameter->getDeclaringClass()?->getName();

        if ($consumerClass !== null) {
            $contextualGive = $container->contextualGive($consumerClass, $typeName);

            if ($contextualGive !== null) {
                if (!$container->has($contextualGive)) {
                    throw new ContainerCompileException(\sprintf(
                        'Contextual give "%s" для %s::%s не зарегистрирован в контейнере.',
                        $contextualGive,
                        $consumerClass,
                        $parameter->getName(),
                    ));
                }

                return $this->serviceGetExpression($contextualGive);
            }
        }

        if (!$container->has($typeName)) {
            if ($parameter->isDefaultValueAvailable()) {
                return var_export($parameter->getDefaultValue(), true);
            }

            throw new ContainerCompileException(\sprintf(
                'Не удалось разрешить параметр $%s типа %s для компиляции.',
                $parameter->getName(),
                $typeName,
            ));
        }

        return $this->serviceGetExpression($typeName);
    }

    /**
     * Разрешает union-тип параметра через {@see ParameterTypeResolver} и выбирает зарегистрированный member.
     *
     * @param Container $container Контейнер-источник
     * @param ReflectionParameter $parameter Параметр конструктора
     * @param ReflectionUnionType $type Union-тип параметра
     *
     * @throws ContainerCompileException Если runtime-разрешение union не удалось или нет default
     *
     * @return string PHP-выражение аргумента
     */
    private function resolveUnionExpression(
        Container $container,
        ReflectionParameter $parameter,
        ReflectionUnionType $type,
    ): string {
        $resolver = new ParameterTypeResolver($container);

        try {
            $resolver->resolve($parameter);
        } catch (Throwable $throwable) {
            throw new ContainerCompileException(\sprintf(
                'Не удалось разрешить union-параметр $%s: %s',
                $parameter->getName(),
                $throwable->getMessage(),
            ), 0, $throwable);
        }

        /** @psalm-suppress DocblockTypeContradiction */
        foreach ($type->getTypes() as $unionMember) {
            if (!$unionMember instanceof ReflectionNamedType || $unionMember->isBuiltin()) {
                continue;
            }

            if ($container->has($unionMember->getName())) {
                return $this->serviceGetExpression($unionMember->getName());
            }
        }

        return $this->defaultValueExpression($parameter);
    }

    /**
     * Возвращает `var_export` значения по умолчанию параметра.
     *
     * @param ReflectionParameter $parameter Параметр конструктора
     *
     * @throws ContainerCompileException Если default value недоступен
     *
     * @return string PHP-литерал из {@see var_export()}
     */
    private function defaultValueExpression(ReflectionParameter $parameter): string
    {
        if ($parameter->isDefaultValueAvailable()) {
            return var_export($parameter->getDefaultValue(), true);
        }

        throw new ContainerCompileException(\sprintf(
            'Не удалось разрешить параметр $%s для компиляции.',
            $parameter->getName(),
        ));
    }

    /**
     * Формирует выражение получения сервиса из compiled-контейнера.
     *
     * @param string $serviceId Идентификатор сервиса
     *
     * @return string Выражение вида `$this->get('serviceId')`
     */
    private function serviceGetExpression(string $serviceId): string
    {
        return '$this->get(' . var_export($serviceId, true) . ')';
    }
}
