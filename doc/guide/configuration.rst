Конфигурация из файлов
======================

Опциональная загрузка определений через ``ContainerConfigurator`` (PHP, JSON, YAML, XML).
Контейнер можно собирать и без конфигурационных файлов — только через API.

Быстрый старт
-------------

.. code-block:: php

   use CloudCastle\DI\Configuration\ContainerConfigurator;
   use CloudCastle\DI\Container;

   $container = new Container();
   (new ContainerConfigurator())->configure($container, [
       __DIR__ . '/config/services.php',
       __DIR__ . '/config/override.json',
   ]);

Приоритеты
----------

1. ``priority`` у параметра
2. Приоритет файла (``ConfigurationSource`` или ключ ``priority`` в конфиге)
3. Порядок в списке — последний побеждает

YAML требует расширение ``ext-yaml``.

Схемы загрузки, слияния и ``apply`` — Wiki `Architecture#конфигурация-загрузка-слияние-применение-v15` и `Configuration`.
