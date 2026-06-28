<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

/**
 * Вторая ступень fluent API: {@see needs()}.
 *
 * @see ContextualBindingConfiguratorInterface
 */
interface ContextualBindingNeedsInterface
{
    /**
     * Тип или id зависимости, которую нужно переопределить в контексте when().
     *
     * @param string $need FQCN интерфейса/класса или id сервиса
     */
    public function needs(string $need): ContextualBindingGiveInterface;
}
