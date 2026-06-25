# Autowiring

Autowiring создаёт экземпляры классов через reflection. Идентификатор сервиса при autowiring — **полное имя класса (FQCN)**.

## Порядок внедрения

Класс `CloudCastle\DI\Autowirer` выполняет шаги **строго в таком порядке**:

1. **Конструктор** — параметры по типам, attributes, имени (если включено)
2. **Свойства** — `#[Inject]` / `#[Autowire]` всегда; typed properties при `enablePropertyAutowiring()`
3. **Методы** — attributes на методе/параметрах всегда; setter и прочие inject-методы при `enableMethodAutowiring()`

Разрешение значений делегируется `MemberResolver` (attributes → имя параметра → reflection-тип).

## Включение autowiring конструктора

### Глобальный режим

```php
$container = new Container();
$container->enableAutowiring();

$service = $container->get(App\Service\UserService::class);
```

Любой **существующий и instantiable** класс доступен через `get(FQCN)` без явного `set()`.

```php
$container->isAutowiringEnabled(); // true
$container->disableAutowiring();
```

### Точечная регистрация

```php
$container->autowire(App\Service\UserService::class);
$container->get(App\Service\UserService::class);
```

Класс доступен через `get()` **даже при выключенном** глобальном autowiring.

### Autowiring по имени параметра

По умолчанию **выключен**:

```php
$container->set('logger', $logger);
$container->enableParameterNameAutowiring();
$container->autowire(App\Service\OrderService::class);
```

```php
final readonly class OrderService
{
    public function __construct(
        private LoggerInterface $logger, // → $container->get('logger')
    ) {
    }
}
```

Работает для параметров **конструктора**, **свойств** (через `MemberResolver`) и **методов**.

## Autowiring свойств

### Режимы

| Режим | Когда срабатывает |
|-------|-------------------|
| Attributes `#[Inject]` / `#[Autowire]` | **Всегда**, независимо от флагов |
| `enablePropertyAutowiring()` | Все **неинициализированные** typed properties без promoted/static |

```php
$container->enablePropertyAutowiring();
$container->isPropertyAutowiringEnabled();
$container->disablePropertyAutowiring();
```

### Пример: attribute на свойстве

```php
use CloudCastle\DI\Attribute\Inject;

final class ReportExporter
{
    #[Inject('app.clock')]
    private ClockInterface $clock;

    public function getClock(): ClockInterface
    {
        return $this->clock;
    }
}
```

```php
$container->set('app.clock', $clock);
$container->autowire(ReportExporter::class);

$exporter = $container->get(ReportExporter::class);
```

Attribute **не требует** `enablePropertyAutowiring()` — достаточно `autowire()` или `enableAutowiring()`.

### Пример: typed property без attribute

```php
final class LegacyHandler
{
    private LoggerInterface $logger;

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }
}
```

```php
$container->set(LoggerInterface::class, $logger);
$container->enablePropertyAutowiring();
$container->autowire(LegacyHandler::class);

$handler = $container->get(LegacyHandler::class);
```

### Что пропускается

- **Promoted properties** конструктора (`public function __construct(private Clock $clock)`)
- **Уже инициализированные** свойства (`private Clock $clock = new SystemClock()`)
- **Static**-свойства
- **Untyped** properties (без `#[Inject]`)

### Nullable и union на свойствах

```php
final class OptionalCacheConsumer
{
    private ?CacheInterface $cache;

    public function getCache(): ?CacheInterface
    {
        return $this->cache;
    }
}
```

```php
$container->enablePropertyAutowiring();
$container->autowire(OptionalCacheConsumer::class);
// CacheInterface не зарегистрирован → $cache = null
```

Union `LoggerInterface|Clock` — первый разрешимый object-тип слева направо (как у параметров конструктора).

## Autowiring методов

### Режимы

| Режим | Когда срабатывает |
|-------|-------------------|
| Attributes на методе или параметрах | **Всегда** |
| `enableMethodAutowiring()` | Public/protected методы **класса** (не родителя) с параметрами, не magic/static/constructor |

```php
$container->enableMethodAutowiring();
$container->isMethodAutowiringEnabled();
$container->disableMethodAutowiring();
```

### Пример: inject-метод с attribute

```php
use CloudCastle\DI\Attribute\Inject;

final class AuditTrail
{
    private ClockInterface $clock;

    #[Inject]
    protected function setClock(ClockInterface $clock): void
    {
        $this->clock = $clock;
    }
}
```

```php
$container->set(ClockInterface::class, $clock);
$container->enableAutowiring(); // или autowire(AuditTrail::class)

$audit = $container->get(AuditTrail::class);
```

### Пример: setter без attribute

```php
final class MailerConsumer
{
    private MailerInterface $mailer;

    public function setMailer(MailerInterface $mailer): void
    {
        $this->mailer = $mailer;
    }
}
```

```php
$container->set(MailerInterface::class, $mailer);
$container->enableMethodAutowiring();
$container->autowire(MailerConsumer::class);

$consumer = $container->get(MailerConsumer::class);
```

