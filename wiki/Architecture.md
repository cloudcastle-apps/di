<p align="center">
  <img src="https://raw.githubusercontent.com/cloudcastle-apps/di/main/assets/logo.svg" alt="CloudCastle DI" width="64">
</p>

# 🏗️ Архитектура и принцип работы

> [← Главная](Home) · [Сравнение](Comparison) · [Autowiring](Autowiring)

На этой странице — схемы жизненного цикла сервисов, autowiring, конфигурации, заморозки, сканирования и вспомогательных механизмов пакета **cloudcastle/di**.

## Обзор компонентов

Публичная точка входа — `Container`. Внутренние классы не предназначены для прямого использования в приложении, но формируют чёткое разделение ответственности.

```mermaid
flowchart TB
    subgraph public [Публичный API]
        CI[ContainerInterface]
        C[Container]
        CR[ContainerRegistry]
        LS[LazyService]
        TSI[TaggedServiceIterator]
        TSL[TaggedServiceLocator]
    end

    subgraph resolve [Разрешение сервисов]
        SAR[ServiceAliasResolver]
        SIR[ServiceInstanceResolver]
        ARD[AfterResolvingDispatcher]
        CI_INV[CallableInvoker]
        INTROS[ContainerIntrospector dump]
    end

    subgraph autowire [Autowiring]
        AW[Autowirer]
        MR[MemberResolver]
        PTR[ParameterTypeResolver]
        CDR[ClassDependencyResolver]
        ITR[IntersectionTypeResolver]
        ASIR[AttributeServiceIdReader]
        PI[PropertyInjector]
        MI[MethodInjector]
        BUILTIN_ATTR[Inject / Autowire]
        CUSTOM_ATTR[ServiceIdAttribute]
    end

    subgraph scan [Сканирование]
        CS[ClassScanner]
    end

    subgraph config [Конфигурация v1.5]
        CC[ContainerConfigurator]
        CLR[ConfigurationLoaderRegistry]
        CM[ConfigurationMerger]
        CA[ConfigurationApplicator]
        ASR_REG[AttributeServiceIdRegistry]
        subgraph loaders [Загрузчики]
            L_PHP[PhpLoader]
            L_JSON[JsonLoader]
            L_YAML[YamlLoader]
            L_XML[XmlLoader]
        end
    end

    CI --> C
    C --> SAR
    C --> SIR
    C --> ARD
    C --> CI_INV
    C --> AW
    C --> CS
    C --> ASR_REG
    C --> INTROS
    C --> LS
    C --> TSI
    C --> TSL
    CC --> CLR
    CLR --> loaders
    CC --> CM
    CC --> CA
    CA --> C
    ASR_REG --> ASIR
    BUILTIN_ATTR --> ASIR
    CUSTOM_ATTR --> ASR_REG
    CR -.->|хранит ссылку| CI
    SIR -->|get в фабриках| CI
    AW --> MR
    AW --> PI
    AW --> MI
    MR --> ASIR
    MR --> PTR
    PTR --> CDR
    PTR --> ITR
    CDR -->|рекурсивный get| CI
    PI --> MR
    MI --> MR
    CI_INV --> MR
```

