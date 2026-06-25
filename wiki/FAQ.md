# FAQ

## Чем CloudCastle DI отличается от PHP-DI / Symfony / Pimple?

CloudCastle DI — **минимальный** PSR-11 контейнер (~80 строк): явная регистрация через `set()`, singleton-фабрики, без autowiring и конфигурационных файлов. Подходит, когда нужна лёгкая зависимость без фреймворка.

## Есть autowiring?

Нет. Каждый сервис регистрируется явно. См. [Анти-паттерны](Anti-patterns).

## Можно использовать id = FQCN класса?

Да, id — произвольная строка. Часто используют `'App\\Service\\Mailer'` или `'mailer'`, но автоматического резолва по типу нет.

## Потокобезопасность?

`Container` не синхронизирован. В типичном PHP-FPM один контейнер на запрос — проблем нет. В long-running workers при параллельном доступе к одному экземпляру нужна внешняя синхронизация или отдельный контейнер на worker.

## Поддерживаются ли tagged services / декораторы?

Нет встроенной поддержки. Реализуйте в composition root или обёртке поверх контейнера.

## Как обновить Packagist после форка?

В upstream настроен workflow **Packagist** с secret `PACKAGIST_TOKEN`. Для форка настройте свой токен в Settings → Secrets.

## Где API-документация?

```bash
composer docs
```

Результат в `docs/` (не коммитится; генерируется локально). Пользовательское руководство — `doc/guide/` в репозитории и эта Wiki.

## Куда задавать вопросы?

- [Discussions Q&A](https://github.com/cloudcastle-apps/di/discussions/categories/q-a) — использование
- [Issues](https://github.com/cloudcastle-apps/di/issues) — баги
- [SECURITY](https://github.com/cloudcastle-apps/di/blob/main/SECURITY.md) — уязвимости (не в Issues)
