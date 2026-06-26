Нагрузка, производительность и безопасность
==========================================

Краткая выжимка. Подробные таблицы по каждому тесту — Wiki:

- `Security-tests <https://github.com/cloudcastle-apps/di/wiki/Security-tests>`_
- `Performance-and-load <https://github.com/cloudcastle-apps/di/wiki/Performance-and-load>`_

Безопасность (17 тестов)
-----------------------

``composer test:security`` — отсутствие «фантомных» регистраций, повтор фабрики и hook
``afterResolving`` после исключения, непрозрачные id (в т.ч. null-byte), безопасные
сообщения ``NotFoundException``, autowiring (disabled / abstract / interface), циклы alias.

Нагрузочные (15 тестов)
-----------------------

``composer test:load`` — 1000–2000 сервисов: ``set``/``get``, singleton, alias, decorate,
``addDefinitions``, ``bind``, ``make``, ``call``, ``afterResolving``, tagged API. Пороги до 3 с.

Производительность (12 тестов)
------------------------------

``composer test:performance`` — 1000–10000 итераций ``get``, ``has``, ``set``, ``make``,
``call``, ``bind``, autowire (warm/cold). Пороги 0.35–1.5 с.

Бенчмарки
---------

.. code-block:: bash

   composer benchmark-report   # фактические времена (markdown)
   composer ci                 # все наборы + coverage + mutation

См. также
---------

- `Тестирование <testing.rst>`_ (unit/integration)
- `CONTRIBUTING.md <https://github.com/cloudcastle-apps/di/blob/main/CONTRIBUTING.md>`_
