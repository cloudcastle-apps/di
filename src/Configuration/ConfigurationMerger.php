<?php

declare(strict_types=1);

namespace CloudCastle\DI\Configuration;

/**
 * Сливает несколько слоёв конфигурации с учётом приоритетов.
 *
 * По умолчанию побеждает последний источник (больший порядковый индекс).
 * Явный `priority` у параметра или файла переопределяет порядок загрузки.
 *
 * @psalm-suppress MixedAssignment
 */
final class ConfigurationMerger
{
    /**
     * Имена секций конфигурации, которые объединяются при слиянии слоёв.
     *
     * @var list<string>
     */
    private const MERGE_SECTIONS = [
        'services',
        'aliases',
        'bind',
        'contextual',
        'autowire',
        'register_attributes',
        'tags',
        'scan',
        'autowiring',
    ];

    /**
     * Сливает несколько слоёв конфигурации с учётом приоритетов.
     *
     * @param list<ConfigurationLayer> $layers Слои в порядке загрузки
     *
     * @return array<string, mixed> Объединённая конфигурация по секциям
     */
    public function merge(array $layers): array
    {
        /** @var array<string, mixed> $merged */
        $merged = [];

        foreach (self::MERGE_SECTIONS as $section) {
            if ($section === 'contextual') {
                $merged[$section] = $this->mergeContextualSection($layers);

                continue;
            }

            $merged[$section] = $this->mergeSection($layers, $section);
        }

        return $merged;
    }

    /**
     * Сливает секцию contextual с учётом приоритетов по парам consumerClass/need.
     *
     * @param list<ConfigurationLayer> $layers Слои конфигурации в порядке загрузки
     *
     * @return array<string, array<string, string>> Карта consumerClass → need → id сервиса
     */
    private function mergeContextualSection(array $layers): array
    {
        $winners = $this->collectContextualWinners($layers);

        return $this->buildContextualResult($winners);
    }

    /**
     * Собирает победителей для каждой пары consumerClass/need по приоритету и порядку слоя.
     *
     * @param list<ConfigurationLayer> $layers Слои конфигурации в порядке загрузки
     *
     * @return array<int|string, array{effectivePriority: int, order: int, value: mixed}>
     */
    private function collectContextualWinners(array $layers): array
    {
        /** @var array<string, array{effectivePriority: int, order: int, value: string}> $winners */
        $winners = [];

        foreach ($layers as $layer) {
            $sectionData = $layer->config['contextual'] ?? null;

            if (!\is_array($sectionData)) {
                continue;
            }

            $layerDefaultPriority = $this->resolveLayerDefaultPriority($layer);

            foreach ($sectionData as $consumerClass => $needsMap) {
                if (!\is_string($consumerClass) || !\is_array($needsMap)) {
                    continue;
                }

                foreach ($needsMap as $need => $give) {
                    if (!\is_string($need)) {
                        continue;
                    }

                    [$giveValue, $entryPriority] = $this->unwrapEntry($give);

                    if (!\is_string($giveValue)) {
                        continue;
                    }

                    $key = $consumerClass . '::' . $need;
                    $effectivePriority = $entryPriority ?? $layerDefaultPriority;

                    $this->considerWinner($winners, $key, $effectivePriority, $layer->order, $giveValue);
                }
            }
        }

        return $winners;
    }

    /**
     * Преобразует карту победителей в итоговую структуру contextual-секции.
     *
     * @param array<int|string, array{
     *     effectivePriority: int,
     *     order: int,
     *     value: mixed
     * }> $winners Победители с ключом `consumerClass::need`
     *
     * @return array<string, array<string, string>> Карта consumerClass → need → id сервиса
     */
    private function buildContextualResult(array $winners): array
    {
        /** @var array<string, array<string, string>> $result */
        $result = [];

        foreach ($winners as $key => $winner) {
            if (!\is_string($key)) {
                continue;
            }

            $separator = strpos($key, '::');

            if ($separator === false) {
                continue;
            }

            $giveId = $winner['value'];

            if (!\is_string($giveId)) {
                continue;
            }

            $consumerClass = substr($key, 0, $separator);
            $need = substr($key, $separator + 2);
            $result[$consumerClass][$need] = $giveId;
        }

        return $result;
    }

