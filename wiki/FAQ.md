<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# ❓ FAQ

> [← Главная](Home) · [📊 Сравнение — полная таблица](Comparison) · [Quick start](Quick-start)

## Чем CloudCastle DI отличается от PHP-DI / Symfony / Pimple / Laravel / Nette?

**Сравнение с 5 аналогами** и колонкой победителей — **[Comparison](Comparison)**.

Кратко: компактный PSR-11 контейнер (`psr/container`), autowiring, `scan()`, теги, **compiled container** (v1.9), **contextual binding** runtime/config/compiled (v1.10–1.13), opt-in **profiler / memory pool / smart cache** (v1.15–1.17), **lazy ghost** (v1.18). Полная таблица — [Comparison](Comparison).

## Есть autowiring?

**Да.** Включите глобально:

```php
$container->enableAutowiring();
$service = $container->get(App\Service\MyService::class);
```

Или точечно: `$container->autowire(App\Service\MyService::class)`.

Подробнее — [Autowiring](Autowiring).

## Можно ли конфигурировать контейнер из YAML/JSON?

**Да** (v1.5.0) — через **`ContainerConfigurator`**: PHP (по умолчанию), JSON, YAML (`ext-yaml`), XML. Несколько файлов с приоритетами. Конфигурация **необязательна**. См. [Configuration](Configuration).

## Можно ли использовать свои PHP attributes для inject?

**Да** — реализуйте `ServiceIdAttribute` и вызовите `registerAttribute()`. См. [Autowiring](Autowiring).

## Можно использовать id = FQCN класса?

**Да.** При autowiring id — полное имя класса. Можно также использовать произвольные строки (`'logger'`, `'mailer'`) через `set()`.

## Как зарегистрировать все классы в каталоге?

```php
$container->scan(__DIR__ . '/Services', 'App\\Services\\');
```

См. [Сканирование классов](Class-scanning).

## Есть глобальный контейнер?

**Да** — `ContainerRegistry::set()` / `get()`. Рекомендуется инициализировать в bootstrap; в тестах вызывайте `ContainerRegistry::reset()`. См. [Глобальный реестр](Global-registry).

## Поддерживаются tagged services и декораторы?

**Да:** `tag()` / `getTagged()` и `decorate()`. С v1.3.0 также `getTaggedIds()`, `getTaggedIterator()`, `getTaggedLocator()`. См. [Теги и декораторы](Tags-and-decorators).

## Есть call(), bind() и afterResolving?

**Да** (с v1.3.0):

- **`call($callable, $parameters?)`** — autowiring параметров при вызове функции/метода (`CallableInvoker`)
- **`bind($abstract, $concrete)`** — `autowire` + `alias` для класса или только `alias` для id
- **`addDefinitions(array)`** — массовый `set()`
- **`afterResolving($id, $callback)`** — callback после нового создания (не из singleton-кэша)

См. [call(), bind(), afterResolving](Call-bind-callbacks).

## Есть прототипы, alias и lazy?

**Да** (с v1.2.0):

- **`make($id)`** — новый экземпляр без singleton-кэша
- **`alias($alias, $targetId)`** — альтернативный id
- **`lazy($serviceId)`** — отложенное создание через `LazyService::getValue()`

**Lazy ghost** (v1.18.0, [#34](https://github.com/cloudcastle-apps/di/issues/34)):

- **`lazyGhost($interface, $serviceId)`** — proxy для interface; класс реализации не загружается до первого вызова метода
- Требует opt-in `symfony/var-exporter` (`composer require symfony/var-exporter`)

См. [Прототипы, alias и lazy](Prototypes-alias-lazy).

## Есть profiler, memory pool и smart cache?

**Да**, opt-in (не влияют на prod по умолчанию):

| Фича | Версия | API |
|------|--------|-----|
| Performance Profiler | v1.15 | `enableProfiling()`, `profileReport()` |
| Memory Pool | v1.16 | `enablePooling()`, `releaseToPool()`, `poolStats()` |
| Smart Caching | v1.17 | `cacheFor()`, `cacheTagFor()`, `forget()`, `cacheStats()` |

См. [Performance and load](Performance-and-load).

## Есть contextual binding?

**Да**, полностью (v1.10–1.13, [#25](https://github.com/cloudcastle-apps/di/issues/25) завершён):

| Слой | Версия | API |
|------|--------|-----|
| Контракты | v1.10.0 | `ContextualBinding`, `ContextualBindingRegistryInterface`, fluent `when/needs/give` |
| Runtime | v1.11.0 | `Container::when()->needs()->give()` |
| Config | v1.12.0 | секция `contextual` в PHP/JSON/YAML/XML |
| Compiled | v1.13.0 | `contextualGive()` в сгенерированном классе |

Руководство — [Contextual binding](Contextual-binding).

## Есть compiled container?

**Да** (v1.9.0). `ContainerCompiler` генерирует PHP-класс из замороженного контейнера — без reflection на hot path `get()`. Поддерживаются `set`, `autowire`, `alias`, tags; **не** фабрики, property/method injection, decorators. См. [Compiled container](Compiled-container).

## Поддерживаются PHP attributes?

**Да.** `CloudCastle\DI\Attribute\Inject` и `Autowire` на **параметрах конструктора**, **свойствах**, **методах** и параметрах методов:

```php
#[Inject('app.clock')]
private ClockInterface $clock;

#[Inject]
protected function setClock(ClockInterface $clock): void {}
```

Подробнее — [Autowiring](Autowiring).

## Autowiring свойств и методов?

**Да.**

- **`enablePropertyAutowiring()`** — typed properties без attribute (после конструктора)
- **`enableMethodAutowiring()`** — public/protected setter и inject-методы с параметрами
- Attributes `#[Inject]` / `#[Autowire]` на свойствах и методах работают **без** этих флагов

Порядок: конструктор → свойства → методы. См. [Autowiring](Autowiring).

## Поддерживаются intersection-типы?

**Да.** Параметры вида `Iterator&Countable` разрешаются, если экземпляр из контейнера удовлетворяет всем типам intersection.

## Autowiring по имени параметра?

**Да**, опционально: `enableParameterNameAutowiring()`. Параметр `$logger` получит сервис с id `'logger'`, если он зарегистрирован. По умолчанию выключено.

## Обнаруживаются циклические зависимости?

При **autowiring** — да (`ContainerException`). В **фабриках** `set()` — нет, возможен бесконечный цикл.

## Потокобезопасность?

`Container` не синхронизирован. В PHP-FPM один контейнер на запрос — типичный сценарий. В long-running workers при параллельном доступе нужна внешняя синхронизация или контейнер на worker.

## Как обновить Packagist после форка?

В upstream настроен workflow **Packagist** с secret `PACKAGIST_TOKEN`. Для форка настройте свой токен в Settings → Secrets.

## Где API-документация?

```bash
composer docs
```

Результат в `docs/` (генерируется локально). Руководство — `doc/guide/` и эта Wiki. **Схемы архитектуры** — [Архитектура](Architecture).

## Куда задавать вопросы?

- [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a)
- [Issues](https://github.com/cloudcastle-apps/di/issues) — баги
- [SECURITY](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md) — уязвимости
