# Теги и декораторы

Расширения контракта для группового доступа к сервисам и обёрток без изменения фабрик.

## Tagged services

### `tag(string $id, string $tag): void`

Привязывает id сервиса к имени тега. Один сервис может иметь **несколько** тегов. Повторный вызов с тем же id и тегом **не дублирует** запись.

```php
$container->set('logger.app', $appLogger);
$container->set('logger.audit', $auditLogger);
$container->set('metrics', $metrics);

$container->tag('logger.app', 'loggers');
$container->tag('logger.audit', 'loggers');
$container->tag('metrics', 'monitors');
```

### `getTagged(string $tag): array`

Возвращает `array<string, mixed>` — карта **id → экземпляр** в порядке вызовов `tag()` для этого тега.

```php
foreach ($container->getTagged('loggers') as $id => $logger) {
    echo $id; // logger.app, logger.audit
}
```

Неизвестный тег → пустой массив `[]`.

### Пропуск «мёртвых» id

Id **пропускается**, если для него нет `set()` / `autowire()` и класс недоступен через autowiring:

```php
$container->tag('ghost', 'handlers');
$container->getTagged('handlers'); // ghost не попадёт в результат
```

### Autowired-сервисы

Если id зарегистрирован через `autowire()` или доступен при включённом autowiring, `getTagged()` вызовет `get()` и создаст экземпляр.

```php
$container->enableAutowiring();
$container->autowire(App\Handler\EmailHandler::class);
$container->tag(App\Handler\EmailHandler::class, 'handlers');

$handlers = $container->getTagged('handlers');
```

### `getTaggedIds(string $tag): array`

Список id **без** создания экземпляров — удобно для отложенной загрузки или проверки конфигурации.

```php
$ids = $container->getTaggedIds('handlers'); // ['handler.email', 'handler.sms']
```

### `getTaggedIterator(string $tag): TaggedServiceIterator`

Итерация только **значений** (без ключей id):

```php
foreach ($container->getTaggedIterator('handlers') as $handler) {
    $handler->handle($event);
}
```

### `getTaggedLocator(string $tag): TaggedServiceLocator`

Доступ по id внутри тега:

```php
$locator = $container->getTaggedLocator('handlers');

if ($locator->has('handler.email')) {
    $locator->get('handler.email')->send($message);
}

foreach ($locator as $id => $handler) {
    // id => instance
}
```

### Сравнение API тегов

| Метод | Результат | Создаёт экземпляры | Когда использовать |
|-------|-----------|-------------------|-------------------|
| `getTagged($tag)` | `array<id, instance>` | сразу все | нужна карта id → объект |
| `getTaggedIds($tag)` | `list<id>` | нет | проверка конфигурации, отложенная загрузка |
| `getTaggedIterator($tag)` | только values | при `foreach` | pipeline обработчиков без id |
| `getTaggedLocator($tag)` | `has` / `get` / iterate | при `get()` | выборочный доступ по id в теге |

`TaggedServiceLocator` фиксирует список id в конструкторе; `has()` дополнительно проверяет `container->has($id)`.

## Декораторы

### `decorate(string $id, callable $decorator): void`

Регистрирует функцию:

```php
callable(mixed $inner, ContainerInterface $container): mixed
```

Декораторы вызываются при **первом** `get($id)` до кэширования singleton. Порядок регистрации — от **inner** к **outer**:

```php
$container->set('api', static fn () => new ApiClient());

$container->decorate('api', static fn ($inner, $c) => new RetryApiClient($inner));
$container->decorate('api', static fn ($inner, $c) => new LoggingApiClient($inner, $c->get('logger')));

$client = $container->get('api');
// LoggingApiClient(RetryApiClient(ApiClient))
```

### Сброс кэша

`decorate()` **сбрасывает** singleton-кэш для id — следующий `get()` пересоберёт цепочку.

### Autowired + decorate

```php
$container->enableAutowiring();
$container->decorate(App\Service\Mailer::class, static fn ($inner, $c) => new TracingMailer($inner));

$mailer = $container->get(App\Service\Mailer::class);
```

Inner — экземпляр, созданный autowiring.

### Декоратор и `null`

Если декорированный сервис или декоратор вернул `null`, singleton **не кэшируется** (как для фабрик) — см. [Фабрики и singleton](Factories-and-singleton).

## Комбинированный пример

```php
$container = new Container();
$container->enableAutowiring();

$container->set('event.bus', static fn () => new SyncEventBus());
$container->decorate('event.bus', static fn ($inner, $c) => new AsyncEventBus($inner, $c->get('queue')));

$container->autowire(App\Listener\OrderCreatedListener::class);
$container->autowire(App\Listener\OrderShippedListener::class);

$container->tag(App\Listener\OrderCreatedListener::class, 'listeners');
$container->tag(App\Listener\OrderShippedListener::class, 'listeners');

foreach ($container->getTagged('listeners') as $listener) {
    $container->get('event.bus')->subscribe($listener);
}
```

## См. также

- [call(), bind(), afterResolving](Call-bind-callbacks)
- [Справочник API](API-reference)
- [Autowiring](Autowiring)