    /**
     * Сливает одну именованную секцию конфигурации из всех слоёв.
     *
     * @param list<ConfigurationLayer> $layers Слои конфигурации в порядке загрузки
     * @param string $section Имя секции из {@see MERGE_SECTIONS}
     *
     * @return array<mixed> Ассоциативная или списковая секция в зависимости от типа
     */
    private function mergeSection(array $layers, string $section): array
    {
        /** @var array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners */
        $winners = [];

        foreach ($layers as $layer) {
            $sectionData = $layer->config[$section] ?? null;

            if (!\is_array($sectionData)) {
                continue;
            }

            $layerDefaultPriority = $this->resolveLayerDefaultPriority($layer);

            if ($this->isListSection($section)) {
                $this->mergeListSection($winners, $section, $sectionData, $layer->order, $layerDefaultPriority);

                continue;
            }

            $this->mergeMapSection($winners, $sectionData, $layer->order, $layerDefaultPriority);
        }

        if ($this->isListSection($section)) {
            return $this->buildListResult($winners);
        }

        return $this->buildMapResult($winners);
    }

    /**
     * Объединяет элементы списковой секции (autowire, register_attributes, scan).
     *
     * @param array<string|int, array{
     *     effectivePriority: int,
     *     order: int,
     *     value: mixed
     * }> $winners Накопленные победители по ключу элемента (по ссылке)
     * @param string $section Имя списковой секции
     * @param array<mixed> $sectionData Данные секции из текущего слоя
     * @param int $order Порядковый индекс слоя
     * @param int $layerDefaultPriority Приоритет слоя по умолчанию
     */
    private function mergeListSection(
        array &$winners,
        string $section,
        array $sectionData,
        int $order,
        int $layerDefaultPriority,
    ): void {
        foreach ($sectionData as $index => $entry) {
            [$value, $entryPriority] = $this->unwrapEntry($entry);
            $key = $this->resolveListEntryKey($section, $value, $index);
            $effectivePriority = $entryPriority ?? $layerDefaultPriority;

            $this->considerWinner($winners, $key, $effectivePriority, $order, $value);
        }
    }

    /**
     * Формирует ключ элемента списковой секции для разрешения конфликтов.
     *
     * @param string $section Имя секции
     * @param mixed $value Значение элемента
     * @param int|string $index Индекс элемента в массиве слоя
     *
     * @return string Уникальный ключ победителя (например, `scan:/path` или `autowire:FQCN`)
     */
    private function resolveListEntryKey(string $section, mixed $value, int|string $index): string
    {
        if ($section === 'scan' && \is_array($value) && isset($value['directory']) && \is_string($value['directory'])) {
            return 'scan:' . $value['directory'];
        }

        if (\is_string($value)) {
            return $section . ':' . $value;
        }

        return $section . ':' . $index;
    }

    /**
     * Объединяет элементы ассоциативной секции (services, aliases, bind, tags и т.д.).
     *
     * @param array<string|int, array{
     *     effectivePriority: int,
     *     order: int,
     *     value: mixed
     * }> $winners Накопленные победители по ключу (по ссылке)
     * @param array<mixed> $sectionData Данные секции из текущего слоя
     * @param int $order Порядковый индекс слоя
     * @param int $layerDefaultPriority Приоритет слоя по умолчанию
     */
    private function mergeMapSection(array &$winners, array $sectionData, int $order, int $layerDefaultPriority): void
    {
        foreach ($sectionData as $key => $entry) {
            if (!\is_string($key)) {
                continue;
            }

            [$value, $entryPriority] = $this->unwrapEntry($entry);
            $effectivePriority = $entryPriority ?? $layerDefaultPriority;

            $this->considerWinner($winners, $key, $effectivePriority, $order, $value);
        }
    }

