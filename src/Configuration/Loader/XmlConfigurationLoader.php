<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration\Loader;

use Closure;
use CloudCastle\DI\Contract\ConfigurationLoaderInterface;
use CloudCastle\DI\Exception\ContainerException;
use SimpleXMLElement;

/**
 * Загрузчик XML-конфигурации контейнера.
 *
 * @psalm-suppress MixedAssignment Динамические секции XML-конфигурации
 */
final class XmlConfigurationLoader implements ConfigurationLoaderInterface
{
    /**
     * {@inheritDoc}
     */
    public function supports(string $path): bool
    {
        return str_ends_with(strtolower($path), '.xml');
    }

    /**
     * {@inheritDoc}
     */
    public function load(string $path): array
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new ContainerException(\sprintf('Файл конфигурации "%s" не найден или недоступен.', $path));
        }

        $previous = libxml_use_internal_errors(true);

        try {
            $xml = simplexml_load_file($path);

            if ($xml === false) {
                throw new ContainerException(\sprintf('Ошибка разбора XML-конфигурации "%s".', $path));
            }

            return $this->parseRoot($xml);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Разбирает корневой элемент `<container>` в массив секций конфигурации.
     *
     * @param SimpleXMLElement $xml Корневой XML-элемент
     *
     * @return array<string, mixed> Секции конфигурации и необязательный ключ `priority`
     */
    private function parseRoot(SimpleXMLElement $xml): array
    {
        $config = [];

        foreach ($this->sectionParsers() as $section => $parser) {
            if (!isset($xml->{$section})) {
                continue;
            }

            $node = $xml->{$section};

            /** @var SimpleXMLElement $node */
            $config[$section] = $parser($node);
        }

        $priority = $this->readOptionalIntAttribute($xml, 'priority');

        if ($priority !== null) {
            $config['priority'] = $priority;
        }

        return $config;
    }

    /**
     * Возвращает карту имён секций и функций их разбора.
     *
     * @return array<string, Closure(SimpleXMLElement): mixed>
     */
    private function sectionParsers(): array
    {
        return [
            'services' => $this->parseServices(...),
            'aliases' => $this->parseAliases(...),
            'bind' => $this->parseBind(...),
            'contextual' => $this->parseContextual(...),
            'autowire' => $this->parseAutowireList(...),
            'tags' => $this->parseTags(...),
            'scan' => $this->parseScan(...),
            'register_attributes' => $this->parseStringList(...),
            'autowiring' => $this->parseAutowiringFlags(...),
        ];
    }

    /**
     * Разбирает секцию `<services>` в карту id → определение сервиса.
     *
     * @param SimpleXMLElement $services Узел секции services
     *
     * @return array<string, mixed>
     */
    private function parseServices(SimpleXMLElement $services): array
    {
        $result = [];

        foreach ($services->service as $service) {
            $id = $this->readRequiredAttribute($service, 'id');
            $result[$id] = $this->parseServiceValue($service);
        }

        return $result;
    }

    /**
     * Разбирает одно определение `<service>`: класс, ленивость, приоритет или текстовое значение.
     *
     * @param SimpleXMLElement $service Узел service
     *
     * @return array<string, mixed>|string Определение сервиса или id другого сервиса
     */
    private function parseServiceValue(SimpleXMLElement $service): array|string
    {
        $priority = $this->readOptionalIntAttribute($service, 'priority');
        $class = $this->readOptionalAttribute($service, 'class');
        $lazy = $this->readOptionalBoolAttribute($service, 'lazy');
        $text = trim((string) $service);

        if ($class !== null) {
            $value = ['class' => $class];

            if ($lazy !== null) {
                $value['lazy'] = $lazy;
            }

            if ($priority !== null) {
                $value['priority'] = $priority;
            }

            return $value;
        }

        if ($priority !== null) {
            return ['value' => $text, 'priority' => $priority];
        }

        return $text;
    }

    /**
     * Разбирает секцию `<aliases>` в карту псевдоним → целевой id.
     *
     * @param SimpleXMLElement $aliases Узел секции aliases
     *
     * @return array<string, string|array{value: string, priority?: int}>
     */
    private function parseAliases(SimpleXMLElement $aliases): array
    {
        $result = [];

        foreach ($aliases->alias as $alias) {
            $name = $this->readRequiredAttribute($alias, 'name');
            $target = $this->readRequiredAttribute($alias, 'target');
            $result[$name] = $this->wrapWithPriority($target, $alias);
        }

        return $result;
    }

    /**
     * Разбирает секцию `<bind>` в карту abstract → concrete.
     *
     * @param SimpleXMLElement $bind Узел секции bind
     *
     * @return array<string, string|array{value: string, priority?: int}>
     */
    private function parseBind(SimpleXMLElement $bind): array
    {
        $result = [];

        foreach ($bind->binding as $binding) {
            $abstract = $this->readRequiredAttribute($binding, 'abstract');
            $concrete = $this->readRequiredAttribute($binding, 'concrete');
            $result[$abstract] = $this->wrapWithPriority($concrete, $binding);
        }

        return $result;
    }

    /**
     * Разбирает секцию `<contextual>` в карту when → needs → give.
     *
     * @param SimpleXMLElement $contextual Узел секции contextual
     *
     * @return array<string, array<string, string|array{value: string, priority?: int}>>
     */
    private function parseContextual(SimpleXMLElement $contextual): array
    {
        $result = [];

        foreach ($contextual->when as $when) {
            $consumerClass = $this->readRequiredAttribute($when, 'class');
            $needs = [];

            foreach ($when->need as $need) {
                $type = $this->readRequiredAttribute($need, 'type');
                $give = $this->readRequiredAttribute($need, 'give');
                $needs[$type] = $this->wrapWithPriority($give, $need);
            }

            $result[$consumerClass] = $needs;
        }

        return $result;
    }

    /**
     * Разбирает список классов для autowire из секции `<autowire>`.
     *
     * @param SimpleXMLElement $autowire Узел секции autowire
     *
     * @return list<string|array{value: string, priority?: int}>
     */
    private function parseAutowireList(SimpleXMLElement $autowire): array
    {
        $result = [];

        foreach ($autowire->class as $class) {
            $name = $this->readOptionalAttribute($class, 'name');
            $value = $name ?? trim((string) $class);

            if ($value === '') {
                continue;
            }

            $result[] = $this->wrapListEntry($value, $class);
        }

        return $result;
    }

    /**
     * Разбирает секцию `<tags>` в карту имени тега → список id сервисов.
     *
     * @param SimpleXMLElement $tags Узел секции tags
     *
     * @return array<string, list<string>>
     */
    private function parseTags(SimpleXMLElement $tags): array
    {
        $result = [];

        foreach ($tags->tag as $tag) {
            $name = $this->readRequiredAttribute($tag, 'name');
            $ids = [];

            foreach ($tag->id as $id) {
                $ids[] = (string) $id;
            }

            $result[$name] = $ids;
        }

        return $result;
    }

    /**
     * Разбирает секцию `<scan>` в список каталогов для автоматического сканирования.
     *
     * @param SimpleXMLElement $scan Узел секции scan
     *
     * @return list<array{directory: string, namespace?: string}>
     */
    private function parseScan(SimpleXMLElement $scan): array
    {
        $result = [];

        foreach ($scan->directory as $directory) {
            $path = $this->readRequiredAttribute($directory, 'path');
            $entry = ['directory' => $path];
            $namespace = $this->readOptionalAttribute($directory, 'namespace');

            if ($namespace !== null) {
                $entry['namespace'] = $namespace;
            }

            $result[] = $entry;
        }

        return $result;
    }

    /**
     * Разбирает список строковых значений из дочерних `<attribute class="...">`.
     *
     * @param SimpleXMLElement $parent Родительский узел секции
     *
     * @return list<string|array{value: string, priority?: int}>
     */
    private function parseStringList(SimpleXMLElement $parent): array
    {
        $result = [];

        foreach ($parent->attribute as $attribute) {
            $result[] = $this->wrapListEntry($this->readRequiredAttribute($attribute, 'class'), $attribute);
        }

        return $result;
    }

    /**
     * Разбирает флаги autowiring из секции `<autowiring>`.
     *
     * @param SimpleXMLElement $autowiring Узел секции autowiring
     *
     * @return array<string, bool> Включённые флаги (только со значением true)
     */
    private function parseAutowiringFlags(SimpleXMLElement $autowiring): array
    {
        $flags = ['enabled', 'parameter_name', 'property', 'method'];
        $result = [];

        foreach ($flags as $flag) {
            $value = $this->readOptionalBoolAttribute($autowiring, $flag);

            if ($value === true) {
                $result[$flag] = true;
            }
        }

        return $result;
    }

    /**
     * Оборачивает строковое значение в структуру с приоритетом, если атрибут `priority` задан.
     *
     * @param string $value Строковое значение записи
     * @param SimpleXMLElement $element XML-элемент с необязательным атрибутом priority
     *
     * @return string|array{value: string, priority?: int}
     */
    private function wrapWithPriority(string $value, SimpleXMLElement $element): array|string
    {
        $priority = $this->readOptionalIntAttribute($element, 'priority');

        if ($priority === null) {
            return $value;
        }

        return ['value' => $value, 'priority' => $priority];
    }

    /**
     * Оборачивает элемент списковой секции с учётом атрибута `priority`.
     *
     * @param string $value Строковое значение элемента
     * @param SimpleXMLElement $element XML-элемент списка
     *
     * @return string|array{value: string, priority?: int}
     */
    private function wrapListEntry(string $value, SimpleXMLElement $element): array|string
    {
        return $this->wrapWithPriority($value, $element);
    }

    /**
     * Читает обязательный строковый атрибут XML-элемента.
     *
     * @param SimpleXMLElement $element XML-элемент
     * @param string $name Имя атрибута
     *
     * @throws ContainerException Если атрибут отсутствует
     *
     * @return string Значение атрибута
     */
    private function readRequiredAttribute(SimpleXMLElement $element, string $name): string
    {
        $attributes = $element->attributes();

        if ($attributes === null || !isset($attributes[$name])) {
            throw new ContainerException(\sprintf('XML: обязательный атрибут "%s" отсутствует.', $name));
        }

        return (string) $attributes[$name];
    }

    /**
     * Читает необязательный строковый атрибут XML-элемента.
     *
     * @param SimpleXMLElement $element XML-элемент
     * @param string $name Имя атрибута
     *
     * @return string|null Значение атрибута или null, если не задан
     */
    private function readOptionalAttribute(SimpleXMLElement $element, string $name): ?string
    {
        $attributes = $element->attributes();

        if ($attributes === null || !isset($attributes[$name])) {
            return null;
        }

        return (string) $attributes[$name];
    }

    /**
     * Читает необязательный целочисленный атрибут XML-элемента.
     *
     * @param SimpleXMLElement $element XML-элемент
     * @param string $name Имя атрибута
     *
     * @throws ContainerException Если атрибут задан, но не является числом
     *
     * @return int|null Значение атрибута или null, если не задан
     */
    private function readOptionalIntAttribute(SimpleXMLElement $element, string $name): ?int
    {
        $value = $this->readOptionalAttribute($element, $name);

        if ($value === null) {
            return null;
        }

        if (!is_numeric($value)) {
            throw new ContainerException(\sprintf('XML: атрибут "%s" должен быть числом.', $name));
        }

        return (int) $value;
    }

    /**
     * Читает необязательный логический атрибут XML-элемента.
     *
     * Допустимые истинные значения: `1`, `true`, `yes`, `on` (без учёта регистра).
     *
     * @param SimpleXMLElement $element XML-элемент
     * @param string $name Имя атрибута
     *
     * @return bool|null true, если атрибут задан истинным; false или null — иначе
     */
    private function readOptionalBoolAttribute(SimpleXMLElement $element, string $name): ?bool
    {
        $value = $this->readOptionalAttribute($element, $name);

        if ($value === null) {
            return null;
        }

        return \in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}
