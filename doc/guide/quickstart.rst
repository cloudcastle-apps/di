Быстрый старт
=============

Требования
----------

- PHP ^8.3
- ``psr/container`` ^2.0 (подтягивается автоматически)

Установка
---------

.. code-block:: bash

   composer require cloudcastle/di

Минимальный пример
------------------

.. code-block:: php

   <?php

   declare(strict_types=1);

   use CloudCastle\DI\Container;

   $container = new Container();
   $container->set('logger', new Psr\Log\NullLogger());
   $logger = $container->get('logger');

Autowiring
----------

.. code-block:: php

   $container->enableAutowiring();
   $service = $container->get(App\Service\UserService::class);

Attributes ``Inject`` / ``Autowire`` и intersection-типы — см. :doc:`autowiring`.

Property и method injection
^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   $container->enablePropertyAutowiring();
   $container->enableMethodAutowiring();

   // #[Inject] на свойстве или inject-методе — без этих флагов

Сканирование каталога
---------------------

.. code-block:: php

   $container->scan(__DIR__ . '/Services', 'App\\Services\\');

Подробнее — :doc:`class-scanning`.

Прототипы, alias и lazy
-----------------------

.. code-block:: php

   $container->make(Job::class); // без singleton-кэша
   $container->alias(LoggerInterface::class, 'logger');
   $container->set('reports', $container->lazy(ReportGenerator::class));

Глобальный реестр
-----------------

.. code-block:: php

   use CloudCastle\DI\ContainerRegistry;

   ContainerRegistry::set($container);
   $service = ContainerRegistry::get()->get(App\Service\UserService::class);

См. :doc:`global-registry`.

Дальше
------

* :doc:`autowiring`
* :doc:`configuration`
* :doc:`class-scanning`
* :doc:`factories`
* :doc:`tags-decorators`
* :doc:`testing`
