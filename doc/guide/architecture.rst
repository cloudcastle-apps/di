Архитектура
===========

Подробные **Mermaid-диаграммы** (компоненты, bootstrap, ``get``/``make``, autowiring, configuration, freeze, scan, alias, bind, lazy, теги, декораторы, ``registerAttribute``) опубликованы в GitHub Wiki:

`Архитектура <https://github.com/cloudcastle-apps/di/wiki/Architecture>`_

Краткая схема потока:

.. code-block:: text

   ┌─ Ручной bootstrap ─────────────────────────────────────────┐
   │ set / bind / autowire / scan / registerAttribute / tag     │
   └────────────────────────────┬───────────────────────────────┘
                                │
   ┌─ Декларативный v1.5 ───────┤
   │ ContainerConfigurator      │
   │  PHP / JSON / YAML / XML   │
   │  → merge → apply           │
   └────────────────────────────┼───────────────────────────────┘
                                ▼
                    опционально freeze()
                                │
                                ▼
   get() / make() / call() → ServiceAliasResolver → ServiceInstanceResolver
                                │                           │
                                │           ┌───────────────┼───────────────┐
                                │           ▼               ▼               ▼
                                │      definitions    Autowirer         decorators
                                │           │               │
                                │           └───────► afterResolving (если новый)
                                │                       resolved (singleton)

Autowiring внутри ``Autowirer``: конструктор → свойства → методы; каждая зависимость снова вызывает ``get()``.

Пользовательские attributes: ``registerAttribute()`` → ``AttributeServiceIdRegistry`` → ``MemberResolver``.

См. также :doc:`quickstart`, :doc:`autowiring`, :doc:`configuration`, :doc:`class-scanning`.
