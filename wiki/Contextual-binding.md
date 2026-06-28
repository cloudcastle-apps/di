<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🔗 Contextual binding (#25)

> [← Главная](Home) · [API](API-reference#v210-contextual-binding-контракты) · [Compiled container](Compiled-container)

**Contextual binding** — привязка зависимости к **контексту** класса-потребителя: `when(A)->needs(B)->give(C)`.

Аналог Laravel `when()->needs()->give()` и PHP-DI contextual definitions.

---

## Статус (v1.12.0)

| Этап | Статус | MR / релиз |
|------|--------|------------|
| **1. Контракты** | ✅ v1.10.0 | [#61](https://github.com/cloudcastle-apps/di/pull/61) |
| **2. Runtime** | ✅ v1.11.0 | `Container::when()`, registry, autowirer |
| **3. Config** | ✅ v1.12.0 | секция `contextual` в PHP/JSON/YAML/XML |
| **4. Compiler** | 🔜 | compiled container parity |

Полный runtime и **declarative config** доступны (v1.11–1.12). Compiled (#25 ч.4) — в roadmap.

### Пример (runtime)

```php
$container->enableAutowiring();
$container->bind(LoggerInterface::class, 'default.logger');
$container->set('memory.logger', new MemoryLogger());
$container->when(ReportService::class)
    ->needs(LoggerInterface::class)
    ->give('memory.logger');

$report = $container->get(ReportService::class); // MemoryLogger
$other = $container->get(AuditService::class);   // default binding
```

### Пример (config, v1.12.0)

```yaml
contextual:
  App\ReportService:
    Psr\Log\LoggerInterface: memory.logger
```

```php
(new ContainerConfigurator())->configure($container, [__DIR__ . '/container.yaml']);
```

---

## Контракты (v1.10.0) и runtime (v1.11.0)

### `ContextualBinding`

Value object: `consumerClass` (when), `need` (needs), `give` (id или FQCN).

### Fluent API (интерфейсы)

```php
$container->when(ReportController::class)
    ->needs(LoggerInterface::class)
    ->give(FileLogger::class);
```

| Интерфейс | Метод |
|-----------|--------|
| `ContextualBindingConfiguratorInterface` | `when(string $consumerClass)` |
| `ContextualBindingNeedsInterface` | `needs(string $need)` |
| `ContextualBindingGiveInterface` | `give(string $serviceId)` |

### `ContextualBindingRegistryInterface`

| Метод | Описание |
|-------|----------|
| `register(ContextualBinding $binding)` | Добавить правило |
| `bindingsFor(string $consumerClass)` | Список правил для класса |
| `resolve(string $consumerClass, string $need)` | id для give или `null` |

---

## Roadmap

- Issue [#25](https://github.com/cloudcastle-apps/di/issues/25) — milestone **v2.0** (runtime + config + compiler)
- Декомпозиция: [комментарий в #25](https://github.com/cloudcastle-apps/di/issues/25#issuecomment-4825334534)

---

## См. также

- [Comparison](Comparison) — contextual vs PHP-DI / Laravel
- [Upgrading](Upgrading) — 1.9.0 → 1.10.0
- [FAQ](FAQ)
