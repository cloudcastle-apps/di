# Справочник параметров конфигурации

Полное описание **декларативной конфигурации** CloudCastle DI: все ключи массива конфигурации, способы загрузки и слияния файлов, примеры для каждого формата.

Краткий обзор и диаграммы — в [Конфигурация из файлов](Configuration). Здесь — **справочник по параметрам** и **пошаговые примеры**.

---

## Когда использовать конфигурацию

| Способ | Когда подходит |
|--------|----------------|
| **PHP API** (`set`, `bind`, `scan`, …) | Полный контроль, callable-фабрики, условная логика в коде |
| **Файлы конфигурации** | Один composition root, несколько окружений (dev/prod), общие defaults |
| **Смешанный** | `loadMany()` + правки массива + `apply()`, или PHP-файл с `return` и callable в `services` |

Конфигурация **необязательна** — контейнер работает без `ContainerConfigurator`.

---

## Поток данных

```mermaid
flowchart LR
    S[Источники] --> R[ConfigurationSourceResolver]
    R --> L[Слои ConfigurationLayer]
    L --> M[ConfigurationMerger]
    M --> A[ConfigurationApplicator]
    A --> C[Container]
```

1. **Resolver** разворачивает строки, каталоги и объекты-источники в список **файлов** (слои).
2. **Merger** объединяет секции с учётом **priority** и порядка загрузки.
3. **Applicator** вызывает методы контейнера в фиксированном порядке (см. ниже).

---

## API `ContainerConfigurator`

| Метод | Назначение |
|-------|------------|
| `configure($container, $sources)` | `loadMany()` + `apply()` |
| `loadMany($sources)` | Загрузить и слить, **без** применения к контейнеру |
| `load($path)` | Один файл → массив |
| `apply($container, $config)` | Применить уже готовый массив |

### Типы элементов `$sources`

| Тип | Пример | Поведение |
|-----|--------|-----------|
| `string` (файл) | `'config/base.php'` | Один файл |
| `string` (каталог) | `'config/layers/'` | Все поддерживаемые файлы каталога, **без** вложенных подкаталогов |
| `ConfigurationSource` | `new ConfigurationSource($path, priority: 50)` | Один файл + приоритет слоя |
| `ConfigurationDirectorySource` | `new ConfigurationDirectorySource($dir, priority: 10, scan: …)` | Каталог + опции |
| `ConfigurationFilesSource` | `new ConfigurationFilesSource([$a, $b], priority: 5)` | Явный список файлов |

Поддерживаемые расширения: `.php`, `.json`, `.yaml`, `.yml`, `.xml`.

---

## Способы указать источники (примеры)

### 1. Один PHP-файл

```php
<?php

use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Container;

$container = new Container();
(new ContainerConfigurator())->configure($container, [
    __DIR__ . '/config/container.php',
]);
```

### 2. Несколько файлов — побеждает последний

```php
$configurator->configure($container, [
    __DIR__ . '/config/base.php',
    __DIR__ . '/config/override.json',
    __DIR__ . '/config/prod.xml',
]);
```

Без явного `priority` значение `app.label` из `prod.xml` перекроет `override.json`.

### 3. Строка-путь к каталогу

Все файлы с поддерживаемым расширением в каталоге загружаются в **лексикографическом** порядке имени:

```php
$configurator->configure($container, [
    __DIR__ . '/config/layers',  // 01-base.php, 02-overlay.json, …
]);
```

Неподдерживаемые расширения (`.ini`, `.env`) **пропускаются**.

### 4. `ConfigurationSource` — файл с приоритетом слоя

```php
use CloudCastle\DI\Configuration\ConfigurationSource;

$configurator->configure($container, [
    __DIR__ . '/config/override.json',
    new ConfigurationSource(__DIR__ . '/config/base.php', priority: 100),
]);
```

Файл `base.php` с `priority: 100` **перебьёт** более поздний JSON без приоритета.

### 5. `ConfigurationDirectorySource` — каталог с опциями

```php
use CloudCastle\DI\Configuration\ConfigurationDirectoryScan;
use CloudCastle\DI\Configuration\ConfigurationDirectorySource;

$configurator->configure($container, [
    new ConfigurationDirectorySource(__DIR__ . '/config/layers'),
    new ConfigurationDirectorySource(
        __DIR__ . '/config/nested',
        priority: 50,
        scan: ConfigurationDirectoryScan::Recursive,
    ),
]);
```

