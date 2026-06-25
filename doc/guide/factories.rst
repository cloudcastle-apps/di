Фабрики и singleton
===================

Готовый экземпляр
-----------------

.. code-block:: php

   $container->set('mailer', new Mailer($dsn));

Фабрика (callable)
------------------

Если передан ``callable``, он вызывается один раз; результат кэшируется до следующего ``set()``:

.. code-block:: php

   use CloudCastle\DI\Contract\ContainerInterface;

   $container->set(
       'repository',
       static function (ContainerInterface $container): UserRepository {
           return new UserRepository($container->get('pdo'));
       },
   );

В фабрику передаётся сам контейнер — так строятся цепочки зависимостей.

Поддерживаемые callable
-----------------------

* замыкание ``fn () => ...``;
* объект с ``__invoke``;
* first-class callable ``$factory->create(...)``;
* массив ``[$object, 'method']`` (если не перепутать с data-массивом сервиса).

Повторная регистрация
---------------------

``set()`` с тем же id **сбрасывает** ранее созданный singleton:

.. code-block:: php

   $container->set('token', 'dev');
   $container->set('token', 'prod'); // get('token') вернёт 'prod'

Ограничения
-----------

* Значение ``null`` через ``set('id', null)`` не распознаётся как регистрация (``isset`` в PHP).
* Фабрика, возвращающая ``null``, **не** кэшируется — при каждом ``get()`` фабрика вызывается снова.
* Циклические зависимости в **фабриках** (A → B → A) не обнаруживаются; при **autowiring** — ``ContainerException``.
