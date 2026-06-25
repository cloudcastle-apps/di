<?php

declare(strict_types=1);

namespace CloudCastle\DI\Contract;

use Psr\Container\ContainerInterface as PsrContainerInterface;

/**
 * Контракт DI-контейнера CloudCastle, расширяющий PSR-11.
 *
 * Поверх {@see PsrContainerInterface} добавляет регистрацию сервисов ({@see set()}),
 * группировку по тегам, декораторы, autowiring и сканирование каталогов.
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
}
