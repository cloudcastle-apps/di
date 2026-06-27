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

ContainerRegistry
-----------------

Если тесты используют глобальный реестр, сбрасывайте его в ``tearDown``:

.. code-block:: php

   protected function tearDown(): void
   {
       ContainerRegistry::reset();
       parent::tearDown();
   }

Autowiring в тестах
-------------------

.. code-block:: php

   $container->enableAutowiring();
   $container->set(ClockInterface::class, new FixedClock());

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
   composer test:security      # 17 тестов безопасности
   composer test:load          # 15 нагрузочных
   composer test:performance   # 12 performance
   composer test:coverage      # ≥95% строк (~98% фактически)
   composer test:mutation      # Infection, MSI ≥95% по src/
   composer benchmark-report   # фактические бенчмарки
   composer benchmark-check    # регрессия (CI)
   composer ci

**Всего:** 470 PHPUnit-тестов (unit 421, integration 5, security 17, load 15, performance 12).
(Security-tests, Performance-and-load).

См. также файл ``CONTRIBUTING.md`` в корне репозитория.
