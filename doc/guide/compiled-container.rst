Compiled container
====================

С v1.9.0 пакет поддерживает **compiled container** — генерацию PHP-класса wiring из
замороженного runtime-контейнера без reflection на hot path ``get()``.

Полное руководство — `Wiki: Compiled container
<https://github.com/cloudcastle-apps/di/wiki/Compiled-container>`_.

Минимальный пример
------------------

.. code-block:: php

   use CloudCastle\DI\Compiler\ContainerCompiler;
   use CloudCastle\DI\Container;

   $container = new Container();
   $container->autowire(App\Service\OrderService::class);
   $container->freeze();

   (new ContainerCompiler())->compile(
       $container,
       __DIR__ . '/var/compiled/AppContainer.php',
   );

Поддерживается
--------------

- ``set()`` — literal и prebuilt object без аргументов конструктора
- ``autowire()`` — только constructor injection
- ``alias()``, ``tag()``

Не поддерживается в compiled
----------------------------

- Callable-фабрики ``set('id', fn () => …)``
- Глобальный ``enableAutowiring()``
- Property / method injection, ``decorate()``, ``afterResolving()``
- Contextual binding (v2, `#25 <https://github.com/cloudcastle-apps/di/issues/25>`_)

См. также
---------

- `API reference (compiled) <https://github.com/cloudcastle-apps/di/wiki/API-reference#compiled-container-v19>`_
- `Upgrading 1.8 → 1.9 <https://github.com/cloudcastle-apps/di/wiki/Upgrading>`_
