Архитектура
===========

Подробные **Mermaid-диаграммы** (компоненты, ``get``/``make``, autowiring, scan, alias, lazy, теги, декораторы) опубликованы в GitHub Wiki:

`Архитектура <https://github.com/cloudcastle-apps/di/wiki/Architecture>`_

Краткая схема потока:

.. code-block:: text

   set / autowire / scan / alias
              │
              ▼
   get() / make() → ServiceAliasResolver → ServiceInstanceResolver
              │                                    │
              │                    ┌───────────────┼───────────────┐
              │                    ▼               ▼               ▼
              │               definitions    Autowirer         decorators
              │                    │               │
              │                    └───────► resolved (singleton)

Autowiring внутри ``Autowirer``: конструктор → свойства → методы; каждая зависимость снова вызывает ``get()``.

См. также :doc:`quickstart`, :doc:`autowiring`, :doc:`class-scanning`.
