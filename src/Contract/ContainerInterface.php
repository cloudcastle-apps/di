<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use CloudCastle\DI\LazyService;
use CloudCastle\DI\TaggedServiceIterator;
use CloudCastle\DI\TaggedServiceLocator;
use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Контракт DI-контейнера CloudCastle, расширяющий PSR-11.
 *
 * Поверх {@see PsrContainerInterface} добавляет регистрацию сервисов ({@see set()}),
 * группировку по тегам, декораторы, autowiring, сканирование каталогов, вызов callable ({@see call()}),
 * привязку абстракций ({@see bind()}), массовую регистрацию ({@see addDefinitions()})
 * и пост-обработку resolve ({@see afterResolving()}).
 *
 * Идентификатор сервиса — произвольная строка или FQCN при autowiring.
 * Явная регистрация через {@see set()} имеет приоритет над autowiring для того же id.
 *
 * @see \CloudCastle\DI\Container Реализация по умолчанию
 */
interface ContainerInterface extends PsrContainerInterface
{
    /**
     * Регистрирует фабрику или готовый экземпляр по идентификатору.
     *
     * Если передан `callable`, он вызывается при первом {@see get()} с текущим контейнером
     * в аргументе; результат кэшируется как singleton до следующего {@see set()} или {@see decorate()}.
     *
     * Значение `null` через `set('id', null)` не считается регистрацией (ограничение `isset()` в PHP).
     *
     * @param string $id Идентификатор сервиса (строка или FQCN)
     * @param mixed $concrete Фабрика `callable(self): mixed` или готовый экземпляр/скаляр
     */
    public function set(string $id, mixed $concrete): void;

    /**
     * Проверяет наличие регистрации сервиса без его создания.
     *
     * Возвращает `true`, если для id вызван {@see set()} (включая callable) или {@see autowire()}.
     * Не учитывает только singleton-кэш без definition и не означает, что {@see get()} уже вызывался.
     *
     * @param string $id Идентификатор сервиса
     *
     * @return bool `true`, если определение зарегистрировано явно
     */
    public function hasDefinition(string $id): bool;

    /**
     * Привязывает идентификатор сервиса к тегу для группового получения.
     *
     * Один сервис может иметь несколько тегов. Повторный вызов с тем же id и тегом
     * не добавляет дубликат в порядок {@see getTagged()}.
     *
     * @param string $id Идентификатор сервиса (должен быть доступен через {@see get()} или autowiring)
     * @param string $tag Имя тега (произвольная строка)
     */
    public function tag(string $id, string $tag): void;

    /**
     * Возвращает все сервисы с указанным тегом.
     *
     * Порядок элементов соответствует порядку вызовов {@see tag()} для этого тега.
     * Id без {@see hasDefinition()} и без возможности autowiring пропускаются без исключения.
     *
     * @param string $tag Имя тега
     *
     * @return array<string, mixed> Карта идентификатор → экземпляр; пустой массив для неизвестного тега
     */
    public function getTagged(string $tag): array;

    /**
     * Регистрирует декоратор, оборачивающий сервис при {@see get()}.
     *
     * Декораторы применяются при первом разрешении сервиса, до кэширования singleton.
     * Порядок регистрации: первый декоратор ближе к исходному (inner) экземпляру.
     * Вызов сбрасывает singleton-кэш для указанного id.
     *
     * @param string $id Идентификатор декорируемого сервиса
     * @param callable(mixed, self): mixed $decorator Функция `(mixed $inner, ContainerInterface $container): mixed`
     */
    public function decorate(string $id, callable $decorator): void;

    /**
     * Включает глобальный autowiring по FQCN при {@see get()}.
     *
     * После включения любой существующий instantiable-класс доступен по полному имени класса
     * без явного {@see set()}, если id не зарегистрирован иначе.
     */
    public function enableAutowiring(): void;

    /**
     * Отключает глобальный autowiring по FQCN.
     *
     * Классы, зарегистрированные через {@see autowire()}, остаются доступны для {@see get()}.
     */
    public function disableAutowiring(): void;

    /**
     * Проверяет, включён ли глобальный autowiring по FQCN.
     *
     * @return bool `true`, если вызван {@see enableAutowiring()} и не вызван {@see disableAutowiring()}
     */
    public function isAutowiringEnabled(): bool;

