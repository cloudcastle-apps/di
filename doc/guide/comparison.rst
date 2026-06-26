Сравнение с аналогами
=====================

Краткая выжимка. **Пошаговое** сравнение с плюсами и минусами по каждому критерию —
`Wiki: Comparison <https://github.com/cloudcastle-apps/di/wiki/Comparison>`_.

Преимущества
------------

- Одна runtime-зависимость — ``psr/container``.
- PSR-11, autowiring (constructor, property, method), attributes, ``scan()``, теги, декораторы.
- Явный bootstrap в PHP — без YAML и compiled container.
- Подходит для библиотек, CLI, API bootstrap и тестов.

Недостатки
----------

- Нет **compiled container** и **contextual binding** (план v2) — сейчас лучше PHP-DI / Symfony DI.
- Только **PHP ^8.3**; ``scan()`` — regex, не полный AST.
- Меньше экосистемы, чем у Symfony / Laravel / PHP-DI.

Когда выбрать
-------------

**CloudCastle DI** — если нужен компактный composition root без фреймворка.

**Другой контейнер** — если уже Symfony/Laravel, нужен compiler/contextual binding сейчас,
или достаточно Pimple на 3–5 сервисов.

См. также
---------

- `Анти-паттерны <anti-patterns.rst>`_
- `Архитектура <architecture.rst>`_
