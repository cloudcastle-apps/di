Autowiring
----------

Attributes ``Inject`` / ``Autowire`` и intersection-типы — см. :doc:`autowiring`.

Property и method injection
^^^^^^^^^^^^^^^^^^^^^^^^^^^

.. code-block:: php

   $container->enablePropertyAutowiring();
   $container->enableMethodAutowiring();

   // #[Inject] на свойстве или inject-методе — без этих флагов

Сканирование каталога