    /**
     * Включает autowiring по имени параметра конструктора (`$logger` → id `logger`).
     *
     * Применяется после PHP attributes и до разрешения по типу.
     */
    public function enableParameterNameAutowiring(): void;

    /**
     * Отключает autowiring по имени параметра.
     */
    public function disableParameterNameAutowiring(): void;

    /**
     * Проверяет, включён ли autowiring по имени параметра.
     */
    public function isParameterNameAutowiringEnabled(): bool;

    /**
     * Включает autowiring типизированных свойств (после конструктора).
     *
     * Свойства с {@see \CloudCastle\DI\Attribute\Inject} / {@see \CloudCastle\DI\Attribute\Autowire}
     * внедряются всегда; глобальный режим дополнительно обрабатывает все неинициализированные typed properties.
     */
    public function enablePropertyAutowiring(): void;

    /**
     * Отключает autowiring свойств по типу.
     */
    public function disablePropertyAutowiring(): void;

    /**
     * Проверяет, включён ли autowiring свойств по типу.
     */
    public function isPropertyAutowiringEnabled(): bool;

    /**
     * Включает autowiring параметров методов (setter и другие inject-методы).
     *
     * Методы с attributes внедряются всегда; глобальный режим вызывает все public/protected методы с параметрами.
     */
    public function enableMethodAutowiring(): void;

    /**
     * Отключает autowiring методов по умолчанию.
     */
    public function disableMethodAutowiring(): void;

    /**
     * Проверяет, включён ли autowiring методов.
     */
    public function isMethodAutowiringEnabled(): bool;

    /**
     * Регистрирует пользовательский PHP attribute для autowiring.
     *
     * Класс должен быть помечен `#[\Attribute]` и реализовывать {@see ServiceIdAttribute}.
     * После регистрации attribute обрабатывается так же, как встроенные
     * {@see \CloudCastle\DI\Attribute\Inject} и {@see \CloudCastle\DI\Attribute\Autowire}.
     *
     * @param string $attributeClass Полное имя класса attribute
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если класс не attribute или не реализует контракт
     */
    public function registerAttribute(string $attributeClass): void;

    /**
     * Регистрирует класс для autowiring по его полному имени (id = FQCN).
     *
     * Работает независимо от {@see isAutowiringEnabled()}. Сбрасывает singleton-кэш для className.
     *
     * @param string $className Полное имя класса (class-string)
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если класс не найден или не instantiable
     */
    public function autowire(string $className): void;

    /**
     * Сканирует каталог и регистрирует найденные классы через {@see autowire()}.
     *
     * Использует {@see \CloudCastle\DI\ClassScanner}: рекурсивный обход `.php`-файлов,
     * парсинг namespace/class без выполнения файла, фильтр по префиксу namespace.
     * Id с уже существующим {@see set()} не перезаписываются.
     *
     * @param string $directory Абсолютный или относительный путь к каталогу
     * @param string|null $namespace Необязательный фильтр по префиксу namespace (например `App\\Services\\`)
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если каталог не существует
     */
    public function scan(string $directory, ?string $namespace = null): void;

    /**
     * Создаёт новый экземпляр сервиса без сохранения в singleton-кэш.
     *
     * Повторные вызовы {@see make()} с тем же id возвращают разные экземпляры.
     * Декораторы применяются так же, как при {@see get()}.
     *
     * @param string $id Идентификатор сервиса или FQCN при autowiring
     *
     * @throws \CloudCastle\DI\Exception\NotFoundException Если сервис недоступен
     * @throws \CloudCastle\DI\Exception\ContainerException При ошибке autowiring или циклической зависимости
     *
     * @return mixed Новый экземпляр сервиса
     */
    public function make(string $id): mixed;

    /**
     * Регистрирует alias: обращения к `$alias` разрешаются через `$targetId`.
     *
     * @param string $alias Альтернативный идентификатор
     * @param string $targetId Целевой id сервиса
     *
     * @throws \CloudCastle\DI\Exception\ContainerException При циклической цепочке alias
     */
    public function alias(string $alias, string $targetId): void;

    /**
     * Возвращает обёртку с отложенным {@see get()} для `$serviceId`.
     *
     * Удобно регистрировать через {@see set()}: `$container->set('heavy', $container->lazy(Heavy::class))`.
     *
     * @param string $serviceId Id сервиса для отложенного разрешения
     */
    public function lazy(string $serviceId): LazyService;

