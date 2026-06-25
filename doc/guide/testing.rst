Тестирование с контейнером
==========================

Unit-тесты
----------

Создавайте новый ``Container`` в каждом тесте — экземпляры не разделяют состояние:

.. code-block:: php

   final class OrderServiceTest extends TestCase
   {
       public function testCreatesOrder(): void
       {
           $container = new Container();
           $container->set('clock', new FixedClock('2026-01-01'));
           $container->set(
               'orders',
               static fn (ContainerInterface $c): OrderService => new OrderService($c->get('clock')),
           );

           $service = $container->get('orders');
           // ...
       }
   }

Подмена зависимостей
--------------------

Зарегистрируйте тестовые double **до** первого ``get()``:

.. code-block:: php

   $container->set('mailer', $this->createMock(MailerInterface::class));

После ``get()`` замена через ``set()`` создаст новый singleton при следующем ``get()``.

Интеграционные тесты
--------------------

Для сценария «боевой» конфигурации вынесите регистрацию сервисов в функцию или класс:

.. code-block:: php

   function createApplicationContainer(): Container
   {
       $container = new Container();
       // register services...
       return $container;
   }

Так проще переиспользовать wiring в нескольких тестах.

Проверки в проекте
------------------

.. code-block:: bash

   composer test:unit
   composer test:integration
   composer ci

См. также файл ``CONTRIBUTING.md`` в корне репозитория.