| Компонент | Роль |
|-----------|------|
| `Container` | Регистрация (`set`, `autowire`, `tag`, `decorate`, `alias`, `bind`, `addDefinitions`, `registerAttribute`), `call()`, `afterResolving()`, флаги autowiring, `freeze()` / `dump()`, делегирование resolve |
| `ContainerIntrospector` | Снимок wiring для `dump()` / `getDefinitionIds()` |
| `ServiceAliasResolver` | Цепочки `alias → targetId`, детекция циклов |
| `ServiceInstanceResolver` | Кэш, definitions, autowiring, декораторы; общий для `get()` и `make()` |
| `AfterResolvingDispatcher` | Callback после нового resolve |
| `CallableInvoker` | Autowiring вызова callable |
| `TaggedServiceIterator` / `TaggedServiceLocator` | Итерация и доступ к сервисам по тегу |
| `Autowirer` | `new` + property + method injection |
| `ClassScanner` | Парсинг PHP-файлов без выполнения, список FQCN |
| `ContainerConfigurator` | Загрузка конфигурации из PHP/JSON/YAML/XML, слияние по приоритетам, `apply()` к контейнеру |
| `AttributeServiceIdRegistry` | Пользовательские PHP-attributes для autowiring (`registerAttribute()`) |
| `LazyService` | Отложенный `get()` при первом `getValue()` |
| `LazyGhostProxyFactory` | Lazy ghost/proxy для interface (v1.18, opt-in var-exporter) |
| `ContainerProfiler` | Opt-in замеры get/make/call (v1.15) |
| `ServiceObjectPool` | Object pool для `make()` (v1.16) |
| `ServiceTtlRegistry` | TTL singleton-кэша (v1.17) |
| `ContainerRegistry` | Глобальный singleton-контейнер приложения |

---

## Жизненный цикл приложения (bootstrap)

Типичный composition root: один контейнер на запрос (PHP-FPM) или на worker.

```mermaid
sequenceDiagram
    participant App as Точка входа
    participant C as Container
    participant CC as ContainerConfigurator
    participant CS as ClassScanner
    participant CR as ContainerRegistry

    App->>C: new Container()
    alt Ручной bootstrap
        App->>C: registerAttribute() опционально
        App->>C: set() / bind() / enableAutowiring()
        App->>C: scan(directory, namespace)
        C->>CS: scan()
        CS-->>C: list FQCN
        loop каждый класс без set()
            C->>C: autowire(FQCN)
        end
    else Декларативный v1.5
        App->>CC: configure(container, sources)
        CC->>CC: loadMany → merge по priority
        CC->>C: apply: register_attributes → autowiring → scan → services → autowire → bind → aliases → tags
    end
    opt production
        App->>C: freeze()
        Note over C: мутации set/tag/… → ContainerException
    end
    App->>CR: set(container)
    App->>C: get(RootService::class)
    Note over C: рекурсивное разрешение графа зависимостей
    C-->>App: готовый RootService
```

**Приоритет регистрации:** явный `set(id)` всегда сильнее autowiring для того же `id`. `scan()` не перезаписывает существующие `set()`.

**Альтернатива (v1.5):** вместо ручного `set()` / `scan()` — `ContainerConfigurator::configure($container, …)` с одним или несколькими файлами (PHP, JSON, YAML, XML). Слои с большим `priority` перекрывают предыдущие. См. [Конфигурация из файлов](Configuration).

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
    Deco --> Hooks{singleton и не из кэша?}
    Hooks -->|да| ARD[AfterResolvingDispatcher]
    Hooks -->|нет| Save
    ARD --> Save{singleton и value !== null?}
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
        AttrP[Inject / Autowire / custom attribute]
        TypedP[typed properties при enablePropertyAutowiring]
    end

    subgraph step3 [3. Методы]
        MI[MethodInjector.inject]
        AttrM[методы с attributes]
        SetM[setter при enableMethodAutowiring]
    end

    subgraph resolve [Разрешение значения]
        ASIR[AttributeServiceIdReader]
        PTR[ParameterTypeResolver]
        GET["container.get()"]
    end

    RC --> Params --> MR1 --> New
    MR1 --> ASIR
    MR1 --> PTR
    ASIR --> GET
    PTR --> GET
    New --> PI
    PI --> AttrP
    PI --> TypedP
    AttrP --> MI
    TypedP --> MI
    MI --> AttrM
    MI --> SetM
    AttrM --> ASIR
    SetM --> ASIR