    /**
     * Регистрирует несколько определений за один вызов (последовательный {@see set()}).
     *
     * Каждая пара id → concrete обрабатывается как отдельный {@see set()}: сбрасывается singleton-кэш
     * для id, явная регистрация имеет приоритет над autowiring.
     *
     * @param array<string, mixed> $definitions Карта id → экземпляр, скаляр или фабрика `callable(self): mixed`
     */
    public function addDefinitions(array $definitions): void;

    /**
     * Привязывает абстракцию к реализации одним вызовом.
     *
     * Если `$concrete` — имя **instantiable-класса**: вызываются {@see autowire()} и {@see alias()}.
     * Если `$concrete` — существующий id, интерфейс с autowiring или alias: только {@see alias()}.
     * Иначе — {@see \CloudCastle\DI\Exception\ContainerException}.
     *
     * @param string $abstract Интерфейс, абстрактный id или FQCN-алиас (то, по чему будут вызывать {@see get()})
     * @param string $concrete FQCN реализации, id зарегистрированного сервиса или интерфейс
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если concrete нельзя привязать или цикл alias
     */
    public function bind(string $abstract, string $concrete): void;

    /**
     * Начинает contextual binding: при создании {@see $consumerClass} зависимость {@see needs()} → {@see give()}.
     *
     * Аналог `when(A)->needs(B)->give(C)`. Последнее правило для пары (consumer, need) побеждает.
     * Не работает после {@see freeze()}.
     *
     * @param string $consumerClass FQCN класса-потребителя (when)
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если класс не найден или контейнер заморожен
     */
    public function when(string $consumerClass): ContextualBindingNeedsInterface;

    /**
     * Возвращает id сервиса из contextual give для пары (consumer, need) или `null`.
     *
     * @param string $consumerClass FQCN класса-потребителя
     * @param string $need FQCN типа или id зависимости (needs)
     */
    public function contextualGive(string $consumerClass, string $need): ?string;

    /**
     * Вызывает callable с autowiring параметров (те же правила, что у конструктора при {@see get()}).
     *
     * Поддерживаются closure, first-class callable, `[object, 'method']`, invokable-объекты
     * и строки с именем глобальной функции. Ключи `$parameters` — имена параметров callable.
     *
     * @param callable $callable Вызываемая функция, метод или closure
     * @param array<string, mixed> $parameters Явные аргументы по имени (переопределяют autowire)
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если обязательный параметр не разрешается
     *
     * @return mixed Результат вызова callable
     */
    public function call(callable $callable, array $parameters = []): mixed;

    /**
     * Регистрирует callback после **нового** создания сервиса при {@see get()} или {@see make()}.
     *
     * Не вызывается при повторном {@see get()}, если экземпляр уже в singleton-кэше.
     * Каждый {@see make()} создаёт новый экземпляр и снова вызывает callback.
     * Несколько callback для одного id выполняются в порядке регистрации.
     * Callback получает id, созданный экземпляр и контейнер.
     *
     * @param string $id Идентификатор сервиса (как в {@see get()})
     * @param callable(string, mixed, self): void $callback
     */
    public function afterResolving(string $id, callable $callback): void;

    /**
     * Возвращает id сервисов с тегом **без** вызова {@see get()} (без создания экземпляров).
     *
     * @param string $tag Имя тега
     *
     * @return list<string> Id в порядке {@see tag()}; пустой список для неизвестного тега
     */
    public function getTaggedIds(string $tag): array;

    /**
     * Возвращает итератор только **значений** сервисов тега (без ключей id).
     *
     * Порядок — как в {@see tag()}. Недоступные id пропускаются. При итерации вызывается {@see get()}.
     *
     * @param string $tag Имя тега
     */
    public function getTaggedIterator(string $tag): TaggedServiceIterator;

    /**
     * Возвращает locator: {@see TaggedServiceLocator::has()}, {@see TaggedServiceLocator::get()}
     * и итерация `id => instance` по тегу.
     *
     * Список id фиксируется при создании locator. Итерация делегирует {@see getTagged()}.
     *
     * @param string $tag Имя тега
     */
    public function getTaggedLocator(string $tag): TaggedServiceLocator;