    /**
     * Сравнивает кандидата с текущим победителем и обновляет карту при более высоком приоритете.
     *
     * При равном приоритете побеждает слой с большим порядковым индексом.
     *
     * @param array<string|int, array{
     *     effectivePriority: int,
     *     order: int,
     *     value: mixed
     * }> $winners Карта победителей (по ссылке)
     * @param string $key Ключ элемента в секции
     * @param int $effectivePriority Эффективный приоритет записи
     * @param int $order Порядковый индекс слоя
     * @param mixed $value Значение записи
     */
    private function considerWinner(
        array &$winners,
        string $key,
        int $effectivePriority,
        int $order,
        mixed $value,
    ): void {
        $candidate = ['effectivePriority' => $effectivePriority, 'order' => $order, 'value' => $value];

        if (!isset($winners[$key])) {
            $winners[$key] = $candidate;

            return;
        }

        $current = $winners[$key];

        if ($candidate['effectivePriority'] > $current['effectivePriority']) {
            $winners[$key] = $candidate;

            return;
        }

        if (
            $candidate['effectivePriority'] === $current['effectivePriority']
            && $candidate['order'] > $current['order']
        ) {
            $winners[$key] = $candidate;
        }
    }

    /**
     * Собирает ассоциативную секцию из значений победителей.
     *
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     *
     * @return array<mixed> Итоговая ассоциативная секция
     */
    private function buildMapResult(array $winners): array
    {
        $result = [];

        foreach ($winners as $key => $winner) {
            $result[$key] = $winner['value'];
        }

        return $result;
    }

    /**
     * Собирает списковую секцию, упорядоченную по порядку загрузки слоёв.
     *
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     *
     * @return list<mixed> Итоговый список значений
     */
    private function buildListResult(array $winners): array
    {
        uasort(
            $winners,
            static fn (array $left, array $right): int => $left['order'] <=> $right['order'],
        );

        $result = [];

        foreach ($winners as $winner) {
            $result[] = $winner['value'];
        }

        return $result;
    }

    /**
     * Определяет приоритет слоя: явный в конфиге, приоритет файла или порядковый индекс.
     *
     * @param ConfigurationLayer $layer Слой конфигурации
     *
     * @return int Эффективный приоритет по умолчанию для записей без собственного priority
     */
    private function resolveLayerDefaultPriority(ConfigurationLayer $layer): int
    {
        $configPriority = $layer->config['priority'] ?? null;

        if (\is_int($configPriority)) {
            return $configPriority;
        }

        if ($layer->filePriority !== null) {
            return $layer->filePriority;
        }

        return $layer->order;
    }

    /**
     * Извлекает значение и необязательный приоритет из записи конфигурации.
     *
     * Поддерживает форматы: скаляр, `['value' => ..., 'priority' => int]` и inline `priority`.
     *
     * @param mixed $entry Элемент секции из слоя
     *
     * @return array{0: mixed, 1: int|null} Кортеж [значение, приоритет или null]
     */
    private function unwrapEntry(mixed $entry): array
    {
        if (!\is_array($entry) || !\array_key_exists('value', $entry)) {
            $priority = $this->extractInlinePriority($entry);

            return [$this->stripPriority($entry), $priority];
        }

        $priority = $entry['priority'] ?? null;

        return [$entry['value'], \is_int($priority) ? $priority : null];
    }

    /**
     * Удаляет ключ `priority` из массива записи, не меняя остальное значение.
     *
     * @param mixed $entry Элемент секции
     *
     * @return mixed Значение без поля priority
     */
    private function stripPriority(mixed $entry): mixed
    {
        if (!\is_array($entry) || !isset($entry['priority'])) {
            return $entry;
        }

        unset($entry['priority']);

        return $entry;
    }

    /**
     * Извлекает inline-приоритет из массива записи, если он задан целым числом.
     *
     * @param mixed $entry Элемент секции
     *
     * @return int|null Приоритет или null, если не указан
     */
    private function extractInlinePriority(mixed $entry): ?int
    {
        if (!\is_array($entry)) {
            return null;
        }

        $priority = $entry['priority'] ?? null;

        return \is_int($priority) ? $priority : null;
    }

    /**
     * Проверяет, хранится ли секция в виде списка, а не ассоциативного массива.
     *
     * @param string $section Имя секции
     *
     * @return bool true для autowire, register_attributes и scan
     */
    private function isListSection(string $section): bool
    {
        return \in_array($section, ['autowire', 'register_attributes', 'scan'], true);
    }
}
