Глобальный реестр
=================

``CloudCastle\DI\ContainerRegistry`` — singleton-контейнер приложения.

.. code-block:: php

   ContainerRegistry::set($container);
   $service = ContainerRegistry::get()->get(OrderService::class);

API
---

* ``set(ContainerInterface)`` — регистрация
* ``get(): ContainerInterface`` — доступ (``ContainerException`` до ``set()``)
* ``has(): bool``
* ``reset(): void`` — изоляция тестов

Рекомендации
------------

Инициализируйте в bootstrap. В PHPUnit вызывайте ``reset()`` в ``tearDown``. В новом коде предпочитайте явную передачу зависимостей — см. :doc:`anti-patterns`.
