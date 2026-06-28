# История изменений

Формат основан на [Keep a Changelog](https://keepachangelog.com/ru/1.1.0/),
версионирование — [SemVer](https://semver.org/lang/ru/).

## [1.10.0] — 2026-06-28

### Добавлено

- **Contextual binding — контракты** ([#25](https://github.com/cloudcastle-apps/di/issues/25), часть 1 из 4, [#61](https://github.com/cloudcastle-apps/di/pull/61))
- `ContextualBinding` — value object when/needs/give
- `ContextualBindingRegistryInterface`, fluent-интерфейсы `when()` / `needs()` / `give()`

### Изменено

- **Тесты:** 607 PHPUnit (unit 555, integration 8, security 17, load 15, performance 12)

### Документация

- Wiki: [Contextual binding](wiki/Contextual-binding.md), обновлены Home, API-reference, Upgrading
- `UPGRADING.md`: секция 1.9.0 → 1.10.0

## [1.9.0] — 2026-06-25

### Добавлено

- **`ContainerCompiler`** — компиляция замороженного контейнера в PHP-класс ([#24](https://github.com/cloudcastle-apps/di/issues/24))
- `Compiler/*`: `ContainerCompileSnapshotBuilder`, `CompiledContainerPhpGenerator`, `CompileConstructorPlanner`, `CompileParameterReferenceResolver`
- **`AbstractCompiledContainer`** — базовый runtime-класс сгенерированных контейнеров
- Интеграционные тесты parity runtime vs compiled

### Изменено

- **Покрытие:** 100% line coverage по `src/`; убраны skip в YAML-тестах
- **Тесты:** 604 PHPUnit (unit 552, integration 8, security 17, load 15, performance 12)
- CI: `test:performance` с `XDEBUG_MODE=off`; mutation только PHP 8.3+

### Документация

- Wiki: [Compiled container](wiki/Compiled-container.md), обновлены Comparison, API-reference, Upgrading
- `UPGRADING.md`: секция 1.8.0 → 1.9.0

## [1.8.0] — 2026-06-25

### Изменено

- **Минимальная версия PHP:** `^8.3` → **`^8.1`**; CI matrix PHP 8.1–8.5 (GitHub Actions + GitLab CI)
- Код: совместимость с PHP 8.1 (без `readonly class`, `#[Override]`, typed class constants)
- Dev: PHPUnit ^10.5, PHPStan ^1.12, Rector `UP_TO_PHP_81`, Psalm 8.1

### Документация

- Wiki и README: полное оформление, баннер `assets/docs-hero.svg`
- [Comparison](wiki/Comparison.md): единая таблица vs **6 аналогов** (PHP-DI, Symfony, Pimple, Laravel, League, Nette) с колонкой победителей
- `UPGRADING.md`: секция 1.7.0 → 1.8.0

## [1.7.0] — 2026-06-27

### Добавлено

- **`ConfigurationDirectorySource`**, **`ConfigurationFilesSource`**, **`ConfigurationSourceResolver`** — конфигурация из каталога (flat/recursive) и явного списка файлов
- **`ConfigurationDirectoryScan`** — enum `Flat` / `Recursive` для сканирования каталогов
- **`tools/coverage-check.php`** — проверка покрытия ≥95% **по каждому файлу** в CI
- **`.github/issue-views.yml`**, **`tools/sync-github-issue-views.py`** — синхронизация GitHub Issue Views
- Wiki: [**Справочник параметров конфигурации**](wiki/Configuration-reference.md)

### Изменено

- **`ContainerConfigurator`:** `$sources` — `list<string|ConfigurationSource|ConfigurationDirectorySource|ConfigurationFilesSource>`
- CI: расширение `ext-yaml` в setup-php; per-file coverage gate
- **Тесты:** 506 PHPUnit (unit 457, integration 5, security 17, load 15, performance 12)
- Расширены unit/mutation-тесты для loaders, `CallableInvoker`, `ConfigurationSourceResolver`

### Исправлено

- **`YamlConfigurationLoader`:** обработка `null`/`false` от `yaml_parse_file`; предупреждения парсера → `ContainerException`

## [1.6.0] — 2026-06-27

### Добавлено

- **`composer benchmark-check`** — проверка регрессии производительности (`tools/benchmark-lib.php`, порог ×1.5 для CI)
- Шаг **Benchmark regression check** в GitHub Actions после `test:performance`
- Расширенные unit-тесты для `src/Configuration/` (merger, loaders, applicator, XML/JSON edge cases)
- Черновик миграции **v2.0** в `UPGRADING.md` (breaking changes, чек-лист, ссылки на #24/#25/#33)

### Изменено

- **Infection:** `src/Configuration/` снова в mutation scope; MSI ≥95% по всему `src/`
- **Тесты:** 470 PHPUnit (unit 421, integration 5, security 17, load 15, performance 12); покрытие строк ~98%
- **XmlConfigurationLoader:** в секцию `autowiring` попадают только явные `true`-флаги (атрибуты `false`/`0` не экспортируются)
- Wiki, README, `doc/guide/` — актуализированы под v1.6.0 (тесты, бенчмарки, сравнение с аналогами)

### Исправлено

- YAML-тест в CI не пропускается при наличии `ext-yaml`

## [1.5.0] — 2026-06-26

### Добавлено

- **`ContainerConfigurator`** — опциональная конфигурация из PHP, JSON, YAML, XML; несколько источников с приоритетами
- Классы `Configuration\*`: загрузчики, merger, applicator, `ConfigurationSource`
- **`registerAttribute()`** — регистрация пользовательских PHP attributes (`ServiceIdAttribute`)
- **`AttributeServiceIdRegistry`**, контракт **`ServiceIdAttribute`**
- `composer.json` → `suggest.ext-yaml` для YAML-конфигов
- Wiki: [Configuration](wiki/Configuration.md); обновлены API, Bootstrap, Autowiring, Testing, README

### Изменено

- **Тесты:** 375 PHPUnit (unit 326, integration 5, security 17, load 15, performance 12); покрытие строк ~96%
- Infection: MSI ≥95% по ядру; `src/Configuration/` временно вне mutation scope (исправлено в 1.6.0)
- Документация и wiki приведены к v1.5.0

## [1.4.0] — 2026-06-26

### Добавлено

- **`freeze()`** / **`isFrozen()`** — блокировка изменения определений после bootstrap
- **`getDefinitionIds()`** — список зарегистрированных id без resolve
- **`dump()`** — снимок состояния контейнера для отладки
- **`ServiceAliasResolver::getAliases()`** — доступ к карте alias (для dump)
- Unit-тесты: `ContainerFreezeTest`, `ContainerIntrospectionTest`
- Wiki: API, Bootstrap — примеры freeze и dump

## [1.3.1] — 2026-06-26

### Исправлено

- **`afterResolving()`:** при исключении в callback singleton снимается из кэша (не остаётся частично инициализированный сервис)

### Добавлено

- **Security-тесты:** 4 → 16 (`ContainerSecurityResolveTest`, расширен `ContainerSecurityTest`)
- Wiki: подробное описание security/load/performance; футер `_Footer.md`; пошаговое [Comparison](wiki/Comparison.md)

### Изменено

- README, doc/guide — актуальные счётчики тестов (256)

## [1.3.0] — 2026-06-25

### Добавлено

- **`Container::call()`** — вызов callable с autowiring параметров; класс **`CallableInvoker`**
- **`Container::bind()`** — привязка абстракции к классу (`autowire` + `alias`) или к существующему id
- **`Container::addDefinitions()`** — массовая регистрация через `set()`
- **`Container::afterResolving()`** — callback после создания сервиса (не при чтении из singleton-кэша); класс **`AfterResolvingDispatcher`**
- **`getTaggedIds()`**, **`getTaggedIterator()`**, **`getTaggedLocator()`** — доступ к тегам без полного `getTagged()`; классы **`TaggedServiceIterator`**, **`TaggedServiceLocator`**
- Wiki: [call(), bind(), afterResolving](wiki/Call-bind-callbacks.md); обновлены API, теги, архитектура

### Изменено

- README, CHANGELOG, UPGRADING, Wiki (Home, Architecture, API, FAQ, Quick-start, Bootstrap, Tags) под v1.3.0
- Расширен PHPDoc классов v1.3 (`CallableInvoker`, `AfterResolvingDispatcher`, `TaggedServiceIterator`, `TaggedServiceLocator`, `Container`)
- PHPMD: порог сложности `Container` и ignorepattern публичных методов API
- **Тесты:** load 3→15, performance 4→12; `ContainerV13LoadTest`, `ContainerTaggedLoadTest`, `ContainerV13PerformanceTest`, `ContainerAutowirePerformanceTest`; `tools/benchmark-report.php`, `composer benchmark-report`
- Wiki: [Нагрузка и производительность](wiki/Performance-and-load.md), обновлены [Testing](wiki/Testing.md), README, CONTRIBUTING

## [1.2.0] — 2026-06-26

### Добавлено

- **`Container::make()`** — создание сервиса без singleton-кэша (прототип)
- **`Container::alias()`** — привязка альтернативного id к целевому сервису; цепочки alias и детекция циклов
- **`Container::lazy()`** и класс **`LazyService`** — отложенное `get()` при первом `getValue()`
- Классы **`ServiceAliasResolver`**, **`ServiceInstanceResolver`** — разрешение alias и создание экземпляров
- **`ClassScanner`:** парсинг нескольких `class` в одном файле; объявления `enum` в regex (enum не регистрируются — не instantiable)
- Wiki: [Архитектура](wiki/Architecture.md) — Mermaid-схемы работы пакета
- Wiki: [Прототипы, alias и lazy](wiki/Prototypes-alias-lazy.md); обновлены API, scan, factories

### Изменено

- `has()` / `hasDefinition()` учитывают alias
- `getTagged()` разрешает alias у id в теге
- Логика resolve вынесена в `ServiceInstanceResolver` (снижение сложности `Container`)
- README, CHANGELOG, UPGRADING под v1.2.0

## [1.1.0] — 2026-06-25

### Добавлено

- **Autowiring:** `enableAutowiring()`, `disableAutowiring()`, `isAutowiringEnabled()`, `autowire()`; разрешение зависимостей конструктора по reflection
- **PHP attributes:** `CloudCastle\DI\Attribute\Inject`, `Autowire` — явный id сервиса на параметре конструктора
- **Intersection-типы** (`Iterator&Countable`) при autowiring
- **Autowiring по имени параметра:** `enableParameterNameAutowiring()`, `disableParameterNameAutowiring()`, `isParameterNameAutowiringEnabled()` (по умолчанию выключен)
- **Autowiring свойств:** `enablePropertyAutowiring()`, `disablePropertyAutowiring()`, `isPropertyAutowiringEnabled()`; attributes на property — всегда
- **Autowiring методов:** `enableMethodAutowiring()`, `disableMethodAutowiring()`, `isMethodAutowiringEnabled()`; attributes на method — всегда
- **Сканирование:** `scan($directory, $namespace?)`, класс `ClassScanner`
- **Tagged services:** `tag()`, `getTagged()`
- **Декораторы:** `decorate()`
- **Глобальный реестр:** `ContainerRegistry::set()` / `get()` / `has()` / `reset()`
- Классы `Autowirer`, `MemberResolver`, `PropertyInjector`, `MethodInjector`, `ParameterTypeResolver`, `ClassDependencyResolver`, `IntersectionTypeResolver`, `AttributeServiceIdReader`; обнаружение циклических зависимостей при autowiring
- Wiki: Autowiring, Class-scanning, Global-registry, Tags-and-decorators; обновлены все страницы
- Руководство `doc/guide/`: autowiring, class-scanning, global-registry, tags-decorators

### Изменено

- `has()` учитывает доступность через autowiring
- `hasDefinition()` учитывает явный `autowire()`
- README, FAQ, `composer.json` description и keywords

## [1.0.3] — 2026-06-25

### Добавлено

- GitHub Wiki: 10 страниц документации, sidebar, workflow публикации из каталога `wiki/`
- Защита ветки `main` (ruleset: PR, CI PHP 8.3–8.5)
- Labels `dependencies`, `github-actions` для Dependabot

### Изменено

- GitHub Actions: `actions/checkout` 4 → 7, `actions/cache` 4 → 6
- Dev-зависимости: Deptrac 4, PHPCS 4, Infection 0.33 (без изменений публичного API)

## [1.0.2] — 2026-06-25

### Добавлено

- Расширенные `keywords` и двуязычное `description` в `composer.json` для поиска на Packagist
- README: English lead, таблица сравнения с PHP-DI / Symfony / Pimple, badges CI и coverage
- GitHub topics репозитория (`php`, `dependency-injection`, `psr-11`, …)
- CODEOWNERS, Dependabot, `UPGRADING.md`, user guide в `doc/guide/`

## [1.0.1] — 2026-06-25

### Исправлено

- Метаданные пакета: `homepage` на Packagist, `support.source` на репозиторий `cloudcastle-apps/di`
- `FUNDING.yml` без блока `github` до одобрения GitHub Sponsors (кнопка Sponsor без ошибки парсинга)

### Добавлено

- Community health files: `CODE_OF_CONDUCT.md`, `SUPPORT.md`, `GOVERNANCE.md`, `CITATION.cff`
- Шаблоны GitHub Discussions, Issues и Pull Request
- Автообновление Packagist через GitHub Actions
- Расширенные тесты: граничные случаи, цепочки зависимостей, performance `hasDefinition`

## [1.0.0] — 2026-06-25

### Добавлено

- Реализация DI-контейнера `CloudCastle\DI\Container`
- Контракт `ContainerInterface`, расширяющий PSR-11
- Исключения `NotFoundException`, `ContainerException`
- Регистрация сервисов через `set()` с поддержкой фабрик и singleton-кэша
- Набор тестов: unit, integration, security, load, performance
- Мутационное тестирование (Infection, MSI ≥95%)
- CI для GitHub Actions и GitLab CI
- Инструменты качества: PHPStan max, Psalm level 1, PHPCS, PHPMD, Deptrac, Rector

[1.10.0]: https://github.com/cloudcastle-apps/di/compare/v1.9.0...v1.10.0
[1.9.0]: https://github.com/cloudcastle-apps/di/compare/v1.8.0...v1.9.0
[1.8.0]: https://github.com/cloudcastle-apps/di/compare/v1.7.0...v1.8.0
[1.7.0]: https://github.com/cloudcastle-apps/di/compare/v1.6.0...v1.7.0
[1.6.0]: https://github.com/cloudcastle-apps/di/compare/v1.5.0...v1.6.0
[1.5.0]: https://github.com/cloudcastle-apps/di/compare/v1.4.0...v1.5.0
[1.4.0]: https://github.com/cloudcastle-apps/di/compare/v1.3.1...v1.4.0
[1.3.1]: https://github.com/cloudcastle-apps/di/compare/v1.3.0...v1.3.1
[1.3.0]: https://github.com/cloudcastle-apps/di/compare/v1.2.0...v1.3.0
[1.2.0]: https://github.com/cloudcastle-apps/di/compare/v1.1.0...v1.2.0
[1.1.0]: https://github.com/cloudcastle-apps/di/compare/v1.0.3...v1.1.0
[1.0.3]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.3
[1.0.2]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.2
[1.0.1]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.1
[1.0.0]: https://github.com/cloudcastle-apps/di/releases/tag/v1.0.0
