Сравнение с аналогами
=====================

Краткая выжимка. **Полная таблица** — функция → CloudCastle → **5 аналогов**
(PHP-DI, Symfony DI, Pimple, Laravel, Nette DI) → 🏆 победитель —
`Wiki: Comparison <https://github.com/cloudcastle-apps/di/wiki/Comparison>`_.

Аналоги
-------

#. `PHP-DI <https://php-di.org/>`_
#. `Symfony DependencyInjection <https://symfony.com/doc/current/service_container.html>`_
#. `Pimple <https://pimple.symfony.com/>`_
#. `Laravel Container <https://laravel.com/docs/container>`_
#. `Nette DI <https://doc.nette.org/en/configuring>`_

Легенда
-------

.. list-table::
   :header-rows: 1
   :widths: 10 90

   * - Символ
     - Значение
   * - ✅
     - Полная поддержка
   * - ⚠️
     - Частично / с ограничениями
   * - ❌
     - Нет
   * - 🔌
     - Через адаптер

Когда выбрать CloudCastle DI
----------------------------

**CloudCastle DI** — composition root в библиотеке, CLI, API или тестах: autowiring, конфиг из файлов,
теги и декораторы при **одной** runtime-зависимости ``psr/container``.

**Другой контейнер:**

- уже **Symfony** / **Laravel** / **Nette** → встроенный DI;
- **3–5** сервисов без autowire → **Pimple**;
- **compiled container** или **contextual binding** прямо сейчас → **PHP-DI**, **Symfony DI** или **Nette DI**.

Сводка (фрагмент)
-----------------

.. list-table::
   :header-rows: 1
   :widths: 22 9 9 9 9 9 9 15

   * - Функция
     - CloudCastle
     - PHP-DI
     - Symfony
     - Pimple
     - Laravel
     - Nette
     - Победитель
   * - PSR-11
     - ✅
     - ✅
     - ✅
     - 🔌
     - ✅
     - 🔌
     - Паритет
   * - Autowiring
     - ✅
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
     - Паритет
   * - Compiled / contextual
     - ⚠️ v2
     - ✅
     - ✅
     - ❌
     - ✅
     - ✅
     - PHP-DI, Symfony, Nette
   * - Benchmark-check CI
     - ✅
     - —
     - —
     - —
     - —
     - —
     - CloudCastle DI

Полная таблица (~45 строк, 5 аналогов) — на Wiki.

См. также
---------

- `Анти-паттерны <anti-patterns.rst>`_
- `Архитектура <architecture.rst>`_
- `Нагрузка и производительность <load-performance.rst>`_