| Параметр | Тип | По умолчанию | Описание |
|----------|-----|--------------|----------|
| `$directory` | `string` | — | Путь к каталогу |
| `$priority` | `?int` | `null` | Приоритет **каждого** файла каталога; `null` — порядок в общем списке источников |
| `$scan` | `ConfigurationDirectoryScan` | `Flat` | `Flat` — только файлы каталога; `Recursive` — включая подкаталоги |

### 6. `ConfigurationFilesSource` — явный список

```php
use CloudCastle\DI\Configuration\ConfigurationFilesSource;

$configurator->configure($container, [
    new ConfigurationFilesSource([
        __DIR__ . '/config/base.php',
        __DIR__ . '/config/override.json',
    ], priority: 10),
]);
```

Порядок в массиве `$paths` = порядок слияния внутри этого источника.

### 7. Загрузка без применения + ручная правка

```php
$configurator = new ContainerConfigurator();
$config = $configurator->loadMany([
    __DIR__ . '/config/base.php',
    __DIR__ . '/config/local.php',
]);

$config['services']['app.debug'] = true;

$configurator->apply($container, $config);
```

### 8. Только `apply()` — массив из кода

```php
$configurator->apply($container, [
    'autowiring' => ['enabled' => true],
    'services' => [
        'app.env' => 'prod',
    ],
    'bind' => [
        LoggerInterface::class => FileLogger::class,
    ],
]);
```

---

## Форматы файлов

| Расширение | Загрузчик | PHP-расширение |
|------------|-----------|----------------|
| `.php` | `PhpConfigurationLoader` | — (формат по умолчанию) |
| `.json` | `JsonConfigurationLoader` | `ext-json` |
| `.yaml`, `.yml` | `YamlConfigurationLoader` | **`ext-yaml`** |
| `.xml` | `XmlConfigurationLoader` | `ext-simplexml` |

- **PHP** — единственный формат, где в `services` допустимы **callable** (фабрики).
- **JSON / YAML / XML** — только декларативные данные (строки, числа, bool, массивы).

Без `ext-yaml` загрузка `.yaml` бросает `ContainerException` с подсказкой установить расширение.

---

## Корневые ключи конфигурации

Итоговый массив после слияния может содержать секции:

| Ключ | Тип | Применение в контейнере |
|------|-----|-------------------------|
| `priority` | `int` | Приоритет **всех** параметров файла без собственного priority (метаданные слоя, не вызывает API) |
| `register_attributes` | `list<string>` | `registerAttribute()` для каждого FQCN |
| `autowiring` | `array<string, bool>` | `enableAutowiring()` и флаги по имени |
| `scan` | `list<array>` | `scan($directory, $namespace?)` |
| `services` | `map<string, mixed>` | `set()`, `bind()`, `autowire()`, `lazy()` |
| `autowire` | `list<string>` | `autowire($class)` |
| `bind` | `map<string, string>` | `bind($abstract, $concrete)` |
| `aliases` | `map<string, string>` | `alias($alias, $target)` |
| `tags` | `map<string, list<string>>` | `tag($id, $tag)` |

### Порядок применения секций

```
register_attributes → autowiring → scan → services → autowire → bind → aliases → tags
```

Сначала регистрируются attributes и флаги autowiring, затем scan добавляет классы, потом явные `services`, и т.д.

---

## Секция `priority` (корень файла)

Задаёт **дефолтный приоритет слоя** для всех ключей файла, у которых нет своего `priority`.

**PHP:**

```php
return [
    'priority' => 50,
    'services' => [
        'app.mode' => 'runtime',
    ],
];
```

**XML** — атрибут корня `<container priority="50">`.

При слиянии побеждает запись с **большим** `effectivePriority`; при равенстве — **более поздний** слой (больший `order`).

---

## Секция `register_attributes`

Список FQCN классов PHP attributes, реализующих `ServiceIdAttribute` (пользовательские `@Inject`-аналоги).

```php
'register_attributes' => [
    App\Attribute\InjectConfig::class,
    App\Attribute\InjectLogger::class,
],
```

**JSON:**

```json
{
    "register_attributes": [
        "App\\Attribute\\InjectConfig"
    ]
}
```

**YAML:**

```yaml
register_attributes:
  - App\Attribute\InjectConfig
```

