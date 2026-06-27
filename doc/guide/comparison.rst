Сравнение с аналогами
=====================

Краткая выжимка. **Пошаговое** сравнение с плюсами и минусами по каждому критерию —
`Wiki: Comparison <https://github.com/cloudcastle-apps/di/wiki/Comparison>`_.

Преимущества
------------

- Одна runtime-зависимость — ``psr/container``.
- PSR-11, autowiring (constructor, property, method), attributes, ``scan()``, теги, декораторы.
- Декларативная конфигурация PHP/JSON/YAML/XML (``ContainerConfigurator``, v1.5+).
- Явный bootstrap в PHP — без обязательного compiled container.
- **506** PHPUnit-тестов, per-file coverage, Infection MSI ≥95% по ``src/``, ``benchmark-check`` в CI (v1.7+).
- Подходит для библиотек, CLI, API bootstrap и тестов.

Недостатки
----------

- Нет **compiled container** и **contextual binding** (план v2, issues #24/#25) — сейчас лучше PHP-DI / Symfony DI.
- Только **PHP ^8.3**; ``scan()`` — regex, не полный AST.
- Меньше экосистемы, чем у Symfony / Laravel / PHP-DI.

Когда выбрать
-------------

**CloudCastle DI** — если нужен компактный composition root без фреймворка, с autowiring и опциональной конфигурацией из файлов.

**Другой контейнер** — если уже Symfony/Laravel, нужен compiler/contextual binding сейчас,
или достаточно Pimple на 3–5 сервисов.

См. также
---------

- `Анти-паттерны <anti-patterns.rst>`_
- `Архитектура <architecture.rst>`_
- `Нагрузка и производительность <load-performance.rst>`_
