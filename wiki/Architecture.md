# Архитектура и принцип работы

На этой странице — схемы жизненного цикла сервисов, autowiring, сканирования и вспомогательных механизмов пакета **cloudcastle/di**.

## Обзор компонентов

Публичная точка входа — `Container`. Внутренние классы не предназначены для прямого использования в приложении, но формируют чёткое разделение ответственности.

```mermaid
flowchart TB
    subgraph public [Публичный API]
        CI[ContainerInterface]
        C[Container]
        CR[ContainerRegistry]
        LS[LazyService]
    end

    subgraph resolve [Разрешение сервисов]
        SAR[ServiceAliasResolver]
        SIR[ServiceInstanceResolver]
    end

    subgraph autowire [Autowiring]
        AW[Autowirer]
        MR[MemberResolver]
        PTR[ParameterTypeResolver]
        CDR[ClassDependencyResolver]
        ITR[IntersectionTypeResolver]
        ASR[AttributeServiceIdReader]
        PI[PropertyInjector]
        MI[MethodInjector]
    end

    subgraph scan [Сканирование]
        CS[ClassScanner]
    end

    CI --> C
    C --> SAR
    C --> SIR
    C --> AW
    C --> CS
    C --> LS
    CR -.->|хранит ссылку| CI
    SIR -->|get в фабриках| CI
    AW --> MR
    AW --> PI
    AW --> MI
    MR --> ASR
    MR --> PTR
    PTR --> CDR
    PTR --> ITR
    CDR -->|рекурсивный get| CI
    PI --> MR
    MI --> MR
```

| Компонент | Роль |
|-----------|------|
| `Container` | Регистрация (`set`, `autowire`, `tag`, `decorate`, `alias`), флаги autowiring, делегирование resolve |
| `ServiceAliasResolver` | Цепочки `alias → targetId`, детекция циклов |
| `ServiceInstanceResolver` | Кэш, definitions, autowiring, декораторы; общий для `get()` и `make()` |
| `Autowirer` | `new` + property + method injection |
| `ClassScanner` | Парсинг PHP-файлов без выполнения, список FQCN |
| `LazyService` | Отложенный `get()` при первом `getValue()` |
| `ContainerRegistry` | Глобальный singleton-контейнер приложения |

---

## Жизненный цикл приложения (bootstrap)

Типичный composition root: один контейнер на запрос (PHP-FPM) или на worker.

```mermaid
sequenceDiagram
    participant App as Точка входа
    participant C as Container
    participant CS as ClassScanner
    participant CR as ContainerRegistry

    App->>C: new Container()
    App->>C: set() / alias() / enableAutowiring()
    App->>C: scan(directory, namespace)
    C->>CS: scan()
    CS-->>C: list FQCN
    loop каждый класс без set()
        C->>C: autowire(FQCN)
    end
    App->>CR: set(container)
    App->>C: get(RootService::class)
    Note over C: рекурсивное разрешение графа зависимостей
    C-->>App: готовый RootService
```

**Приоритет регистрации:** явный `set(id)` всегда сильнее autowiring для того же `id`. `scan()` не перезаписывает существующие `set()`.

---

## `get()` и `make()`: общий путь разрешения

Оба метода сначала разрешают alias, затем вызывают `ServiceInstanceResolver` с флагом singleton.

```mermaid
flowchart TD
    Start([get id или make id]) --> Alias[ServiceAliasResolver.resolve]
    Alias --> Mode{singleton?}
    Mode -->|get: true| Cache{есть в resolved?}
    Mode -->|make: false| Def
    Cache -->|да| ReturnCache[вернуть из кэша]
    Cache -->|нет| Def{есть в definitions?}
    Def -->|да| Factory{callable?}
    Factory -->|да| CallFactory[вызвать фабрику с контейнером]
    Factory -->|нет| UseConcrete[взять готовое значение]
    CallFactory --> Finalize
    UseConcrete --> Finalize[finalizeInstance]
    Def -->|нет| CanAW{canAutowire id?}
    CanAW -->|да| Cycle{id в resolving?}
    Cycle -->|да| ErrCycle[ContainerException цикл]
    Cycle -->|нет| Mark[resolving id = true]
    Mark --> Inst[Autowirer.instantiate]
    Inst --> Finalize
    CanAW -->|нет| ErrNF[NotFoundException]
    Finalize --> Deco[применить декораторы по порядку]
    Deco --> Save{singleton и value !== null?}
    Save -->|да| PutCache[resolved id = instance]
    Save -->|нет| Done
    PutCache --> Done([вернуть instance])
    ReturnCache --> Done
```

| | `get()` | `make()` |
|---|---------|----------|
| Читает `resolved` | да | нет |
| Пишет в `resolved` | да (если не `null`) | нет |
| Фабрика | один раз до `set`/`decorate` | каждый вызов |
| Autowiring | кэшируется | новый объект |
| Декораторы | да | да |

