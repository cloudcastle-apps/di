Быстрый старт
=============

Установка
---------

.. code-block:: bash

   composer require cloudcastle/di

Минимальный пример
------------------

.. code-block:: php

   <?php

   use CloudCastle\DI\Container;

   $container = new Container();

   $container->set('config.timezone', 'Europe/Moscow');
   $container->set('logger', new Psr\Log\NullLogger());

   $timezone = $container->get('config.timezone');
   $logger = $container->get('logger');

Идентификаторы сервисов — произвольные строки. Контейнер **не** выполняет автозагрузку классов по имени класса.

PSR-11
------

``CloudCastle\DI\Container`` реализует ``Psr\Container\ContainerInterface`` и расширенный ``CloudCastle\DI\Contract\ContainerInterface`` с методами ``set()`` и ``hasDefinition()``.

Проверка наличия сервиса:

.. code-block:: php

   if ($container->has('logger')) {
       $logger = $container->get('logger');
   }

``hasDefinition()`` проверяет регистрацию **без** создания экземпляра (удобно для фабрик).
