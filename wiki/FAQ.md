# FAQ

## Чем CloudCastle DI отличается от PHP-DI / Symfony / Pimple?

CloudCastle DI — **компактный** PSR-11 контейнер с явным `set()`, singleton-фабриками, прототипами (`make()`), alias, lazy-сервисами, reflection autowiring (конструктор, **свойства**, **методы**; типы, union, intersection, PHP attributes, autowiring по имени), scan каталогов, tagged services и декораторами. Одна runtime-зависимость (`psr/container`). Без YAML и compiled container.

## Есть autowiring?

**Да.** Включите глобально:

```php
$container->enableAutowiring();
$service = $container->get(App\Service\MyService::class);
```

Или точечно: `$container->autowire(App\Service\MyService::class)`.

Подробнее — [Autowiring](Autowiring).

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

**Да:** `tag()` / `getTagged()` и `decorate()`. См. [Теги и декораторы](Tags-and-decorators).

## Есть прототипы, alias и lazy?

**Да** (с v1.2.0):

- **`make($id)`** — новый экземпляр без singleton-кэша
- **`alias($alias, $targetId)`** — альтернативный id
- **`lazy($serviceId)`** — отложенное создание через `LazyService::getValue()`

См. [Прототипы, alias и lazy](Prototypes-alias-lazy).

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