**XML:**

```xml
<register_attributes>
    <attribute class="App\Attribute\InjectConfig"/>
</register_attributes>
```

---

## Секция `autowiring`

Включает режимы autowiring контейнера. Учитываются только ключи со значением **`true`** (явно).

| Ключ | Метод контейнера |
|------|------------------|
| `enabled` | `enableAutowiring()` |
| `parameter_name` | `enableParameterNameAutowiring()` |
| `property` | `enablePropertyAutowiring()` |
| `method` | `enableMethodAutowiring()` |

**PHP / JSON / YAML:**

```yaml
autowiring:
  enabled: true
  parameter_name: true
  property: true
  method: true
```

**XML:**

```xml
<autowiring enabled="true" parameter_name="true" property="true" method="true"/>
```

Атрибуты `false`, `0`, `no` в XML **не экспортируются** в массив (флаг просто отсутствует).

---

## Секция `scan`

Список каталогов для автоматической регистрации классов через `Container::scan()`.

**PHP:**

```php
'scan' => [
    [
        'directory' => __DIR__ . '/../src',
        'namespace' => 'App\\',
    ],
    [
        'directory' => __DIR__ . '/../modules/Billing',
        // namespace опционален
    ],
],
```

**YAML:**

```yaml
scan:
  - directory: /var/www/app/src
    namespace: App\
```

**XML:**

```xml
<scan>
    <directory path="/var/www/app/src" namespace="App\"/>
</scan>
```

При слиянии дубликаты по ключу `directory` разрешаются через priority/order (как list-секция).

---

## Секция `services`

Карта **id → значение**. Id — произвольная строка (`app.logger`, `config.dsn`, FQCN как id).

### Скаляр, массив, callable (только PHP)

```php
'services' => [
    // скаляр → set($id, $value)
    'app.env' => 'prod',
    'app.debug' => false,
    'app.timeout' => 30,

    // произвольный массив → set как есть
    'config.mail' => [
        'host' => 'smtp.example.com',
        'port' => 587,
    ],

    // callable — только в PHP-файлах
    'logger' => static fn (Container $c): LoggerInterface => new FileLogger(
        $c->get('config.log_path'),
    ),
],
```

### Регистрация класса: `class` и `lazy`

```php
'services' => [
    // id === class → autowire(FQCN)
    FileLogger::class => [
        'class' => FileLogger::class,
    ],

    // id !== class → bind($id, FQCN)
    'logger' => [
        'class' => FileLogger::class,
    ],

    // lazy-сервис
    'lazy.logger' => [
        'class' => FileLogger::class,
        'lazy' => true,
    ],
],
```

| Поле | Тип | Эффект |
|------|-----|--------|
| `class` | `string` FQCN | Класс для `autowire` / `bind` / `lazy` |
| `lazy` | `bool` | `true` → `set($id, $container->lazy($class))` |

**JSON** (без callable):

```json
{
    "services": {
        "app.label": "from-json",
        "lazy.logger": {
            "class": "App\\FileLogger",
            "lazy": true
        }
    }
}
```

**XML:**

```xml
<services>
    <service id="app.label">from-xml</service>
    <service id="logger" class="App\FileLogger" lazy="true"/>
</services>
```

### Приоритет параметра в `services`

**Явная обёртка** (JSON, YAML, XML, PHP):

```php
'app.label' => [
    'value' => 'from-low-priority-file',
    'priority' => 10,
],
```

**Inline priority** в определении класса (PHP, после слияния `priority` снимается с массива):

```php
'app.label' => [
    'class' => SomeClass::class,
    'priority' => 100,
],
```

**XML:**

```xml
<service id="app.label" priority="100">winner</service>
```

---

## Секция `autowire`

Список FQCN для `autowire($class)` (регистрация по имени класса).

**PHP:**

```php
'autowire' => [
    App\Services\Clock::class,
    App\Services\Mailer::class,
],
```

**JSON:**

```json
{
    "autowire": [
        "App\\Services\\Clock"
    ]
}
```

**XML** — элемент `<class>` или `<class name="FQCN"/>`:

```xml
<autowire>
    <class>App\Services\Clock</class>
    <class name="App\Services\Mailer"/>
</autowire>
```

---

## Секция `bind`

Сопоставление абстракции (интерфейс / id) → реализация.

**PHP:**

