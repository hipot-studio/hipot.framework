# `hipot:iblock.menu_ext`: динамическое меню из элементов или разделов инфоблока

`hipot:iblock.menu_ext` строит пункты меню Битрикса по результату выборки из инфоблока. Компонент выбирает элементы либо разделы, преобразует каждую запись в стандартный массив пункта меню, кеширует готовый результат и возвращает его в `.menu_ext.php`.

Компонент не выводит HTML и не подключает шаблон. Его результат объединяется с `$aMenuLinks`, после чего обычный `bitrix:menu` отображает статические и динамические пункты как одно меню.

## Конвейер данных

1. Проверяет обязательные `TYPE`, `CACHE_TAG` и `CACHE_TIME`.
2. Добавляет базовый фильтр `IBLOCK_ID` и `ACTIVE => Y`.
3. В зависимости от `TYPE` выполняет `CIBlockElement::GetList` или `CIBlockSection::GetList`.
4. При необходимости применяет `MODIFY_ITEM` к выбранной записи.
5. Преобразует запись в формат пункта меню Битрикса.
6. Сохраняет итоговый массив в data-кеше и возвращает его вызывающему `.menu_ext.php`.

## Где используется

Файл расширения меню располагается рядом с обычным файлом меню:

```text
/.left.menu.php
/.left.menu_ext.php
```

Битрикс подключает `.left.menu_ext.php`, когда в параметрах `bitrix:menu` включено расширение меню. Файл должен вернуть пункты через переменную `$aMenuLinks`.

## Параметры

| Параметр | Тип | Обязательный | По умолчанию | Описание |
|---|---:|:---:|---|---|
| `TYPE` | `string` | да | — | Источник пунктов: `elements` или `sections`. |
| `CACHE_TAG` | `string` | да | — | Имя каталога кеша внутри `/bitrix/cache/php/`. Регистр приводится к нижнему. |
| `CACHE_TIME` | `int` | да | — | Время кеширования результата в секундах. |
| `IBLOCK_ID` | `int` | да | — | ID инфоблока для выборки. Компонент не валидирует его отдельно, но без него корректная выборка невозможна. |
| `ORDER` | `array` | нет | `['SORT' => 'ASC']` | Сортировка в формате соответствующего `GetList`. |
| `FILTER` | `array` | нет | `[]` | Дополнительный фильтр. Объединяется с `IBLOCK_ID` и `ACTIVE => Y`. |
| `SELECT` | `array` | нет | `[]` | Дополнительные поля или свойства элемента либо поля раздела. При непустом `SELECT` запись также передаётся в четвёртом поле пункта меню. |
| `ADDON_URL_TO_SELECT_ITEM` | `string` | нет | `''` | Шаблон дополнительного URL, по которому пункт считается выбранным. Обрабатывается через `CIBlock::ReplaceDetailUrl` или `CIBlock::ReplaceSectionUrl`. |
| `MODIFY_ITEM`, `~MODIFY_ITEM` | serialized closure | нет | — | Модификатор выбранного элемента или раздела. Контракт совпадает с `hipot:iblock.list`. |

Дополнительные параметры тоже входят в идентификатор кеша. Например, параметр `VERSION` можно менять при изменении логики меню, чтобы получить новый вариант кеша без ожидания окончания TTL.

## Формат результата

Каждый пункт имеет стандартную структуру меню Битрикса:

```php
$arResult[] = [
    0 => 'Название пункта',
    1 => '/catalog/item/',
    2 => ['/catalog/another-matching-url/'],
    3 => ['ID' => 10, 'CODE' => 'item'],
];
```

| Индекс | Содержимое |
|---:|---|
| `0` | Название: `NAME` элемента или раздела. |
| `1` | Основной URL: `DETAIL_PAGE_URL` или `SECTION_PAGE_URL`. |
| `2` | Дополнительные URL для определения активного пункта. Массив пуст, если `ADDON_URL_TO_SELECT_ITEM` не задан. |
| `3` | Параметры пункта меню. Если `SELECT` пуст, компонент возвращает `[]`; иначе — выбранную запись целиком. |

Компонент возвращает обычный индексный массив таких пунктов. При пустой выборке возвращается `[]`, а пустой результат в кеш не записывается.

## Пример `.left.menu_ext.php` для элементов

```php
<?php
defined('B_PROLOG_INCLUDED') || die();

global $APPLICATION;

/** @var array $aMenuLinks */
$dynamicMenuLinks = $APPLICATION->IncludeComponent(
    'hipot:iblock.menu_ext',
    '',
    [
        'TYPE' => 'elements',
        'CACHE_TAG' => 'catalog_left_menu_ext',
        'CACHE_TIME' => 2592000,
        'IBLOCK_ID' => $catalogIblockId,
        'ORDER' => [
            'SORT' => 'ASC',
            'NAME' => 'ASC',
        ],
        'FILTER' => [
            'SECTION_ID' => $sectionId,
            'INCLUDE_SUBSECTIONS' => 'Y',
        ],
        'SELECT' => [
            'CODE',
        ],
        'ADDON_URL_TO_SELECT_ITEM' => '/catalog/#ELEMENT_CODE#/',
    ],
    null,
    ['HIDE_ICONS' => 'Y'],
);

$aMenuLinks = array_merge((array)$dynamicMenuLinks, (array)$aMenuLinks);
```

