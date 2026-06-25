Теги и декораторы
=================

Tagged services
---------------

.. code-block:: php

   $container->tag('logger.app', 'loggers');
   $container->tag('logger.audit', 'loggers');

   foreach ($container->getTagged('loggers') as $id => $logger) {
       // ...
   }

Порядок — порядок вызовов ``tag()``. Id без definition/autowiring пропускаются.

Декораторы
----------

.. code-block:: php

   $container->decorate('api', static fn ($inner, $c) => new RetryApiClient($inner));
   $container->decorate('api', static fn ($inner, $c) => new LoggingApiClient($inner, $c->get('logger')));

Сигнатура: ``(mixed $inner, ContainerInterface $container): mixed``.

Первый декоратор ближе к inner. ``decorate()`` сбрасывает singleton-кэш id.