```php
'bind' => [
    LoggerInterface::class => FileLogger::class,
    'mailer' => App\Mail\SmtpMailer::class,
],
```

**YAML:**

```yaml
bind:
  Psr\Log\LoggerInterface: App\FileLogger
```

**XML:**

```xml
<bind>
    <binding abstract="Psr\Log\LoggerInterface" concrete="App\FileLogger"/>
</bind>
```

С priority:

```php
LoggerInterface::class => [
    'value' => FileLogger::class,
    'priority' => 100,
],
```

---

## Секция `aliases`

Короткие имена для существующих id.

**PHP:**

```php
'aliases' => [
    'env' => 'app.env',
    'log' => 'logger',
],
```

**JSON:**

```json
{
    "aliases": {
        "timeout": "app.timeout"
    }
}
```

---

## Секция `tags`

Группировка id сервисов для `TaggedServiceIterator` / `TaggedServiceLocator`.

**PHP:**

```php
'tags' => [
    'event.handler' => [
        'handler.user_created',
        'handler.order_placed',
    ],
    'middleware' => [
        'middleware.auth',
        'middleware.cors',
    ],
],
```

**XML:**

```xml
<tags>
    <tag name="event.handler">
        <id>handler.user_created</id>
        <id>handler.order_placed</id>
    </tag>
</tags>
```

---

## Правила слияния (priority)

При конфликте одного ключа в map-секциях (`services`, `bind`, `aliases`, `tags`):

1. **`priority` у параметра** — `['value' => …, 'priority' => N]` или XML-атрибут `priority` на элементе
2. **`priority` файла** — ключ `priority` в корне или `ConfigurationSource` / `ConfigurationDirectorySource` / `ConfigurationFilesSource`
3. **Порядок загрузки** — без явных priority побеждает **последний** файл / слой

**Пример:** JSON задаёт `app.label`, XML с `priority="100"` на `<service>` выигрывает даже если JSON загружен позже.

List-секции (`autowire`, `register_attributes`, `scan`): элементы с одинаковым ключом (FQCN, directory) сливаются по тем же правилам; итоговый список сортируется по `order` слоя.

---

## Полный пример: PHP

`config/container.php`:

```php
<?php

declare(strict_types=1);

use App\Attribute\InjectConfig;
use App\FileLogger;
use App\Services\Clock;
use CloudCastle\DI\Container;
use Psr\Log\LoggerInterface;

return [
    'priority' => 10,

    'register_attributes' => [
        InjectConfig::class,
    ],

    'autowiring' => [
        'enabled' => true,
        'parameter_name' => true,
    ],

    'scan' => [
        [
            'directory' => __DIR__ . '/../src',
            'namespace' => 'App\\',
        ],
    ],

    'services' => [
        'app.env' => $_ENV['APP_ENV'] ?? 'dev',

        'logger' => static fn (Container $c): LoggerInterface => new FileLogger(
            __DIR__ . '/../var/log/app.log',
        ),

        Clock::class => [
            'class' => Clock::class,
        ],

        'lazy.heavy' => [
            'class' => App\Services\HeavyService::class,
            'lazy' => true,
        ],
    ],

    'autowire' => [
        App\Services\Mailer::class,
    ],

    'bind' => [
        LoggerInterface::class => FileLogger::class,
    ],

    'aliases' => [
        'env' => 'app.env',
    ],

    'tags' => [
        'console.command' => [
            'command.cache_clear',
            'command.migrate',
        ],
    ],
];
```

Composition root:

```php
$container = new Container();
(new ContainerConfigurator())->configure($container, [
    __DIR__ . '/config/container.php',
    __DIR__ . '/config/local.php',  // переопределения окружения
]);
$container->freeze();
```

---

## Полный пример: JSON + YAML + XML

**`config/10-base.json`:**

```json
{
    "services": {
        "app.env": "staging",
        "app.timeout": 30
    },
    "aliases": {
        "timeout": "app.timeout"
    }
}
```

**`config/20-overlay.yaml`:**

```yaml
services:
  app.label: from-yaml
  app.region: eu

aliases:
  region: app.region

autowiring:
  enabled: true
```

**`config/30-prod.xml`:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<container priority="100">
    <services>
        <service id="app.label" priority="200">production</service>
        <service id="app.env">prod</service>
    </services>
    <autowiring enabled="true" parameter_name="true"/>
    <bind>
        <binding abstract="Psr\Log\LoggerInterface" concrete="App\FileLogger"/>
    </bind>
