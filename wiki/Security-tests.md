# Тесты безопасности

Каталог: `tests/Security/`. Класс: `ContainerSecurityTest` (4 теста).

Команда: `composer test:security` (входит в `composer ci`).

## Зачем нужны

Контейнер — **composition root** приложения: ошибки в кэшировании, сообщениях об ошибках и обработке id могут привести к утечке состояния, повторному использованию «битых» singleton или раскрытию внутренних путей. Security-тесты фиксируют **безопасное поведение** при сбоях resolve — не penetration-тест всего приложения.

## Что проверяется (сводка)

| # | Тест | Угроза / риск | Ожидаемое поведение |
|---|------|---------------|---------------------|
| 1 | `testMissingServiceDoesNotRegisterResolvedState` | «Фантомная» регистрация после failed `get()` | `has()` / `hasDefinition()` = false |
| 2 | `testFactoryExceptionDoesNotCachePartialInstance` | Кэширование недособранного singleton | Повторный `get()` снова вызывает фабрику |
| 3 | `testServiceIdentifierIsTreatedAsOpaqueString` | Инъекция через id (SQL-style строки) | id — непрозрачная строка, без интерпретации |
| 4 | `testNotFoundMessageContainsOnlyRequestedIdentifier` | Утечка путей в исключениях | Сообщение только с запрошенным id |

---

## Тест 1. Отсутствующий сервис не оставляет следов

**Метод:** `testMissingServiceDoesNotRegisterResolvedState`

### Шаги

1. Создать пустой `Container`.
2. Вызвать `get('missing')` в `try/catch`.
3. Поймать `NotFoundException`.
4. Проверить `has('missing') === false`.
5. Проверить `hasDefinition('missing') === false`.

### Зачем

Если бы неудачный `get()` помечал id как «существующий» или создавал пустую definition, последующие проверки `has()` вводили бы в заблуждение и могли маскировать ошибки конфигурации.

### Связь с PSR-11

`has($id)` должен отражать только **реально разрешимые** сервисы, а не факт неудачной попытки resolve.

---

## Тест 2. Исключение в фабрике не кэширует singleton

**Метод:** `testFactoryExceptionDoesNotCachePartialInstance`

### Шаги

1. Зарегистрировать `set('broken', fn () => throw new RuntimeException(...))`.
2. Дважды вызвать `get('broken')`, каждый раз ловя `RuntimeException`.
3. Убедиться, что `hasDefinition('broken')` остаётся `true`.
4. Счётчик вызовов фабрики = **2** (не 1).

### Зачем

Кэширование после исключения привело бы к «залипшему» состоянию: второй `get()` мог бы снова бросать исключение **без** повторного вызова фабрики или, наоборот, вернуть неконсистентный объект. Повторный вызов фабрики даёт шанс на recovery после исправления зависимостей (в тестах — явная семантика).

### Ограничение

Тест не покрывает частично созданный объект **до** throw внутри фабрики — ответственность автора фабрики.

---

## Тест 3. Идентификатор — непрозрачная строка

**Метод:** `testServiceIdentifierIsTreatedAsOpaqueString`

### Шаги

1. id = `"service'; DROP TABLE users; --"` (строка с кавычками и SQL-подобным текстом).
2. `set($id, $service)` и `get($id)`.
3. Вернуться должен **тот же** экземпляр `$service`.

### Зачем

Контейнер **не** парсит id как SQL, путь к файлу или шаблон. id — ключ ассоциативного хранилища. Это важно, если id приходят из конфигурации пользователя (плагины, динамические имена).

### Что не проверяется

- RCE через autowire произвольного класса (см. unit/integration + не регистрируйте опасные FQCN).
- XSS в id (id не выводится в HTML контейнером).

---

## Тест 4. Сообщение NotFound без утечки окружения

**Метод:** `testNotFoundMessageContainsOnlyRequestedIdentifier`

### Шаги

1. Запросить `get('payments.gateway')`.
2. Поймать `NotFoundException`.
3. Сообщение строго: `Сервис "payments.gateway" не зарегистрирован.`
4. В сообщении **нет** подстроки `vendor` (типичный путь автозагрузки).

### Зачем

Исключения не должны раскрывать внутренние пути сервера, стек зависимостей или список всех зарегистрированных id — только запрошенный идентификатор.

---

## Запуск и CI

```bash
composer test:security
composer ci   # вместе с unit, integration, load, performance
```

Workflow `.github/workflows/quality.yml` — PHP 8.3, 8.4, 8.5.

## Рекомендации автору приложения

- Не передавайте пользовательский ввод напрямую в `get()` без валидации whitelist id.
- При autowiring не включайте `enableAutowiring()` с произвольным FQCN из запроса.
- Для отчётов об ошибках в production логируйте id, но не полный internal stack в публичный API.

## См. также

- [Тестирование](Testing) — обзор всех наборов
- [Анти-паттерны](Anti-patterns) — service locator, глобальный контейнер
- [SECURITY.md](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md) — сообщение об уязвимостях