```

При autowiring зависимости конструктора снова вызывают `$container->get()` — поэтому возможны цепочки и циклы (отслеживаются в `resolving`).

---

## Разрешение одного параметра / свойства

`MemberResolver` задаёт **фиксированный порядок** для конструктора, свойств и методов.

```mermaid
flowchart TD
    Start([параметр или свойство]) --> Custom{custom attribute из registerAttribute?}
    Custom -->|да, id задан| GetCustom["container.get(id из attribute)"]
    Custom -->|нет| Attr{Inject / Autowire?}
    Attr -->|да, id задан| GetAttr["container.get(id из attribute)"]
    Attr -->|нет| Name{enableParameterNameAutowiring и has name?}
    Name -->|да| GetName["container.get(имя параметра)"]
    Name -->|нет| Type[ParameterTypeResolver]
    GetCustom --> End([значение])
    GetAttr --> End
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

### `bind()` vs `alias()`

```mermaid
flowchart TD
    Bind[bind abstract, concrete] --> IsClass{concrete — instantiable класс?}
    IsClass -->|да| AW[autowire concrete]
    AW --> Alias1[alias abstract → concrete]
    IsClass -->|нет, существующий id| Alias2[alias abstract → id]
    IsClass -->|иначе| Err[ContainerException]

    AliasOnly[alias alias, targetId] --> Store[aliases только переименование]
```

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
    Out --> Hooks[afterResolving если новый]
    Hooks --> Cache{singleton get?}
    Cache -->|да| Resolved[записать в resolved]
    DecorateReg[decorate id] -.->|сброс| Resolved
