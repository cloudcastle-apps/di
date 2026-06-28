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
     * @param list<ConfigurationLayer> $layers
     *
     * @return array<string, mixed>
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
     * @param list<ConfigurationLayer> $layers
     *
     * @return array<string, array<string, string>>
     */
    private function mergeContextualSection(array $layers): array
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

        /** @var array<string, array<string, string>> $result */
        $result = [];

        foreach ($winners as $key => $winner) {
            $separator = strpos($key, '::');

            if ($separator === false) {
                continue;
            }

            $consumerClass = substr($key, 0, $separator);
            $need = substr($key, $separator + 2);
            $result[$consumerClass][$need] = $winner['value'];
        }

        return $result;
    }

    /**
     * @param list<ConfigurationLayer> $layers
     *
     * @return array<mixed>
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
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     * @param array<mixed> $sectionData
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
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     * @param array<mixed> $sectionData
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
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
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
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     *
     * @return array<mixed>
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
     * @param array<string|int, array{effectivePriority: int, order: int, value: mixed}> $winners
     *
     * @return list<mixed>
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
     * @return array{0: mixed, 1: int|null}
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

    private function stripPriority(mixed $entry): mixed
    {
        if (!\is_array($entry) || !isset($entry['priority'])) {
            return $entry;
        }

        unset($entry['priority']);

        return $entry;
    }

    private function extractInlinePriority(mixed $entry): ?int
    {
        if (!\is_array($entry)) {
            return null;
        }

        $priority = $entry['priority'] ?? null;

        return \is_int($priority) ? $priority : null;
    }

    private function isListSection(string $section): bool
    {
        return \in_array($section, ['autowire', 'register_attributes', 'scan'], true);
    }
}
