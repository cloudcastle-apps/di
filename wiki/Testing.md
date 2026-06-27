# Тестирование

## Наборы тестов

| Команда | Каталог | Тестов | Описание |
|---------|---------|--------|----------|
| `composer test:unit` | `tests/Unit/` | 421 | API, autowiring, configuration, freeze, mutation-сценарии |
| `composer test:integration` | `tests/Integration/` | 5 | Графы зависимостей, PSR-11 |
| `composer test:security` | `tests/Security/` | 17 | Безопасность resolve, id, autowiring, freeze |
| `composer test:load` | `tests/Load/` | 15 | Массовые регистрации и resolve (1000–3000 ops) |
| `composer test:performance` | `tests/Performance/` | 12 | Латентность hot path (до 10 000 итераций) |
| `composer test:coverage` | — | — | Покрытие строк ≥95% |
| `composer test:mutation` | — | — | Infection MSI ≥95% |
| `composer benchmark-check` | `tools/` | — | Регрессия бенчмарков (порог ×1.5, как в CI) |

**Всего:** 470 PHPUnit-тестов (без coverage/mutation/benchmark-check).

Фактические метрики (локальный прогон v1.6): покрытие строк **~98%**; Infection MSI **≥95%** по всему `src/` (включая `src/Configuration/`).

### Подробная документация по наборам

| Набор | Wiki |
|-------|------|
| Security (17) | **[Тесты безопасности](Security-tests)** — кэш, autowire, freeze, id |
| Load (15) + Performance (12) | **[Нагрузка и производительность](Performance-and-load)** — методология, пороги, `benchmark-check` |

```bash
composer test:security
composer test:load
composer test:performance
composer benchmark-check       # после performance — как в CI
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

### Configuration и mutation

Каталог `tests/Unit/Configuration/` покрывает merger, loaders (PHP/JSON/YAML/XML), applicator и edge cases для Infection. Trait `ConfigurationArrayAssertTrait` — типобезопасные проверки массивов конфигурации в тестах.

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

- [Тесты безопасности](Security-tests) — 17 сценариев, шаги, риски
- [Нагрузка и производительность](Performance-and-load) — 15 load + 12 performance, пороги CI, `benchmark-check`

## Покрытие и мутации

```bash
composer test:coverage   # порог ≥95% строк (~98% фактически)
composer test:mutation   # Infection MSI ≥95% по src/
```

Требуется PCOV или Xdebug (`XDEBUG_MODE=coverage`).