```

`decorate(id)` сбрасывает `resolved[id]`. Порядок: первый зарегистрированный декоратор ближе к inner.

---

## Tagged services

```mermaid
flowchart TD
    Tag[tag id, tagName] --> List[добавить id в tags tagName]
    List --> API{какой API}
    API -->|getTagged| Loop[для каждого id в порядке tag]
    API -->|getTaggedIds| Ids[только список id]
    API -->|getTaggedIterator| Iter[foreach → get]
    API -->|getTaggedLocator| Loc[has/get по id в теге]
    Loop --> ResolveAlias[resolve alias]
    ResolveAlias --> Check{hasDefinition или canAutowire?}
    Check -->|нет| Skip[пропустить]
    Check -->|да| Get["get(id)"]
    Get --> Map[результат id => instance]
    Skip --> Loop
    Ids --> Loop
    Iter --> Get
    Loc --> Get
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
    CONTAINER ||--o{ DEFINITIONS : "set() addDefinitions"
    CONTAINER ||--o{ RESOLVED : "get() singleton"
    CONTAINER ||--o{ TAGS : "tag()"
    CONTAINER ||--o{ DECORATORS : "decorate()"
    CONTAINER ||--o{ AUTOWIRED : "autowire()"
    CONTAINER ||--o{ RESOLVING : "autowire в процессе"
    CONTAINER ||--o{ AFTER_RESOLVING : "afterResolving()"
    CONTAINER ||--o{ CUSTOM_ATTRIBUTES : "registerAttribute()"
    CONTAINER ||--|| ALIAS_RESOLVER : "alias() bind()"
    CONTAINER ||--|| FLAGS : "autowiring flags"
    CONTAINER ||--|| FROZEN : "freeze()"

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
    AFTER_RESOLVING {
        string id PK
        list callbacks "порядок регистрации"
    }
    CUSTOM_ATTRIBUTES {
        string attributeClass PK
    }
    FLAGS {
        bool autowiringEnabled
        bool parameterName
        bool property
        bool method
    }
    FROZEN {
        bool isFrozen
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
        S5[bind abstract, concrete]
        S6[addDefinitions array]
        S7[enableAutowiring flags]
        S8[registerAttribute class]
        S9[ContainerConfigurator.configure]
        S10[tag / decorate / afterResolving]
        S11[freeze]
    end

    subgraph obtain [Получение]
        G1[get id]
        G2[make id]
        G3[lazy id → getValue]
        G4[getTagged tag]
        G5[getTaggedIds / Iterator / Locator]
        G6[call callable]
        G7[has / hasDefinition]
        G8[dump / getDefinitionIds]
    end

    S1 --> definitions[(definitions)]
    S2 --> autowired[(autowired)]
    S3 --> autowired
    S4 --> aliases[(aliases)]
    S5 --> aliases
    S5 --> autowired
    S6 --> definitions
    S7 --> flag[autowiring flags]
    S8 --> attrReg[(custom attributes)]
    S9 --> CA[ConfigurationApplicator]
    CA --> definitions
    CA --> autowired
    CA --> aliases
    CA --> flag
    CA --> attrReg
    S10 --> tags[(tags)]
    S10 --> deco[(decorators)]
    S10 --> hooks[(afterResolving)]
    S11 --> frozen[frozen=true]

    G1 --> resolve[ServiceInstanceResolver singleton=true]
    G2 --> resolve2[ServiceInstanceResolver singleton=false]
    G3 --> lazy[LazyService → get]
    G4 --> G1
    G5 --> G1
    G6 --> invoker[CallableInvoker]
    G8 --> introspect[ContainerIntrospector]

    definitions --> resolve
    definitions --> resolve2
    autowired --> resolve
    autowired --> resolve2
    flag --> resolve
    flag --> resolve2
    aliases --> resolve
    aliases --> resolve2
    attrReg --> invoker
    attrReg --> resolve
    deco --> resolve
    deco --> resolve2
    hooks --> resolve
    invoker --> autowired
    frozen -.->|блокирует| register
```

---

## `Container::call()` и `CallableInvoker`

Вызов callable не проходит через `ServiceInstanceResolver` — отдельный путь через `CallableInvoker` и общий `MemberResolver` / `ParameterTypeResolver`.

```mermaid
sequenceDiagram
    participant App as Приложение
    participant C as Container
    participant CI as CallableInvoker
    participant MR as MemberResolver
    participant PTR as ParameterTypeResolver

    App->>C: call(callable, parameters)
    C->>CI: invoke()
    CI->>CI: reflectCallable()
    loop параметры reflection
        alt ключ в parameters
            CI->>CI: явное значение
        else autowire
            CI->>MR: resolveParameter()
            MR->>PTR: тип / attribute / имя
            PTR->>C: get(FQCN)
        end
    end
    CI->>CI: invokeArgs()
    CI-->>App: mixed
```

Поддерживаемые формы: `Closure`, first-class callable, `[object, method]`, invokable, имя функции. Подробнее — [call(), bind(), afterResolving](Call-bind-callbacks).

---

## `afterResolving` и `AfterResolvingDispatcher`

Callback регистрируется в `AfterResolvingDispatcher` и вызывается из `Container::resolveService()` **после** успешного создания, если экземпляр не был прочитан из singleton-кэша до resolve.

```mermaid
sequenceDiagram
    participant C as Container
    participant SIR as ServiceInstanceResolver
    participant ARD as AfterResolvingDispatcher

    C->>C: wasCached = singleton && isset(resolved[id])
    C->>SIR: resolve(...)
    SIR-->>C: instance
    alt not wasCached
        C->>ARD: dispatch(id, instance, container)
        loop callbacks[id]
            ARD->>ARD: callback(id, instance, container)
        end
    end
    C-->>C: return instance
```

| `get()` | `make()` |
|---------|----------|
| callback при первом создании | callback при **каждом** вызове |
| повторный `get()` из кэша — без callback | всегда новый экземпляр → всегда callback |

---

## Сравнение API тегов

```mermaid
flowchart LR
    tag[tag id, name]
    ids[getTaggedIds]
    eager[getTagged]
    iter[getTaggedIterator]
    loc[getTaggedLocator]

    tag --> ids
    ids --> iter
    ids --> loc
    eager --> iter
    eager --> loc
    iter -->|get внутри| eager
    loc -->|get/has| eager
```

| API | Ключи | Eager `get()` |
|-----|-------|---------------|
| `getTagged()` | id → instance | все id тега |
| `getTaggedIds()` | — | нет |
| `getTaggedIterator()` | только values | при foreach |
| `getTaggedLocator()` | id при iterate | при `get()` / foreach |

---

## Конфигурация: загрузка, слияние, применение (v1.5)

`ContainerConfigurator` не заменяет контейнер — он **наполняет** уже созданный `Container` через `ConfigurationApplicator`.

```mermaid
flowchart TD
    Sources[list sources string или ConfigurationSource]
    Sources --> Load[loadMany]
    Load --> Loop[для каждого источника]
    Loop --> Ext{расширение}
    Ext -->|.php| Php[PhpConfigurationLoader require]
    Ext -->|.json| Json[JsonConfigurationLoader]
    Ext -->|.yaml .yml| Yaml[YamlConfigurationLoader ext-yaml]
    Ext -->|.xml| Xml[XmlConfigurationLoader SimpleXML]
    Php --> Layer[ConfigurationLayer + file priority]
    Json --> Layer
    Yaml --> Layer
    Xml --> Layer
    Layer --> Merge[ConfigurationMerger.merge]
    Merge --> Config[объединённый array]
    Config --> Apply[ConfigurationApplicator.apply]
    Apply --> RA[register_attributes]
    RA --> AWF[autowiring flags]
    AWF --> SC[scan]
    SC --> SV[services set bind lazy]
    SV --> AWL[autowire list]
    AWL --> BD[bind]
    BD --> AL[aliases]
    AL --> TG[tags]
    TG --> C[Container готов к get]
```

Приоритет при конфликте: **priority параметра** → **priority файла** → **порядок в списке** (последний побеждает).

---

## Заморозка контейнера (`freeze`)

После `freeze()` любая мутация wiring (`set`, `autowire`, `tag`, `bind`, `configure` через applicator и т.д.) выбрасывает `ContainerException`. `get()` / `make()` / `call()` / `has()` продолжают работать.

```mermaid
stateDiagram-v2
    [*] --> Mutable: new Container
    Mutable --> Mutable: set / bind / scan / configure
    Mutable --> Frozen: freeze()
    Frozen --> Frozen: get / make / call / has
    Frozen --> Error: set / tag / autowire / configure
    Error --> Frozen: ContainerException
    note right of Frozen
        isFrozen() === true
        dump() для отладки
    end note
```

`dump()` и `getDefinitionIds()` доступны в обоих состояниях.

---

## `registerAttribute()` и пользовательские attributes

```mermaid
flowchart LR
    Dev[ServiceIdAttribute class] --> Reg[registerAttribute или register_attributes в конфиге]
    Reg --> Registry[AttributeServiceIdRegistry]
    Registry --> Reader[AttributeServiceIdReader]
    Reader --> MR[MemberResolver]
    MR --> Get["container.get(custom id)"]
```

Встроенные `Inject` / `Autowire` регистрируются в registry по умолчанию; пользовательские — только после `registerAttribute()`.

---

## Глобальный реестр `ContainerRegistry`

```mermaid
sequenceDiagram
    participant Bootstrap
    participant C as Container
    participant CR as ContainerRegistry
    participant App as Код приложения
    participant Test as Тест tearDown

    Bootstrap->>C: wiring + опционально freeze
    Bootstrap->>CR: set(container)
    App->>CR: get()
    CR-->>App: тот же ContainerInterface
    Test->>CR: reset()
    Note over CR: изоляция между тестами
```

---

## См. также

- [Конфигурация из файлов](Configuration)
- [Быстрый старт](Quick-start)
- [call(), bind(), afterResolving](Call-bind-callbacks)
- [Autowiring](Autowiring)
- [Сканирование классов](Class-scanning)
- [Прототипы, alias и lazy](Prototypes-alias-lazy)
- [Фабрики и singleton](Factories-and-singleton)
- [Теги и декораторы](Tags-and-decorators)
- [Справочник API](API-reference)