---

## Autowiring: создание объекта

`Autowirer::instantiate()` — единственная точка создания классов через reflection.

```mermaid
flowchart LR
    subgraph step1 [1. Конструктор]
        RC[ReflectionClass]
        Params[параметры конструктора]
        MR1[MemberResolver.resolveParameter]
        New[newInstanceArgs]
    end

    subgraph step2 [2. Свойства]
        PI[PropertyInjector.inject]
        AttrP[Inject / Autowire на property]
        TypedP[typed properties при enablePropertyAutowiring]
    end

    subgraph step3 [3. Методы]
        MI[MethodInjector.inject]
        AttrM[методы с attributes]
        SetM[setter при enableMethodAutowiring]
    end

    RC --> Params --> MR1 --> New
    New --> PI
    PI --> AttrP
    PI --> TypedP
    AttrP --> MI
    TypedP --> MI
    MI --> AttrM
    MI --> SetM
```

При autowiring зависимости конструктора снова вызывают `$container->get()` — поэтому возможны цепочки и циклы (отслеживаются в `resolving`).

---

## Разрешение одного параметра / свойства

`MemberResolver` задаёт **фиксированный порядок** для конструктора, свойств и методов.

```mermaid
flowchart TD
    Start([параметр или свойство]) --> Attr{Inject / Autowire?}
    Attr -->|да, id задан| GetAttr["container.get(id из attribute)"]
    Attr -->|нет| Name{enableParameterNameAutowiring и has name?}
    Name -->|да| GetName["container.get(имя параметра)"]
    Name -->|нет| Type[ParameterTypeResolver]
    GetAttr --> End([значение])
    GetName --> End
    Type --> End
```

### Разрешение по типу (`ParameterTypeResolver`)

```mermaid
flowchart TD
    T([ReflectionType]) --> Null{type === null?}
    Null -->|да| Default[default value или ContainerException]
    Null -->|нет| Kind{вид типа}
    Kind -->|Union| Union[перебор вариантов, builtin пропуск]
    Kind -->|Intersection| Inter[IntersectionTypeResolver]
    Kind -->|Named| Named{builtin?}
    Named -->|да| Def2[default или исключение]
    Named -->|нет| Class[ClassDependencyResolver]
    Class --> Has{has FQCN?}
    Has -->|да| GetFQCN["container.get(FQCN)"]
    Has -->|нет| Nullable{nullable?}
    Nullable -->|да| NullVal[null]
    Nullable -->|нет| Exc[ContainerException]
    Inter --> Satisfies[экземпляр должен удовлетворять всем типам]
    Union --> End([значение])
    GetFQCN --> End
    Def2 --> End
    Default --> End
    NullVal --> End
    Exc --> End
    Satisfies --> End
```

Особые случаи:

- `ContainerInterface` / `Psr\Container\ContainerInterface` → текущий контейнер
- Intersection `A&B` → сервис, проходящий проверку всех интерфейсов
- Union → первый подходящий не-builtin тип с `has()`

---

## Циклические зависимости

```mermaid
flowchart LR
    A[get ServiceA] --> B[autowire создаёт A]
    B --> C[конструктор нужен ServiceB]
    C --> D[get ServiceB]
    D --> E[autowire создаёт B]
    E --> F[конструктор нужен ServiceA]
    F --> G{A уже в resolving?}
    G -->|да| X[ContainerException]
    G -->|нет| A
```

Стек `resolving` очищается в `finally` после успеха или ошибки instantiate.

**Важно:** циклы в **фабриках** `set()` не отслеживаются — возможен бесконечный рекурсивный `get()`.

---

## Alias

```mermaid
flowchart TD
    Reg[alias alias targetId] --> Store[aliases alias = targetId]
    Store --> Check[hasCycle от alias]
    Check -->|цикл| Rollback[удалить запись] --> Err[ContainerException]
    Check -->|ок| Ok[готово]
    Use[get/make alias] --> Walk[resolve: идти по цепочке]
    Walk --> Final[конечный id]
    Final --> Resolve[ServiceInstanceResolver]
```

`has()` возвращает `true` для id, зарегистрированного как alias, даже если target ещё не создан.

---

## Lazy-сервис

```mermaid
sequenceDiagram
    participant App
    participant C as Container
    participant L as LazyService
    participant Target as Целевой сервис

    App->>C: set('reports', lazy(ReportGenerator::class))
    C-->>App: LazyService (обёртка)
    Note over L: factory ещё не вызывалась
    App->>L: getValue() первый раз
    L->>C: get(ReportGenerator::class)
    C->>Target: создать / взять из кэша
    Target-->>L: instance
    L->>L: кэш внутри LazyService
    App->>L: getValue() повторно
    L-->>App: тот же instance из LazyService
```

