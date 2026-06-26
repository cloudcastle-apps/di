Сканирование классов
====================

``Container::scan($directory, $namespace?)`` регистрирует instantiable-классы через ``autowire()``.

.. code-block:: php

   $container->scan(__DIR__ . '/Services', 'App\\Services\\');

Поведение
---------

* Рекурсивный обход ``.php`` файлов (``ClassScanner``).
* Парсинг ``namespace``, нескольких ``class`` и ``enum`` без выполнения файла.
* Фильтр по префиксу namespace (trailing ``\\`` нормализуется).
* Только instantiable-классы после ``class_exists()`` (нужен PSR-4 autoload).
* Существующие ``set(FQCN)`` **не перезаписываются**.

Ограничения
-----------

* ``enum`` парсятся, но **не регистрируются** (не instantiable).
* Anonymous classes и файлы без классов пропускаются.
* Не сканируйте ``vendor/`` без фильтра namespace.

Явные ``set()`` после ``scan()`` — для интерфейсов и переопределений.
