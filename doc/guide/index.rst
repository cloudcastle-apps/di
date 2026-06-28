CloudCastle DI
==============

.. image:: https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/docs-hero.svg
   :alt: CloudCastle DI
   :align: center
   :width: 100%

Руководство пользователя **cloudcastle/di** — лёгкий PSR-11 контейнер для **PHP 8.1+**.

**Возможности:** autowiring (constructor, property, method), конфигурация PHP/JSON/YAML/XML,
``scan()``, прототипы ``make()``, alias, lazy, ``call()`` / ``bind()``, after-resolving hooks,
tagged services, декораторы, ``freeze()``, compiled container (v1.9), contextual binding контракты (v1.10), ``ContainerRegistry``.

**Документация онлайн:** `Wiki <https://github.com/cloudcastle-apps/di/wiki/Home>`_ ·
`Сравнение с аналогами <https://github.com/cloudcastle-apps/di/wiki/Comparison>`_.

.. toctree::
   :maxdepth: 2
   :caption: Содержание

   architecture
   quickstart
   configuration
   autowiring
   class-scanning
   global-registry
   tags-decorators
   factories
   testing
   anti-patterns
   comparison
   compiled-container
   load-performance
