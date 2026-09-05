# `hipot:hiblock.list`: выборка записей highload-блока

`hipot:hiblock.list` применяет конвейер [`hipot:iblock.list`](hipot_iblock_list.md) к записям highload-блока. Компонент находит HL-блок, компилирует его ORM-сущность, выполняет `getList`, подготавливает пользовательские поля, кеширует результат и передаёт массив `ITEMS` в шаблон.

Один и тот же компонент можно использовать для справочника, таблицы событий, виджета или записи из одного элемента. Конкретную выборку определяют параметры, подготовку модели — `result_modifier.php`, отображение — шаблон.

## Конвейер данных

1. Подключает обязательные модули `highloadblock` и `iblock`.
2. Находит HL-блок по `HLBLOCK_ID` либо по его системному имени.
3. Компилирует DataManager-класс сущности.
4. Передаёт `ORDER`, `SELECT`, `FILTER`, `GROUP_BY`, лимит и ORM-кеш в `getList`.
5. Для каждого поля сохраняет исходное значение в ключе с `~` и формирует представление поля.
6. Применяет необязательный `MODIFY_ITEM`, собирает `ITEMS`, навигацию и кеширует результат компонента.

## Параметры

| Параметр | Тип | По умолчанию | Описание |
|---|---:|---|---|
| `HLBLOCK_ID` | `int` | — | ID highload-блока. Имеет приоритет, если передано числовое значение. |
| `HLBLOCK_CODE` | `string` | — | Системное имя HL-блока из поля `NAME`. Используется, если `HLBLOCK_ID` не задан. |
| `ORDER` | `array` | `['ID' => 'DESC']` | Сортировка ORM-запроса. |
| `SELECT` | `array` | `['*']` | Выбираемые поля. Компонент всегда добавляет `ID`. |
| `FILTER` | `array` | `[]` | ORM-фильтр сущности HL-блока. |
| `GROUP_BY` | `array` | `[]` | Группировка ORM-запроса. |
| `PAGESIZE` | `int` | `0` | Число записей на странице. |
| `NTOPCOUNT` | `int` | `0` | Жёсткий лимит без постраничной навигации. Если он больше нуля, `PAGESIZE` не используется. |
| `NAV_SHOW_ALWAYS` | `Y/N` | `N` | Параметр нормализуется компонентом, но в текущей реализации не передаётся в `GetPageNavStringEx`. |
| `NAV_TITLE` | `string` | `''` | Заголовок постраничной навигации. |
| `NAV_TEMPLATE` | `string` | `''` | Шаблон постраничной навигации. |
| `SET_404` | `Y/N` | `N` | Установить статус 404 при пустой выборке или невозможности собрать класс сущности. |
| `ALWAYS_INCLUDE_TEMPLATE` | `Y/N` | `N` | Подключить шаблон при пустом `ITEMS`. |
| `SET_CACHE_KEYS` | `array` | `[]` | Дополнительные ключи `$arResult`, доступные после восстановления кеша. |
| `CACHE_TIME` | `int` | — | Время стандартного кеша компонента. Рекомендуется задавать явно. |
| `CACHE_TIME_ORM` | `int` | `0` | TTL встроенного кеша ORM-запроса. |
| `MODIFY_ITEM`, `~MODIFY_ITEM` | serialized closure | — | Сериализованное через `opis/closure` замыкание, вызываемое для каждой строки. |

Метаданные самого HL-блока кешируются компонентом на 24 часа независимо от `CACHE_TIME_ORM`.

## Результат и представления пользовательских полей

```php
$arResult = [
    'ITEMS' => [],
    'NAV_STRING' => null,
    'NAV_CACHED_DATA' => null,
    'NAV_RESULT' => null,
];
```

Для каждого выбранного поля, кроме `ID`, компонент создаёт несколько представлений:

| Ключ | Содержимое |
|---|---|
| `~UF_NAME` | Исходное значение из ORM. Предпочтительно для сравнений и собственной подготовки вывода. |
| `UF_NAME` | HTML-представление типа пользовательского поля или `&nbsp;`, если тип не умеет его построить. |
| `fields['UF_NAME']` | Метаданные пользовательского поля с подготовленным `VALUE`. |

Не используйте HTML-представление `UF_*` в фильтрах и бизнес-условиях шаблона. Для них предназначены исходные значения `~UF_*`.

## Пример использования

```php
<?php

/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'hipot:hiblock.list',
    'events.table',
    [
        'HLBLOCK_CODE' => 'Events',
        'ORDER' => [
            'UF_START_DATE' => 'ASC',
            'ID' => 'ASC',
        ],
        'SELECT' => [
            'UF_NAME',
            'UF_START_DATE',
            'UF_LOCATION',
            'UF_URL',
        ],
        'FILTER' => [
            '=UF_ACTIVE' => 1,
        ],
        'GROUP_BY' => [],
        'PAGESIZE' => 20,
        'NTOPCOUNT' => 0,
        'NAV_TITLE' => 'События',
        'NAV_TEMPLATE' => '.default',
        'SET_404' => 'N',
        'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
        'CACHE_TIME' => 10800,
        'CACHE_TIME_ORM' => 10800,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Обезличенный шаблон таблицы:

```php
<?php
foreach ($arResult['ITEMS'] as $item):
    $name = (string)$item['~UF_NAME'];
    $url = (string)$item['~UF_URL'];
    ?>
    <article>
        <a href="<?=htmlspecialcharsbx($url)?>">
            <?=htmlspecialcharsbx($name)?>
        </a>
        <time><?=htmlspecialcharsbx((string)$item['~UF_START_DATE'])?></time>
        <span><?=htmlspecialcharsbx((string)$item['~UF_LOCATION'])?></span>
    </article>
<?php endforeach; ?>

<?=$arResult['NAV_STRING']?>
```

## Выборка одной записи

Как и детальная страница у `hipot:iblock.list`, одна запись является списком с ограничением:

```php
'FILTER' => ['ID' => (int)$recordId],
'NTOPCOUNT' => 1,
'PAGESIZE' => 0,
'SET_404' => 'Y',
```

Шаблон получает запись из `$arResult['ITEMS'][0] ?? null`.

## Мутация записи

Для подготовки конкретного представления предпочтителен `result_modifier.php` шаблона:

```php
<?php
foreach ($arResult['ITEMS'] as &$item) {
    $item['VIEW'] = [
        'NAME' => trim((string)$item['~UF_NAME']),
        'URL' => trim((string)$item['~UF_URL']),
    ];
}
unset($item);
```

Параметр `~MODIFY_ITEM` нужен, когда одну мутацию требуется передавать вместе с вызовом компонента. Он принимает результат `Opis\Closure\serialize()`, как и `hipot:iblock.list`. Замыкание должно зависеть только от параметров, участвующих в разделении кеша; пользовательские и другие персональные данные безопаснее добавлять вне общего кешируемого результата.

## Кеширование

У компонента два слоя кеша:

- `CACHE_TIME` кеширует итоговый `$arResult` компонента;
- `CACHE_TIME_ORM` включает кеш ORM-выборки.

Обычно достаточно кеша компонента. ORM-кеш полезен, если такая же выборка нужна в нескольких местах, но его нужно отдельно учитывать при изменении данных. После записи в HL-блок очищайте соответствующие кеши; например, общий кеш компонента можно сбросить через `CBitrixComponent::clearComponentCache('hipot:hiblock.list')`.
