# Тестирование

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

## Подмена зависимостей

Зарегистрируйте тестовый double **до** первого `get()`:

```php
$container->set('mailer', $this->createMock(MailerInterface::class));
```

После `get()` замена через `set()` сбросит кэш — при следующем `get()` будет новый singleton.

## Интеграционные тесты

Вынесите регистрацию сервисов в функцию или класс:

```php
function createApplicationContainer(): Container
{
    $container = new Container();
    // register services...
    return $container;
}
```

Так проще переиспользовать wiring в нескольких тестах.

## Команды в репозитории

```bash
composer test:unit
composer test:integration
composer test:security
composer ci
```

## Покрытие и мутации

Проект поддерживает 100% line coverage и Infection MSI 100%:

```bash
composer test:coverage
composer test:mutation
```

Требуется PCOV или Xdebug (`XDEBUG_MODE=coverage`).
