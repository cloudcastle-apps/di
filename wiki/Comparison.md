<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/docs-hero.svg" alt="CloudCastle DI — документация" width="100%">
</p>

# 📊 Сравнение с аналогами

> **CloudCastle DI** — лёгкий **PSR-11** контейнер для **PHP 8.1+**. Одна runtime-зависимость — `psr/container`.

Сравниваем **шесть аналогов**: [PHP-DI](https://php-di.org/), [Symfony DI](https://symfony.com/doc/current/service_container.html), [Pimple](https://pimple.symfony.com/), [Laravel Container](https://laravel.com/docs/container), [League Container](https://container.thephpleague.com/), [Nette DI](https://doc.nette.org/en/configuring).

**Формат таблицы:** функция → поддержка в каждой библиотеке → **🏆 победитель**.

---

## Как читать таблицу

| Символ | Значение |
|:------:|----------|
| ✅ | Полная поддержка «из коробки» |
| ⚠️ | Частично, с ограничениями или опционально |
| ❌ | Нет или не предусмотрено |
| 🔌 | Только через адаптер / расширение |

**Победитель** — библиотека (или несколько через запятую), которая лучше закрывает критерий для типичного standalone-проекта. При равной функциональности — **Паритет**.

---

## 🧭 Быстрый выбор за 30 секунд

```mermaid
flowchart LR
    A[Нужен DI] --> B{Symfony / Laravel / Nette?}
    B -->|Да| C[DI фреймворка]
    B -->|Нет| D{До 10 сервисов без autowire?}
    D -->|Да| E[Pimple]
    D -->|Нет| F{Compiler / contextual сейчас?}
    F -->|Да| G[PHP-DI / Symfony / Nette]
    F -->|Нет| H[CloudCastle DI ✨]
```

| Сценарий | Рекомендация |
|----------|--------------|
| Composition root в библиотеке, CLI, API | **CloudCastle DI** |
| Уже Symfony / Laravel / Nette | **Встроенный DI** |
| 3–5 сервисов, без autowiring | **Pimple** |
| Лёгкий PSR-11 + definitions | **League Container** |
| Compiled + NEON, экосистема Nette | **Nette DI** |
| Огромный граф + compiler вне Nette | **PHP-DI** или **Symfony DI** |

---

## 📋 Сводная таблица возможностей

### Основа и экосистема

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **PSR-11** | ✅ | ✅ | ✅ | 🔌 | ✅ | ✅ | 🔌 | **CloudCastle**, PHP-DI, Symfony, Laravel, **League** |
| **Мин. PHP** | ^8.1 | 8.1+ | 8.2+ | 7.2+ | 8.2+ | 8.0+ | 8.1+ | **Pimple** (legacy) |
| **Runtime deps (Composer)** | 1 | неск. | symfony/* | 0 | illuminate/* | 2–3 | nette/* | **Pimple** → **CloudCastle** |
| **Standalone без фреймворка** | ✅ | ✅ | ⚠️ | ✅ | ⚠️ | ✅ | ✅ | **CloudCastle**, PHP-DI, Pimple, **League**, **Nette** |
| **Зрелость / community** | ⚠️ | ✅ | ✅✅ | ✅ | ✅✅ | ✅ | ✅ | **Symfony**, **Laravel** |
| **Открытый CI + benchmark-check** | ✅ | ⚠️ | ⚠️ | ❌ | ⚠️ | ❌ | ⚠️ | **CloudCastle DI** |

### Регистрация и жизненный цикл

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **Явная регистрация** | ✅ `set()` | ✅ | ✅ config | ✅ | ✅ | ✅ `add()` | ✅ config | **Паритет** |
| **Фабрики (callable)** | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | **Паритет** |
| **Singleton-кэш** | ✅ | ✅ | ✅ shared | ✅ | ✅ | ✅ shared | ✅ | **Паритет** |
| **Прототипы (`make`)** | ✅ | ✅ | ✅ | ⚠️ | ✅ | ⚠️ | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **Alias** | ✅ | ✅ | ✅ | ⚠️ | ✅ | ✅ | ✅ | **Паритет** |
| **`hasDefinition()` без создания** | ✅ | ⚠️ | ✅ | ❌ | ⚠️ | ⚠️ | ✅ | **CloudCastle**, **Symfony**, **Nette** |
| **`freeze()` после bootstrap** | ✅ | ⚠️ | ✅ compile | ❌ | ❌ | ❌ | ✅ compile | **CloudCastle**, **Symfony**, **Nette** |
| **`dump()` / интроспекция** | ✅ | ⚠️ | ✅ | ❌ | ⚠️ | ⚠️ | ✅ | **Symfony**, **Nette** |

### Autowiring и reflection

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **Constructor autowiring** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** (кроме Pimple) |
| **Property injection** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ inflectors | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **Method / setter injection** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ inflectors | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **PHP Attributes** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** (кроме Pimple) |
| **Свои attributes** | ✅ | ⚠️ | ✅ | ❌ | ✅ | ⚠️ | ✅ | **CloudCastle**, Symfony, Laravel, **Nette** |
| **Autowiring по имени параметра** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** (кроме Pimple) |
| **Union / nullable** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** (кроме Pimple) |
| **Intersection types** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **Детекция циклов** | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ | **Паритет** |
| **Autoconfigure / `_instanceof`** | ❌ | ⚠️ | ✅ | ❌ | ✅ | ❌ | ✅ extensions | **Symfony**, Laravel, **Nette** |

### Сканирование и конфигурация

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **Сканирование каталога** | ✅ regex | ✅ | ✅ Resource | ❌ | ⚠️ | ❌ | ✅ RobotLoader | **Symfony**, **Nette** |
| **Декларативный конфиг PHP** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ | ✅ | **Паритет** |
| **JSON / YAML / XML** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ league-config | ✅ NEON | **Symfony**, **Nette** |
| **Каталог конфигов (v1.7)** | ✅ | ⚠️ | ✅ | ❌ | ✅ | ❌ | ✅ | **CloudCastle**, Symfony, **Nette** |
| **Приоритеты слоёв** | ✅ | ⚠️ | ✅ | ❌ | ✅ | ⚠️ | ✅ | **Symfony**, **CloudCastle**, **Nette** |
| **Compiled container (prod)** | ❌ v2 | ✅ | ✅ | ❌ | ✅ | ❌ | ✅ | **PHP-DI**, **Symfony**, Laravel, **Nette** |

### Расширенный API

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **`bind()` интерфейс → класс** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ definitions | ✅ | **Паритет** (кроме Pimple) |
| **`call()` с autowire** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ delegate | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **After-resolve hooks** | ✅ | ✅ | ✅ | ❌ | ⚠️ | ⚠️ inflectors | ✅ setup | **CloudCastle**, PHP-DI, Symfony, **Nette** |
| **Bulk definitions** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** |
| **Tagged services** | ✅ | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **Паритет** |
| **Tagged iterator / locator** | ✅ | ✅ | ✅ | ❌ | ⚠️ | ⚠️ | ✅ | **CloudCastle**, PHP-DI, Symfony, **Nette** |
| **Декораторы** | ✅ | ✅ | ✅ | ❌ | ✅ | ⚠️ extend | ✅ | **CloudCastle**, PHP-DI, Symfony, Laravel, **Nette** |
| **Lazy loading** | ✅ | ✅ proxy | ✅ ghost | ❌ | ✅ | ⚠️ | ✅ | **Symfony** (ghost) |
| **Contextual binding** | ❌ v2 | ✅ | ✅ | ❌ | ✅ | ✅ | ✅ | **PHP-DI**, Symfony, Laravel, **League**, **Nette** |
| **Scopes (request и т.д.)** | ❌ v2 | ⚠️ | ✅ | ❌ | ✅ | ❌ | ⚠️ | **Symfony**, **Laravel** |

### Интеграция и прочее

| Функция | CloudCastle DI | PHP-DI | Symfony DI | Pimple | Laravel | League | Nette DI | 🏆 Победитель |
|---------|:--------------:|:------:|:----------:|:------:|:-------:|:------:|:--------:|---------------|
| **Глобальный реестр** | ✅ | ❌ | ❌ | ❌ | ✅ Facades | ❌ | ❌ | **Laravel** |
| **Service providers / extensions** | ❌ | ⚠️ | ✅ bundles | ❌ | ✅ | ❌ | ✅ | **Symfony**, Laravel, **Nette** |
| **Интеграция с kernel** | ❌ | ❌ | ✅ | ❌ | ✅ | ❌ | ✅ | **Symfony**, Laravel, **Nette** |
| **Простота API** | ⚠️ | ⚠️ | ❌ | ✅✅ | ⚠️ | ✅ | ⚠️ | **Pimple** |
| **Компактность / аудит кода** | ✅ | ⚠️ | ❌ | ✅✅ | ❌ | ✅ | ⚠️ | **Pimple**, **CloudCastle**, **League** |

---

## ✨ Когда выбирать CloudCastle DI

<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="96">
</p>

| ✅ Подходит | ❌ Лучше другой вариант |
|-------------|-------------------------|
| Composition root в **библиотеке**, CLI, API | Уже **Symfony** / **Laravel** / **Nette** |
| Autowiring + теги **без** фреймворка | **Compiled** + **contextual** **сейчас** → PHP-DI, Symfony, **Nette** |
| Граф **~10–500** сервисов | **Legacy PHP &lt; 8.1** |
| **Одна** зависимость `psr/container` | 3–5 `set()` без autowire → **Pimple** |
| Декларативный конфиг опционален | Definitions-first → **League Container** |

---

## 🔄 Миграция (кратко)

| Из | Действия |
|----|----------|
| **Pimple** | `$p['id'] = fn` → `set()`; `enableAutowiring()` для FQCN |
| **PHP-DI** | definitions → `set()` / `bind()` / `ContainerConfigurator`; убрать compiler |
| **Symfony** | `services.yaml` → `ContainerConfigurator` или PHP bootstrap |
| **Laravel** | providers → composition root; contextual → `bind()` |
| **League** | `add()` / definitions → `set()` / `bind()`; inflectors → `afterResolving()` |
| **Nette** | NEON → PHP/YAML/XML через `ContainerConfigurator`; extensions → `tag()` / bootstrap |

Подробнее — [Быстрый старт](Quick-start), [Конфигурация](Configuration), [Обновление версий](Upgrading).

---

## ⚖️ Итог: CloudCastle DI

### Преимущества

| | |
|---|---|
| 🪶 | Одна runtime-зависимость — `psr/container` |
| 🔧 | Autowiring: constructor, property, method, attributes |
| 📁 | `scan()`, конфиг PHP/JSON/YAML/XML, каталоги (v1.7) |
| 🏷️ | Теги, iterator, locator, декораторы, `call()`, `bind()` |
| 🧊 | `freeze()`, `dump()`, `ContainerRegistry` |
| 🧪 | 506 тестов, benchmark-check в CI |

### Ограничения (v1.x)

| | |
|---|---|
| 🚧 | Нет compiled container — [v2 #24](https://github.com/cloudcastle-apps/di/issues/24) |
| 🚧 | Нет contextual binding — [v2 #25](https://github.com/cloudcastle-apps/di/issues/25) |
| 📌 | `scan()` — regex, не AST |
| 👥 | Меньше community, чем PHP-DI / Symfony / **Nette** |

---

## 📈 Производительность

Для **десятков–сотен** `get()` CloudCastle DI сопоставим с reflection-контейнерами. На **очень больших** графах compiled **Symfony** / **PHP-DI** / **Nette** быстрее.

Цифры — [Нагрузка и производительность](Performance-and-load).

---

## 🔗 См. также

- [FAQ](FAQ)
- [Архитектура](Architecture)
- [Анти-паттерны](Anti-patterns)
- [Roadmap v2](https://github.com/cloudcastle-apps/di/issues/17) · [#24 compiled container](https://github.com/cloudcastle-apps/di/issues/24)
