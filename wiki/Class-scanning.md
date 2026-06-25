# Сканирование классов

Метод `Container::scan()` находит PHP-классы в каталоге и регистрирует их для autowiring через `autowire()`.

## Базовый пример

```php
$container = new Container();
$container->enableAutowiring(); // необязательно, если только autowire() по scan

$container->scan(__DIR__ . '/src/Services', 'App\\Services\\');

$service = $container->get(App\Services\OrderService::class);
```

Второй аргумент — **фильтр по префиксу namespace**. Только классы, чьё FQCN начинается с `App\Services\`, будут зарегистрированы.

Без фильтра:

```php
$container->scan(__DIR__ . '/src');
```

## Как работает `ClassScanner`

Класс `CloudCastle\DI\ClassScanner`:

1. Рекурсивно обходит каталог (`RecursiveDirectoryIterator`).
2. Берёт только файлы с расширением `.php`.
3. Читает содержимое файла **без выполнения** (`file_get_contents`).
4. Извлекает `namespace` и имя `class` регулярными выражениями.
5. Проверяет через `class_exists()` (срабатывает autoload).
6. Оставляет только **instantiable** классы (не abstract, не interface, не trait).

Поддерживаются модификаторы `abstract`, `final`, `readonly` в объявлении класса в regex.

## Поведение `Container::scan()`

```php
foreach ($scanner->scan($directory, $namespace) as $className) {
    if (!$this->hasDefinition($className)) {
        $this->autowire($className);
    }
}
```

| Ситуация | Результат |
|----------|-----------|
| Класс уже зарегистрирован через `set()` | **пропуск** — явная регистрация сохраняется |
| Класс уже в `autowire()` | `autowire()` вызывается снова (сброс кэша singleton) |
| Файл без класса / только trait / interface | пропуск |
| Класс abstract | пропуск |
| Namespace не совпадает с фильтром | пропуск |
| Каталог не существует | `ContainerException` |

## Требования к структуре проекта

- **PSR-4 autoload** должен быть настроен: после парсинга имени класса вызывается `class_exists()`.
- Один класс на файл — стандартная практика PSR-4; иначе autoload может не найти класс.
- Файлы с синтаксическими ошибками могут не загрузиться — класс будет пропущен.

## Фильтр namespace

Префикс нормализуется: trailing `\` добавляется автоматически.

```php
$container->scan($dir, 'App\\Services');   // эквивалентно 'App\\Services\\'
$container->scan($dir, 'App\\Services\\');
```

Класс `App\Services\OrderHandler` — **включён**.  
Класс `App\Domain\Order` — **исключён**.

## Отличие от classmap / Composer scan

`scan()` — **runtime**-регистрация в контейнере, не замена Composer autoload. Он не индексирует vendor и не компилирует контейнер.

Типичный сценарий — bootstrap приложения:

```php
function bootstrapContainer(): Container
{
    $container = new Container();
    $container->enableAutowiring();
    $container->scan(__DIR__ . '/../src/Application', 'App\\Application\\');

    // Явные переопределения поверх scan
    $container->set(LoggerInterface::class, new MonologLogger(...));

    return $container;
}
```

## Ограничения

- Не находит **enum** (regex ищет `class`, не `enum`).
- Не парсит **несколько классов** в одном файле.
- Не выполняет static side effects при сканировании — но `class_exists()` **загружает** класс через autoload.
- Anonymous classes и файлы только с `return` / функциями — пропуск.

## См. также

- [Autowiring](Autowiring) — разрешение зависимостей после scan
- [Справочник API](API-reference) — `scan()`, `ClassScanner`