Singleton-кэш контейнера для целевого id заполняется при **первом** `get()` внутри `LazyService`, не при `set(lazy(...))`.

---

## Декораторы

```mermaid
flowchart LR
    Inner[исходный экземпляр] --> D1[декоратор 1 inner, container]
    D1 --> D2[декоратор 2]
    D2 --> Dn[декоратор N]
    Dn --> Out[результат get/make]
    Out --> Cache{singleton get?}
    Cache -->|да| Resolved[записать в resolved]
```

`decorate(id)` сбрасывает `resolved[id]`. Порядок: первый зарегистрированный декоратор ближе к inner.

---

## Tagged services

```mermaid
flowchart TD
    Tag[tag id, tagName] --> List[добавить id в tags tagName]
    Get[getTagged tagName] --> Loop[для каждого id в порядке tag]
    Loop --> ResolveAlias[resolve alias]
    ResolveAlias --> Check{hasDefinition или canAutowire?}
    Check -->|нет| Skip[пропустить]
    Check -->|да| Get["get(id)"]
    Get --> Map[результат id => instance]
    Skip --> Loop
    Map --> Return[array]
```

Ключ в результате — **исходный** id из `tag()`, значение — после полного `get()` (с alias и декораторами).

---

## Сканирование каталога (`scan`)

```mermaid
flowchart TD
    Scan[Container.scan dir, ns] --> CS[ClassScanner.scan]
    CS --> Walk[рекурсивно .php файлы]
    Walk --> Parse[extractDeclaredTypeNames]
    Parse --> NS{префикс namespace?}
    NS -->|не совпадает| Skip
    NS -->|совпадает| CE{class_exists?}
    CE -->|нет| Skip[пропуск]
    CE -->|да| Inst{isInstantiable?}
    Inst -->|нет enum abstract interface| Skip
    Inst -->|да| FQCN[добавить FQCN]
    FQCN --> Loop{ещё файлы?}
    Skip --> Loop
    Loop -->|да| Walk
    Loop -->|нет| List[list FQCN]
    List --> Reg[для каждого FQCN]
    Reg --> HD{hasDefinition?}
    HD -->|да| Keep[оставить set]
    HD -->|нет| AW[autowire FQCN]
```

Парсинг **не выполняет** PHP-код файла; `class_exists()` загружает класс через Composer autoload.

---

## Хранилища состояния контейнера

```mermaid
erDiagram
    CONTAINER ||--o{ DEFINITIONS : "set()"
    CONTAINER ||--o{ RESOLVED : "get() singleton"
    CONTAINER ||--o{ TAGS : "tag()"
    CONTAINER ||--o{ DECORATORS : "decorate()"
    CONTAINER ||--o{ AUTOWIRED : "autowire()"
    CONTAINER ||--o{ RESOLVING : "autowire в процессе"
    CONTAINER ||--|| ALIAS_RESOLVER : "alias()"

    DEFINITIONS {
        string id PK
        mixed concrete "экземпляр или callable"
    }
    RESOLVED {
        string id PK
        object instance "singleton-кэш"
    }
    TAGS {
        string tag PK
        list ids "порядок регистрации"
    }
    DECORATORS {
        string id PK
        list callables "цепочка обёрток"
    }
    AUTOWIRED {
        string fqcn PK
        bool flag
    }
```

---

## Сравнение путей регистрации и получения

```mermaid
flowchart TB
    subgraph register [Регистрация]
        S1[set id, value]
        S2[autowire FQCN]
        S3[scan directory]
        S4[alias a, b]
        S5[enableAutowiring]
    end

    subgraph obtain [Получение]
        G1[get id]
        G2[make id]
        G3[lazy id → getValue]
        G4[getTagged tag]
    end

    S1 --> definitions[(definitions)]
    S2 --> autowired[(autowired)]
    S3 --> autowired
    S4 --> aliases[(aliases)]
    S5 --> flag[autowiringEnabled]

    G1 --> resolve[ServiceInstanceResolver singleton=true]
    G2 --> resolve2[ServiceInstanceResolver singleton=false]
    G3 --> lazy[LazyService → get]
    G4 --> G1

    definitions --> resolve
    definitions --> resolve2
    autowired --> resolve
    autowired --> resolve2
    flag --> resolve
    flag --> resolve2
    aliases --> resolve
    aliases --> resolve2
```

---

## См. также

- [Быстрый старт](Quick-start)
- [Autowiring](Autowiring)
- [Сканирование классов](Class-scanning)
- [Прототипы, alias и lazy](Prototypes-alias-lazy)
- [Фабрики и singleton](Factories-and-singleton)
- [Теги и декораторы](Tags-and-decorators)
- [Справочник API](API-reference)
