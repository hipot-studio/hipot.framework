# `hipot:iblock.list`: один компонент для списка и детальной страницы

Идея основана на статье [«Базовые компоненты iblock.list, iblock.detail, мутация, варианты использования»](https://www.hipot-studio.com/Codex/base-components-mutator/), но учитывает текущее устройство `hipot.framework`.

Главное отличие от исходной схемы: отдельный базовый `iblock.detail` больше не нужен. И список, и карточка элемента строятся одним компонентом `hipot:iblock.list`. Для детальной страницы компонент делает обычную выборку, отфильтрованную по `ID` или `CODE`, и ограничивает результат одним элементом через `NTOPCOUNT => 1`.

## Основная идея

Компонент отвечает за общий конвейер получения данных:

1. добавляет обязательный фильтр по `IBLOCK_ID` и `ACTIVE => Y`;
2. применяет переданные `ORDER`, `FILTER` и `SELECT`;
3. при необходимости получает свойства и цепочки связанных элементов;
4. позволяет мутировать каждый выбранный элемент;
5. кеширует результат;
6. передаёт унифицированный массив `ITEMS` в шаблон.

Конкретная страница определяется не отдельным классом компонента, а параметрами выборки и шаблоном:

| Сценарий | `FILTER` | Ограничение | Шаблон |
|---|---|---:|---|
| Список новостей | раздел, дата, признаки публикации | `PAGESIZE` | `news.list` |
| Карточка новости | `ID` или `CODE` | `NTOPCOUNT => 1` | `news.detail` |
| Виджет | произвольный бизнес-фильтр | `NTOPCOUNT` | `news.widget` |
| API/read model | произвольный фильтр | по задаче | специализированный шаблон |

Таким образом, детальная страница — это частный случай списка из одного элемента, а `hipot:iblock.list` остаётся единой точкой чтения элементов инфоблока.

## Список элементов

```php
<?php

/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'hipot:iblock.list',
    'news.list',
    [
        'IBLOCK_ID' => NEWS_IBLOCK_ID,
        'ORDER' => [
            'ACTIVE_FROM' => 'DESC',
            'SORT' => 'ASC',
        ],
        'FILTER' => [
            'SECTION_ID' => (int)$sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ],
        'SELECT' => [
            'CODE',
            'ACTIVE_FROM',
            'PREVIEW_TEXT',
            'PREVIEW_PICTURE',
        ],
        'GET_PROPERTY' => 'Y',

        'NTOPCOUNT' => 0,
        'PAGESIZE' => 20,
        'NAV_TEMPLATE' => '.default',
        'NAV_SHOW_ALWAYS' => 'N',
        'NAV_SHOW_ALL' => 'N',
        'NAV_PAGEWINDOW' => 3,

        'SET_404' => 'N',
        'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
        'CACHE_TIME' => 10800,
        'CACHE_GROUPS' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
```

Шаблон перебирает общий контракт результата:

```php
<?php

foreach ($arResult['ITEMS'] as $item) {
    // Вывод карточки элемента списка.
}
```

## Детальная страница тем же `hipot:iblock.list`

Выбирать элемент можно по числовому `ID`:

```php
<?php

/** @global CMain $APPLICATION */

$elementId = (int)$elementId;

$APPLICATION->IncludeComponent(
    'hipot:iblock.list',
    'news.detail',
    [
        'IBLOCK_ID' => NEWS_IBLOCK_ID,
        'FILTER' => [
            'ID' => $elementId,
        ],
        'SELECT' => [
            'CODE',
            'ACTIVE_FROM',
            'DETAIL_TEXT',
            'DETAIL_PICTURE',
        ],
        'GET_PROPERTY' => 'Y',

        // Детальная страница является списком максимум из одного элемента.
        'NTOPCOUNT' => 1,
        'PAGESIZE' => 0,

        // Пустая выборка означает, что запрошенная страница не найдена.
        'SET_404' => 'Y',
        'ALWAYS_INCLUDE_TEMPLATE' => 'N',
        'CACHE_TIME' => 10800,
        'CACHE_GROUPS' => 'N',
    ],
    false,
    ['HIDE_ICONS' => 'Y']
);
```

Для ЧПУ вместо `ID` передаётся проверенный символьный код:

```php
'FILTER' => [
    'CODE' => $elementCode,
],
'NTOPCOUNT' => 1,
```

Детальный шаблон сохраняет тот же контракт `ITEMS`, но явно берёт первый элемент:

```php
<?php

$item = $arResult['ITEMS'][0] ?? null;
if ($item === null) {
    return;
}

// Вывод детальной страницы.
```

`SET_404 => 'Y'` устанавливает статус 404 при пустой выборке, а `ALWAYS_INCLUDE_TEMPLATE => 'N'` не подключает детальный шаблон без элемента. Проверка в шаблоне всё равно полезна: она фиксирует его входной контракт и делает шаблон безопасным при повторном использовании.

## Мутация результата

Базовый компонент не должен знать, как конкретный проект форматирует даты, строит view model, определяет CSS-модификаторы или добавляет данные из прикладных сервисов. Эти изменения относятся к уровню конкретного использования компонента.

Предпочтительное место для мутации результата — `result_modifier.php` шаблона:

```text
/local/templates/<site>/components/hipot/iblock.list/news.list/result_modifier.php
/local/templates/<site>/components/hipot/iblock.list/news.detail/result_modifier.php
```

Пример общей формы мутации:

```php
<?php

foreach ($arResult['ITEMS'] as &$item) {
    $item['VIEW'] = [
        'TITLE' => (string)$item['NAME'],
        'URL' => (string)$item['DETAIL_PAGE_URL'],
    ];
}
unset($item);
```

Если одну и ту же мутацию необходимо передать вызывающей стороной и применить к каждому элементу до помещения в `ITEMS`, компонент поддерживает параметр `~MODIFY_ITEM` с сериализованным замыканием из `opis/closure`:

```php
<?php

use function Opis\Closure\serialize as serializeClosure;

$modifyItem = static function (array &$item): void {
    $item['VIEW']['IS_NEW'] = strtotime((string)$item['ACTIVE_FROM']) > strtotime('-7 days');
};

$APPLICATION->IncludeComponent(
    'hipot:iblock.list',
    'news.list',
    [
        'IBLOCK_ID' => NEWS_IBLOCK_ID,
        'SELECT' => ['ACTIVE_FROM'],
        'PAGESIZE' => 20,
        'GET_PROPERTY' => 'N',
        'CACHE_TIME' => 10800,
        'CACHE_GROUPS' => 'N',
        '~MODIFY_ITEM' => serializeClosure($modifyItem),
    ]
);
```

Такой callback должен быть детерминированным относительно параметров компонента. Не следует захватывать в него текущий запрос, пользователя или иное персональное состояние, если оно не участвует в разделении кеша. Персонализированные данные безопаснее добавлять вне общего кешируемого результата.

## Где должна находиться логика

- `hipot:iblock.list` — универсальная выборка, свойства, связанные цепочки, кеш и общий контракт результата.
- Параметры вызова — ограничение конкретной выборки: инфоблок, фильтр, сортировка, поля, пагинация или один элемент.
- `result_modifier.php` — подготовка данных для конкретного представления.
- `template.php` — только отображение уже подготовленной модели.
- Наследник или проектная копия компонента — только когда действительно требуется изменить сам алгоритм получения данных, а не одну страницу.

Не следует создавать отдельный `hipot:iblock.detail`: он дублировал бы загрузку модулей, фильтрацию, получение свойств, кеширование, обработку 404 и механизм мутации. Различия списка и карточки выражаются параметрами и шаблонами одного `hipot:iblock.list`.

## Контракт компонента

Основные поля результата:

```php
$arResult = [
    'ITEMS' => [],
    'CNT_ITEMS' => 0,
    'NAV_STRING' => null, // При включённой постраничной навигации.
    'NAV_RESULT' => null, // Метаданные постраничной навигации.
];
```

Для списка допустимо любое количество `ITEMS`. Для детальной страницы действуют дополнительные инварианты:

- `NTOPCOUNT` равен `1`;
- `PAGESIZE` равен `0`, постраничная навигация не нужна;
- `ITEMS` содержит ноль или один элемент;
- ноль элементов при `SET_404 => 'Y'` означает 404;
- детальный шаблон работает с `$arResult['ITEMS'][0]`.

Эти правила позволяют использовать один и тот же базовый компонент во всех сценариях чтения элементов и не разносить одинаковую инфраструктурную логику между `list` и `detail`.