В этом варианте динамические пункты добавляются перед статическими. Чтобы статические пункты шли первыми, поменяйте аргументы `array_merge` местами.

## Пример для разделов

```php
<?php
defined('B_PROLOG_INCLUDED') || die();

global $APPLICATION;

/** @var array $aMenuLinks */
$sectionMenuLinks = $APPLICATION->IncludeComponent(
    'hipot:iblock.menu_ext',
    '',
    [
        'TYPE' => 'sections',
        'CACHE_TAG' => 'catalog_sections_menu_ext',
        'CACHE_TIME' => 86400,
        'IBLOCK_ID' => $catalogIblockId,
        'ORDER' => [
            'LEFT_MARGIN' => 'ASC',
        ],
        'FILTER' => [
            'DEPTH_LEVEL' => 1,
        ],
        'SELECT' => [
            'CODE',
            'DEPTH_LEVEL',
            'UF_MENU_ICON',
        ],
        'ADDON_URL_TO_SELECT_ITEM' => '/catalog/#SECTION_CODE#/',
    ],
    null,
    ['HIDE_ICONS' => 'Y'],
);

$aMenuLinks = array_merge((array)$aMenuLinks, (array)$sectionMenuLinks);
```

Непустой `SELECT` делает данные раздела доступными в четвёртом поле пункта. Их можно использовать в шаблоне `bitrix:menu` для иконки, уровня вложенности или CSS-модификатора.

## `MODIFY_ITEM`: тот же контракт, что у `iblock.list`

Модификатор получает выбранную запись по ссылке:

```php
static function (array &$item): void
```

Для `TYPE => 'elements'` это результат `CIBlockElement::GetList()->GetNext()`, для `TYPE => 'sections'` — результат `CIBlockSection::GetList()->GetNext()`. Изменения `NAME`, `DETAIL_PAGE_URL` или `SECTION_PAGE_URL` попадают в заголовок и основной URL пункта меню.

Как и в [`hipot:iblock.list`](hipot_iblock_list.md#мутация-результата), можно передать сериализованное замыкание через `~MODIFY_ITEM`:

```php
<?php

use function Opis\Closure\serialize as serializeClosure;

$modifyItem = static function (array &$item): void {
    $menuName = trim((string)$item['PROPERTY_MENU_NAME_VALUE']);
    $menuUrl = trim((string)$item['PROPERTY_MENU_URL_VALUE']);

    if ($menuName !== '') {
        $item['NAME'] = $menuName;
    }
    if ($menuUrl !== '') {
        $item['DETAIL_PAGE_URL'] = $menuUrl;
    }
};

$dynamicMenuLinks = $APPLICATION->IncludeComponent(
    'hipot:iblock.menu_ext',
    '',
    [
        'TYPE' => 'elements',
        'CACHE_TAG' => 'catalog_left_menu_ext',
        'CACHE_TIME' => 2592000,
        'IBLOCK_ID' => $catalogIblockId,
        'ORDER' => ['SORT' => 'ASC'],
        'FILTER' => [],
        'SELECT' => [
            'PROPERTY_MENU_NAME',
            'PROPERTY_MENU_URL',
        ],
        '~MODIFY_ITEM' => serializeClosure($modifyItem),
    ],
    null,
    ['HIDE_ICONS' => 'Y'],
);
```

Проектный код также может передать `Opis\Closure\SerializableClosure` в параметре `MODIFY_ITEM`; при подготовке параметров Битрикс сохраняет исходное значение в `~MODIFY_ITEM`, которое читает компонент.

Модификатор выполняется только при промахе кеша. Он должен быть детерминированным относительно параметров компонента: не следует захватывать текущего пользователя, запрос или другое персональное состояние, если оно не разделяет кеш.

Компонент вычисляет дополнительные URL и копирует параметры пункта до вызова модификатора. Поэтому изменение записи через `MODIFY_ITEM` влияет на индексы `0` и `1`, но не пересчитывает уже сформированные индексы `2` и `3`.

## Кеширование

Компонент использует data-кеш `CPHPCache` без кеширования вывода. Идентификатор строится из всех `$arParams`, поэтому разные инфоблоки, фильтры, сортировки, модификаторы и версии получают разные записи.

`CACHE_TAG` исторически назван тегом, но фактически определяет каталог `/bitrix/cache/php/<cache_tag>/`. Управляемый тег инфоблока компонент не регистрирует, поэтому изменение элемента или раздела не сбрасывает этот кеш автоматически.

Кеш конкретного каталога можно очистить так:

```php
use Hipot\BitrixUtils\PhpCacher;

PhpCacher::clearDirByTag('catalog_left_menu_ext');
```

Если глобальная настройка `main/component_cache_on` равна `N`, компонент принудительно использует TTL `0`.

## Особенности

- компоненту требуется модуль `iblock`;
- значения `TYPE`, отличные от `elements` и `sections`, дают пустой результат;
- стандартный набор полей элемента: `ID`, `IBLOCK_ID`, `DETAIL_PAGE_URL`, `NAME`;
- стандартный набор полей раздела: `ID`, `IBLOCK_ID`, `CODE`, `SECTION_PAGE_URL`, `NAME`, `DEPTH_LEVEL`;
- `SELECT` расширяет эти наборы и включает выбранную запись в параметры пункта меню;
- компонент не имеет `.parameters.php`, его параметры задаются в PHP-коде `.menu_ext.php`.