    /**
     * Замораживает контейнер: запрещает изменение определений и настроек autowiring.
     *
     * {@see get()}, {@see make()} и {@see call()} продолжают работать.
     * Повторный вызов безопасен (идемпотентен).
     */
    public function freeze(): void;

    /**
     * Проверяет, заморожен ли контейнер.
     */
    public function isFrozen(): bool;

    /**
     * Возвращает все зарегистрированные id (definitions, autowire, alias).
     *
     * @return list<string> Отсортированный список без дубликатов
     */
    public function getDefinitionIds(): array;

    /**
     * Возвращает снимок состояния контейнера для отладки (без создания сервисов).
     *
     * @return array{
     *     frozen: bool,
     *     definitions: list<string>,
     *     autowired: list<string>,
     *     aliases: array<string, string>,
     *     tags: array<string, list<string>>,
     *     decorators: list<string>,
     *     resolved: list<string>,
     *     autowiring: array{
     *         enabled: bool,
     *         parameterName: bool,
     *         property: bool,
     *         method: bool
     *     }
     * }
     */
    public function dump(): array;

    /**
     * Включает или отключает сбор замеров {@see get()}, {@see make()} и {@see call()} (#65).
     *
     * Opt-in режим для dev/staging: в prod профилирование выключено по умолчанию,
     * overhead появляется только после явного вызова.
     */
    public function enableProfiling(): void;

    /**
     * Отключает профилирование без сброса накопленных замеров.
     *
     * Эквивалент повторного вызова {@see enableProfiling()} после {@see disableProfiling()}.
     */
    public function disableProfiling(): void;

    /**
     * Проверяет, включён ли сбор замеров resolve/call.
     */
    public function isProfilingEnabled(): bool;

    /**
     * Сбрасывает все накопленные замеры профилировщика.
     *
     * Не меняет состояние enabled/disabled.
     */
    public function resetProfile(): void;

    /**
     * Возвращает отчёт профилировщика: агрегаты по операции и top-N медленных resolve/call.
     *
     * Каждая запись `top_slowest` содержит:
     * - `operation` — `get`, `make` или `call`;
     * - `target` — id сервиса или описание callable;
     * - `elapsed_ms` — длительность одного вызова;
     * - `cached` — для `get`, был ли singleton уже в кэше.
     *
     * @param int $limit Максимум записей в `top_slowest`; `0` — все замеры по убыванию времени
     *
     * @return array{
     *     enabled: bool,
     *     sample_count: int,
     *     total_ms: float,
     *     by_operation: array<string, array{count: int, total_ms: float, avg_ms: float}>,
     *     top_slowest: list<array{operation: string, target: string, elapsed_ms: float, cached: bool}>
     * }
     */
    public function profileReport(int $limit = 10): array;

    /**
     * Включает object pool для {@see make()} указанного id (#63).
     *
     * Повторные {@see make()} переиспользуют экземпляры, возвращённые через {@see releaseToPool()}.
     * Перед возвратом в пул вызывается {@see \CloudCastle\DI\Contract\PoolableInterface::reset()}, если реализован.
     *
     * @param string $serviceId Id сервиса (после alias — тот же id, что передаётся в {@see make()})
     * @param int $maxSize Максимум свободных экземпляров в пуле (по умолчанию 16)
     */
    public function enablePooling(string $serviceId, int $maxSize = 16): void;

    /**
     * Отключает пул для id и удаляет накопленные свободные экземпляры.
     */
    public function disablePooling(string $serviceId): void;

    /**
     * Проверяет, включён ли object pool для id.
     */
    public function isPoolingEnabled(string $serviceId): bool;

    /**
     * Возвращает экземпляр в пул после {@see make()} с включённым pooling.
     *
     * @throws \CloudCastle\DI\Exception\ContainerException Если пул для id не включён
     */
    public function releaseToPool(string $serviceId, object $instance): void;

    /**
     * Удаляет свободные экземпляры в пуле id без отключения pooling.
     */
    public function clearPool(string $serviceId): void;

    /**
     * Удаляет все свободные экземпляры во всех включённых пулах.
     */
    public function clearAllPools(): void;

    /**
     * @return array{configured: bool, max_size: int, available: int}
     */
    public function poolStats(string $serviceId): array;
}
