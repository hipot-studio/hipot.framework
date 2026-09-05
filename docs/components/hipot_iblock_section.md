# `hipot:iblock.section`: выборка разделов инфоблока

`hipot:iblock.section` переносит подход [`hipot:iblock.list`](hipot_iblock_list.md) на другое хранилище — разделы инфоблока. Параметры компонента задают сортировку, фильтр, поля и навигацию; компонент выполняет `CIBlockSection::GetList`, кеширует результат и передаёт разделы в шаблон.

Компонент подходит для рубрикаторов, каталогов разделов, вкладок, аккордеонов и других блоков, в которых основными сущностями являются разделы. Прикладная довыборка и подготовка данных выполняются в `result_modifier.php`, а HTML остаётся в `template.php`.

## Конвейер данных

1. Компонент добавляет базовый фильтр `IBLOCK_ID` и `ACTIVE => Y`.
2. Применяет `ORDER`, `FILTER` и `SELECT`.
3. При необходимости считает элементы отдельно для каждого раздела.
4. Помечает выбранный раздел и сохраняет его в `CUR_SECTION`.
5. Формирует постраничную навигацию, кеширует результат и подключает шаблон.
6. После кешируемой части может установить заголовок и хлебные крошки выбранного раздела.

## Параметры

| Параметр | Тип | По умолчанию | Описание |
|---|---:|---|---|
| `IBLOCK_ID` | `int` | — | ID инфоблока, разделы которого нужно выбрать. |
| `ORDER` | `array` | `['SORT' => 'ASC']` | Сортировка в формате `CIBlockSection::GetList`. |
| `FILTER` | `array` | `[]` | Дополнительный фильтр разделов. Объединяется с базовым фильтром компонента. |
| `SELECT` | `array` | `[]` | Выбираемые поля разделов, включая пользовательские поля `UF_*`. |
| `SELECTED_SECTION_ID` | `int` | `0` | ID текущего раздела. Найденный раздел получает `SELECTED => 'Y'` и записывается в `CUR_SECTION`. |
| `SELECTED_SECTION_CODE` | `string` | `''` | Символьный код текущего раздела, если ID не передан. |
| `SECTION_CODE_PATH` | `Y/N` | `N` | Извлечь последний сегмент из пути в `SELECTED_SECTION_CODE` и `FILTER['CODE']`. |
| `SELECT_COUNT` | `Y/N` | `N` | Выполнить дополнительный подсчёт элементов для каждого раздела. |
| `SELECT_COUNT_ELEM_FILTER` | `array` | `[]` | Дополнительный фильтр `CIBlockElement::GetList` для подсчёта. |
| `PAGESIZE` | `int` | `0` | Число разделов на странице. Значение `0` отключает навигацию. |
| `NAV_TEMPLATE` | `string` | `''` | Шаблон постраничной навигации. |
| `NAV_SHOW_ALWAYS` | `Y/N` | `N` | Показывать навигацию даже для одной страницы. |
| `NAV_SHOW_ALL` | `Y/N` | `N` | Разрешить режим «показать все». |
| `NAV_PAGEWINDOW` | `int` | — | Ширина диапазона номеров страниц. |
| `INCLUDE_SEO` | `Y/N` | `N` | Установить заголовок и добавить выбранный раздел в хлебные крошки. |
| `ADDON_PRE_CHAINS` | `array` | `[]` | Пункты хлебных крошек перед выбранным разделом: `[['TEXT' => '...', 'URL' => '...']]`. |
| `SET_404` | `Y/N` | `N` | Установить статус 404 при пустой выборке. |
| `INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS` | `Y/N` | `N` | Подключить шаблон, даже если разделы не найдены. |
| `CACHE_TIME` | `int` | `3600` | Время кеширования результата в секундах. |

`FILTER['SECTION_ID']` в текущей реализации обрабатывается как специальный запрос вложенных разделов, но этот путь помечен в исходном коде как незавершённый. Для рабочего сценария надёжнее заранее получить нужные ID разделов и передать их через `FILTER['ID']` либо использовать обычные поля древовидной структуры в фильтре.

## Результат

```php
$arResult = [
    'SECTIONS' => [],
    'CUR_SECTION' => null,
    'NAV_STRING' => null,
];
```

Каждый элемент `SECTIONS` — результат `CIBlockSection::GetList()->GetNext()`: обычные ключи содержат подготовленные значения, а ключи с префиксом `~` — исходные. Если включён `SELECT_COUNT`, добавляется `ELEMENT_CNT_FROM_ELEMS`. Выбранный раздел дополнительно получает `SELECTED => 'Y'`.

## Пример: список разделов

```php
<?php

/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'hipot:iblock.section',
    'catalog.sections',
    [
        'IBLOCK_ID' => $catalogIblockId,
        'ORDER' => [
            'SORT' => 'ASC',
            'NAME' => 'ASC',
        ],
        'FILTER' => [
            'DEPTH_LEVEL' => 1,
        ],
        'SELECT' => [
            'ID',
            'IBLOCK_ID',
            'NAME',
            'CODE',
            'SECTION_PAGE_URL',
            'UF_ICON',
        ],
        'SELECT_COUNT' => 'Y',
        'SELECT_COUNT_ELEM_FILTER' => [
            'ACTIVE_DATE' => 'Y',
        ],
        'PAGESIZE' => 20,
        'NAV_TEMPLATE' => '.default',
        'NAV_SHOW_ALWAYS' => 'N',
        'NAV_SHOW_ALL' => 'N',
        'SET_404' => 'N',
        'INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS' => 'Y',
        'CACHE_TIME' => 10800,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Шаблон использует единый контракт разделов:

```php
<?php
foreach ($arResult['SECTIONS'] as $section):
    ?>
    <a href="<?=htmlspecialcharsbx($section['SECTION_PAGE_URL'])?>">
        <?=htmlspecialcharsbx($section['~NAME'])?>
        <span><?= (int)$section['ELEMENT_CNT_FROM_ELEMS'] ?></span>
    </a>
<?php endforeach; ?>

<?=$arResult['NAV_STRING']?>
```

## Довыборка данных в `result_modifier.php`

Если представлению нужны элементы внутри каждого раздела, их можно добавить на уровне конкретного шаблона:

```php
<?php

foreach ($arResult['SECTIONS'] as &$section) {
    $section['ELEMENTS'] = [];

    $rows = CIBlockElement::GetList(
        ['SORT' => 'ASC'],
        [
            'IBLOCK_ID' => (int)$section['IBLOCK_ID'],
            'SECTION_ID' => (int)$section['ID'],
            'ACTIVE' => 'Y',
        ],
        false,
        ['nTopCount' => 10],
        ['ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL'],
    );

    while ($element = $rows->GetNext()) {
        $section['ELEMENTS'][] = $element;
    }
}
unset($section);
```

Такой код создаёт отдельную выборку для каждого раздела. Для большого числа разделов лучше выполнить одну общую выборку элементов и сгруппировать результат по `IBLOCK_SECTION_ID`, чтобы не получить N+1 запросов.

## Разделение ответственности

- параметры вызова описывают выборку разделов;
- `hipot:iblock.section` выполняет запрос, навигацию, кеширование и SEO выбранного раздела;
- `result_modifier.php` добавляет данные, нужные конкретному блоку;
- `template.php` отображает готовый `$arResult['SECTIONS']`.

Как и у `hipot:iblock.list`, собственный компонент нужен только при изменении самого алгоритма выборки. Для нового рубрикатора или виджета обычно достаточно другого набора параметров и нового шаблона.
