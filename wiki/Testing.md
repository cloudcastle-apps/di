# Тестирование

## Наборы тестов

| Команда | Каталог | Тестов | Описание |
|---------|---------|--------|----------|
| `composer test:unit` | `tests/Unit/` | 208 | Поведение API, autowiring, v1.2/v1.3 |
| `composer test:integration` | `tests/Integration/` | 5 | Графы зависимостей, PSR-11 |
| `composer test:security` | `tests/Security/` | 4 | Безопасность resolve и сообщений об ошибках |
| `composer test:load` | `tests/Load/` | 15 | Массовые регистрации и resolve (1000–3000 ops) |
| `composer test:performance` | `tests/Performance/` | 12 | Латентность hot path (до 10 000 итераций) |
| `composer test:coverage` | — | — | Покрытие строк ≥95% |
| `composer test:mutation` | — | — | Infection MSI ≥95% |

**Всего:** 244 PHPUnit-теста (без coverage/mutation).

### Подробная документация по наборам

| Набор | Wiki |
|-------|------|
| Security (4) | **[Тесты безопасности](Security-tests)** — пошагово каждый тест |
| Load (15) + Performance (12) | **[Нагрузка и производительность](Performance-and-load)** — методология, все 27 сценариев, пороги, бенчмарки |

```bash
composer test:security
composer test:load
composer test:performance
composer ci                  # полный пайплайн
composer benchmark-report    # фактические времена бенчмарков (markdown)
```

## Unit-тесты

Создавайте новый `Container` в каждом тесте — экземпляры не разделяют состояние:

```php
final class OrderServiceTest extends TestCase
{
    public function testCreatesOrder(): void
    {
        $container = new Container();
        $container->set('clock', new FixedClock('2026-01-01'));
        $container->set(
            'orders',
            static fn (ContainerInterface $c): OrderService => new OrderService($c->get('clock')),
        );

        $service = $container->get('orders');
        // assertions...
    }
}
```

## Autowiring в тестах

```php
$container = new Container();
$container->enableAutowiring();
$container->set(ClockInterface::class, new FixedClock('2026-01-01'));

$service = $container->get(OrderService::class);
```

Property и method injection в тестах:

```php
$container = new Container();
$container->enableAutowiring();
$container->enablePropertyAutowiring();
$container->enableMethodAutowiring();
$container->set(LoggerInterface::class, new NullLogger());

$service = $container->get(LegacyServiceWithSetters::class);
```

Явный `set()` переопределяет autowiring для того же типа.

## Подмена зависимостей

Зарегистрируйте тестовый double **до** первого `get()`:

```php
$container->set('mailer', $this->createMock(MailerInterface::class));
```

После `get()` замена через `set()` сбросит кэш — при следующем `get()` будет новый singleton.

## `ContainerRegistry`

Если тесты используют глобальный реестр, сбрасывайте его:

```php
protected function tearDown(): void
{
    ContainerRegistry::reset();
    parent::tearDown();
}

public function testWithRegistry(): void
{
    $container = new Container();
    $container->set('clock', new FixedClock());
    ContainerRegistry::set($container);

    // код, вызывающий ContainerRegistry::get()
}
```

Предпочтительнее передавать `Container` явно — без глобального состояния.

## Интеграционные тесты

Вынесите регистрацию сервисов в функцию или класс:

```php
function createApplicationContainer(): Container
{
    $container = new Container();
    $container->enableAutowiring();
    $container->scan(__DIR__ . '/../src/Services', 'App\\Services\\');
    // test doubles...
    return $container;
}
```

## Нагрузка, производительность и безопасность

Кратко — в таблице выше. **Подробно:**

- [Тесты безопасности](Security-tests) — 4 сценария, шаги, риски
- [Нагрузка и производительность](Performance-and-load) — 15 load + 12 performance, пороги CI, `benchmark-report`

## Покрытие и мутации

```bash
composer test:coverage   # порог ≥95% строк
composer test:mutation   # Infection MSI ≥95%
```

Требуется PCOV или Xdebug (`XDEBUG_MODE=coverage`).
