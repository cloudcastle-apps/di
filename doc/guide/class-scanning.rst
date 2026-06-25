Сканирование классов
====================

``Container::scan($directory, $namespace?)`` регистрирует instantiable-классы через ``autowire()``.

.. code-block:: php

   $container->scan(__DIR__ . '/Services', 'App\\Services\\');

Поведение
---------

* Рекурсивный обход ``.php`` файлов (``ClassScanner``).
* Парсинг ``namespace`` и ``class`` без выполнения файла.
* Фильтр по префиксу namespace (trailing ``\\`` нормализуется).
* Только instantiable-классы после ``class_exists()`` (нужен PSR-4 autoload).
* Существующие ``set(FQCN)`` **не перезаписываются**.

Ограничения
-----------

* Не находит ``enum``, несколько классов в файле, anonymous classes.
* Не сканируйте ``vendor/`` без фильтра namespace.

Явные ``set()`` после ``scan()`` — для интерфейсов и переопределений.
