Autowiring
==========

Autowiring создаёт экземпляры через reflection. Id сервиса — **FQCN**.

Порядок внедрения
-----------------

``Autowirer`` выполняет:

1. **Конструктор** — типы, attributes, имя параметра (опционально)
2. **Свойства** — attributes всегда; typed properties при ``enablePropertyAutowiring()``
3. **Методы** — attributes всегда; setter/inject-методы при ``enableMethodAutowiring()``

Включение
---------

.. code-block:: php

   $container->enableAutowiring();
   $container->enableParameterNameAutowiring(); // опционально
   $container->enablePropertyAutowiring();      // опционально
   $container->enableMethodAutowiring();        // опционально

   $service = $container->get(App\Service\UserService::class);

Точечная регистрация: ``autowire(FQCN)`` — работает без глобального autowiring.

Autowiring свойств
------------------

Attributes на свойстве — **всегда**:

.. code-block:: php

   use CloudCastle\DI\Attribute\Inject;

   final class Handler
   {
       #[Inject('app.clock')]
       private ClockInterface $clock;
   }

Typed property без attribute — только с ``enablePropertyAutowiring()``:

.. code-block:: php

   final class Handler
   {
       private LoggerInterface $logger;
   }

   $container->enablePropertyAutowiring();

Пропускаются: promoted properties, уже инициализированные, static, untyped без attribute.

Autowiring методов
------------------

Attribute на методе или параметре — **всегда**:

.. code-block:: php

   #[Inject]
   protected function setClock(ClockInterface $clock): void
   {
       $this->clock = $clock;
   }

Setter без attribute — при ``enableMethodAutowiring()``:

.. code-block:: php

   public function setMailer(MailerInterface $mailer): void
   {
       $this->mailer = $mailer;
   }

Пропускаются: constructor, destructor, magic, static, методы родителя, методы без параметров.

Порядок разрешения значений
---------------------------

1. ``#[Inject]`` / ``#[Autowire]`` с явным id
2. Имя параметра как id (если ``enableParameterNameAutowiring()``)
3. Reflection-тип (union, intersection, named, nullable, default для параметров)

Attributes
----------

``Inject`` и ``Autowire`` — на параметрах конструктора, **свойствах**, **методах** и параметрах методов.

Разрешение по типам
-------------------

* **Object-типы** — рекурсивный ``get()`` / autowiring
* **ContainerInterface / PSR-11** — текущий контейнер
* **Nullable** — ``null``, если зависимость недоступна
* **Default value** — для параметров, если ``hasDefinition()`` = false
* **Union** — первый разрешимый не-builtin вариант
* **Intersection** (``Iterator&Countable``) — кандидат, удовлетворяющий всем типам
* **Builtin** — только default (параметры) или исключение (свойства)

Приоритеты
----------

1. Singleton-кэш
2. Явный ``set()``
3. Attributes с id
4. Имя параметра (если включено)
5. ``autowire()`` / глобальный autowiring

Циклические зависимости при autowiring → ``ContainerException``.

Подробные примеры — Wiki «Autowiring».
