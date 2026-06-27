Быстрый старт
=============

Требования
----------

- PHP ^8.1 (CI: 8.1–8.5)
- ``psr/container`` ^2.0 (подтягивается автоматически)
- Опционально: ``ext-yaml`` — YAML-конфигурация

Полное руководство — `Wiki: Quick start <https://github.com/cloudcastle-apps/di/wiki/Quick-start>`_.

Установка
---------

.. code-block:: bash

   composer require cloudcastle/di:^1.8

Минимальный пример
------------------

.. code-block:: php

   <?php

   declare(strict_types=1);

   use CloudCastle\DI\Container;

   $container = new Container();
   $container->enableAutowiring();
   $container->bind(LoggerInterface::class, FileLogger::class);
   $service = $container->get(App\Service\UserService::class);

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

Конфигурация из файлов
----------------------

.. code-block:: php

   use CloudCastle\DI\Configuration\ContainerConfigurator;

   (new ContainerConfigurator())->configure($container, [
       __DIR__ . '/config/services.php',
   ]);
   $container->freeze();

См. :doc:`configuration`.

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

* :doc:`comparison` — таблица vs 5 аналогов (PHP-DI, Symfony, Pimple, Laravel, Nette)
* :doc:`autowiring`
* :doc:`configuration`
* :doc:`class-scanning`
* :doc:`factories`
* :doc:`tags-decorators`
* :doc:`testing`