### Что пропускается

- `__construct`, `__destruct`, magic-методы (`__call`, …)
- **Static**-методы
- Методы, объявленные в **родительском** классе (только методы самого autowired-класса)
- Методы **без параметров**

## PHP attributes

Attributes `Inject` и `Autowire` применимы к:

- параметрам конструктора;
- **свойствам**;
- **методам** и их **параметрам**.

```php
use CloudCastle\DI\Attribute\Autowire;
use CloudCastle\DI\Attribute\Inject;

final readonly class ReportService
{
    public function __construct(
        #[Inject('app.clock')]
        public ClockInterface $clock,

        #[Autowire(service: 'mailer')]
        public MailerInterface $mailer,
    ) {
    }
}
```

```php
$container->set('app.clock', $clock);
$container->set('mailer', $mailer);
$container->autowire(ReportService::class);
```

Если `id` / `service` **не задан**, применяются autowiring по имени (если включён) и по типу.

Attributes с явным id **имеют приоритет** над autowiring по имени.

## Полный пример: конструктор + property + method

```php
use CloudCastle\DI\Attribute\Inject;
use CloudCastle\DI\Container;

final class OrderProcessor
{
    private MetricsCollector $metrics;

    public function __construct(
        private OrderRepository $repository,
    ) {
    }

    #[Inject('app.metrics')]
    private function wireMetrics(MetricsCollector $metrics): void
    {
        $this->metrics = $metrics;
    }

    public function setEventBus(EventBus $bus): void
    {
        // вызывается только при enableMethodAutowiring()
    }
}

$container = new Container();
$container->set('app.metrics', $metrics);
$container->set(EventBus::class, $eventBus);
$container->enableAutowiring();
$container->enableMethodAutowiring();

$processor = $container->get(OrderProcessor::class);
```

- `OrderRepository` — через конструктор (autowiring по типу)
- `MetricsCollector` — через `#[Inject]` на методе (без `enableMethodAutowiring()`)
- `EventBus` — через `setEventBus()` только если включён `enableMethodAutowiring()`

## Разрешение по reflection-типам (конструктор, свойства, методы)

### Именованные object-типы

Рекурсивный `get()` / autowiring по FQCN.

### `ContainerInterface` и PSR-11

В параметр передаётся **тот же экземпляр контейнера**, который выполняет autowiring.

### Nullable-типы

Если зависимость недоступна — `null` (для свойств и nullable union).

### Значения по умолчанию (только параметры)

```php
public function __construct(ClockInterface $clock = new SystemClock()) {}
```

Default используется, если для типа **нет** `hasDefinition()`. Явный `set()` / `autowire()` имеет приоритет.

### Union-типы

Первый разрешимый **не-builtin** вариант слева направо.

### Intersection-типы (`A&B`)

```php
public function __construct(Iterator&Countable $storage) {}
```

Кандидат из контейнера проверяется `instanceof` для **каждого** члена intersection.

```php
$container->set(Iterator::class, new ArrayIterator(['a', 'b']));
$container->autowire(StorageConsumer::class);
```

### Встроенные типы

Autowiring **не** подставляет скаляры. Для параметров — default или `ContainerException`; для свойств — `ContainerException`.

## Приоритеты

| Источник | Приоритет |
|----------|-----------|
| Singleton-кэш (`resolved`) | наивысший |
| Явный `set()` | выше autowiring |
| `#[Inject]` / `#[Autowire]` с id | выше имени и типа |
| Имя параметра (`enableParameterNameAutowiring`) | выше типа |
| `autowire(FQCN)` / глобальный autowiring | ниже `set()` |
| `NotFoundException` | если ничего не подошло |

## Циклические зависимости

При autowiring цикл A → B → A → `ContainerException`:

```
Обнаружена циклическая зависимость при autowiring сервиса "…".
```

После ошибки контейнер не остаётся в «зависшем» состоянии.

> Циклы в **фабриках** `set()` не отслеживаются. См. [Фабрики и singleton](Factories-and-singleton).

## `has()` и `hasDefinition()`

```php
$container->enableAutowiring();
$container->has(App\Service\UserService::class);           // true, если класс instantiable
$container->hasDefinition(App\Service\UserService::class); // false до autowire() или get()

$container->autowire(App\Service\UserService::class);
$container->hasDefinition(App\Service\UserService::class); // true
```

## Singleton-поведение

Autowired-сервис кэшируется: повторный `get(FQCN)` — **тот же экземпляр** до `set()` / `autowire()`.

## Ограничения

CloudCastle DI **не** поддерживает:

- compiled container;
- конфигурационные YAML/XML;
- autoconfigure Symfony / event subscribers;
- private inject-методы без attributes (только public/protected).

Для property/method injection **без конструктора** используйте `autowire()` + соответствующие флаги или attributes.

## См. также

- [Сканирование классов](Class-scanning)
- [Справочник API](API-reference)
- [Анти-паттерны](Anti-patterns)