</container>
```

**Загрузка каталогом:**

```php
$configurator->configure($container, [
    __DIR__ . '/config',  // 10-base.json → 20-overlay.yaml → 30-prod.xml
]);
```

Итог: `app.label` = `production` (priority 200), `app.env` = `prod`, autowiring включён.

---

## Полный пример: слои каталога + recursive

Структура:

```
config/
  layers/
    01-base.php
    02-overlay.json
  nested/
    root.php
    sub/
      child.json
```

```php
use CloudCastle\DI\Configuration\ConfigurationDirectoryScan;
use CloudCastle\DI\Configuration\ConfigurationDirectorySource;

$configurator->configure($container, [
    new ConfigurationDirectorySource(__DIR__ . '/config/layers'),
    new ConfigurationDirectorySource(
        __DIR__ . '/config/nested',
        scan: ConfigurationDirectoryScan::Recursive,
    ),
]);
```

---

## XML: схема корневого элемента

Корень: `<container priority="…">` (атрибут `priority` опционален).

| Секция | Дочерние элементы |
|--------|-------------------|
| `<services>` | `<service id="…" class="…" lazy="true\|false" priority="…">текст</service>` |
| `<aliases>` | `<alias name="…" target="…" priority="…"/>` |
| `<bind>` | `<binding abstract="…" concrete="…" priority="…"/>` |
| `<autowire>` | `<class>FQCN</class>` или `<class name="FQCN" priority="…"/>` |
| `<tags>` | `<tag name="…"><id>…</id></tag>` |
| `<scan>` | `<directory path="…" namespace="…"/>` |
| `<register_attributes>` | `<attribute class="FQCN" priority="…"/>` |
| `<autowiring>` | атрибуты `enabled`, `parameter_name`, `property`, `method` |

---

## Расширение загрузчиков

```php
use CloudCastle\DI\Configuration\ConfigurationLoaderRegistry;
use CloudCastle\DI\Configuration\ContainerConfigurator;
use CloudCastle\DI\Configuration\Loader\JsonConfigurationLoader;
use CloudCastle\DI\Configuration\Loader\PhpConfigurationLoader;

$configurator = new ContainerConfigurator(
    loaderRegistry: new ConfigurationLoaderRegistry([
        new PhpConfigurationLoader(),
        new JsonConfigurationLoader(),
        new MyTomlConfigurationLoader(), // ConfigurationLoaderInterface
    ]),
);
```

Реализуйте `CloudCastle\DI\Contract\ConfigurationLoaderInterface`:

- `supports(string $path): bool`
- `load(string $path): array`

---

## `freeze()` и конфигурация

После `$container->freeze()` вызов `configure()` / `apply()` приведёт к `ContainerException` при попытке изменить определения (как и прямые `set()` / `bind()`).

Рекомендуемый порядок:

```php
$configurator->configure($container, $sources);
$container->freeze();
```

---

## Ошибки загрузки

| Ситуация | Исключение |
|----------|------------|
| Файл не найден / не читается | `ContainerException` «не найден или недоступен» |
| Неподдерживаемое расширение | «формат … не поддерживается» |
| Пустой `ConfigurationFilesSource` | «список файлов … не может быть пустым» |
| Каталог не существует | «каталог … не найден» |
| YAML без ext-yaml | подсказка установить расширение |
| Невалидный JSON/XML | сообщение парсера |

---

## Что **не** задаётся через конфигурацию

Следующее настраивается только PHP API (не секции файлов):

- `decorator()` / цепочки декораторов
- `afterResolving()` / `bind()` с closure
- `make()` / прототипы (только через явный `set` с фабрикой)
- `ContainerRegistry::set()`

См. [Теги и декораторы](Tags-and-decorators), [call(), bind(), afterResolving](Call-bind-callbacks).

---

## См. также

- [Конфигурация из файлов](Configuration) — обзор и диаграммы
- [Примеры bootstrap](Bootstrap) — composition root, prod + `freeze()`
- [Autowiring](Autowiring) — `register_attributes`, inject attributes
- [Сканирование классов](Class-scanning) — детали `scan()`
- [Справочник API](API-reference) — `ContainerConfigurator`, `registerAttribute()`
