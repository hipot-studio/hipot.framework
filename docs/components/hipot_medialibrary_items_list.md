# `hipot:medialibrary.items.list`: элементы коллекций медиабиблиотеки

`hipot:medialibrary.items.list` — специализированный вариант выборочного конвейера для медиабиблиотеки Битрикса. Он получает элементы одного или нескольких альбомов, сортирует их по имени, при необходимости добавляет сведения из файловой таблицы, кеширует результат и передаёт `ITEMS` в шаблон.

По роли компонент похож на [`hipot:iblock.list`](hipot_iblock_list.md), но источник и набор операций уже: выборку выполняет `CMedialibItem::GetList`, произвольные `FILTER`, `SELECT` и `ORDER` не поддерживаются. Компонент подходит для галерей, списков документов и повторно используемых наборов файлов.

## Конвейер данных

1. Нормализует `COLLECTION_IDS` в массив.
2. Подключает модуль `fileman` и инициализирует медиабиблиотеку.
3. Получает элементы выбранных коллекций через `CMedialibItem::GetList`.
4. Сортирует `ITEMS` по `NAME` в естественном порядке.
5. При `SELECT_FILE_INFO => 'Y'` одним запросом получает метаданные файлов и индексирует их по ID.
6. Кеширует результат и подключает шаблон либо возвращает данные вызывающему коду.

## Параметры

| Параметр | Тип | По умолчанию | Описание |
|---|---:|---|---|
| `COLLECTION_IDS` | `int\|int[]` | `[]` | ID одной или нескольких коллекций медиабиблиотеки. Пустой массив выбирает элементы без ограничения по коллекции. |
| `SELECT_FILE_INFO` | `Y/N` | `N` | Добавить сведения из файловой таблицы в `$arResult['arFileInfo']`. |
| `ONLY_RETURN_ITEMS` | `Y/N` | `N` | Не подключать шаблон, отключить кеш и вернуть `$arResult` из `IncludeComponent`. |
| `CACHE_TYPE` | `A/N/Y` | стандартное | Тип стандартного кеша компонента. |
| `CACHE_TIME` | `int` | `36000` | Время кеширования результата в секундах. |

В визуальном редакторе компонент позволяет выбрать несколько коллекций. Для программного вызова ID можно передать числом или массивом.

## Результат

```php
$arResult = [
    'ITEMS' => [],
    'arFileInfo' => [], // Только при SELECT_FILE_INFO => 'Y'.
];
```

`ITEMS` содержит записи `CMedialibItem::GetList`, в том числе `ID`, `NAME`, `SOURCE_ID` и `PATH`. `SOURCE_ID` — ID файла; по нему находится дополнительная запись `$arResult['arFileInfo'][$item['SOURCE_ID']]` с размером, MIME-типом и другими полями файла.

## Пример: список документов из альбома

```php
<?php

/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'hipot:medialibrary.items.list',
    'documents.list',
    [
        'COLLECTION_IDS' => [$documentsCollectionId],
        'SELECT_FILE_INFO' => 'Y',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 36000,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Обезличенный шаблон списка документов:

```php
<?php
defined('B_PROLOG_INCLUDED') || die();

foreach ($arResult['ITEMS'] as $item):
    $file = $arResult['arFileInfo'][$item['SOURCE_ID']] ?? [];
    ?>
    <article>
        <a href="<?=htmlspecialcharsbx((string)$item['PATH'])?>" target="_blank" rel="noopener">
            <?=htmlspecialcharsbx((string)$item['NAME'])?>
        </a>
        <span>
            <?=htmlspecialcharsbx((string)($file['CONTENT_TYPE'] ?? ''))?>,
            <?=CFile::FormatSize((int)($file['FILE_SIZE'] ?? 0))?>
        </span>
    </article>
<?php endforeach; ?>
```

Тот же `$arResult['ITEMS']` можно отобразить как галерею: `PATH` использовать как адрес изображения, а `NAME` — как подпись или `alt` после экранирования.

## Получение данных без шаблона

Режим `ONLY_RETURN_ITEMS` позволяет использовать компонент как источник данных для другого компонента или включаемой области:

```php
<?php

$media = $APPLICATION->IncludeComponent(
    'hipot:medialibrary.items.list',
    '',
    [
        'COLLECTION_IDS' => [$collectionId],
        'SELECT_FILE_INFO' => 'Y',
        'ONLY_RETURN_ITEMS' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);

foreach ($media['ITEMS'] ?? [] as $item) {
    // Подготовка общего результата вызывающего компонента.
}
```

В этом режиме `CACHE_TIME` принудительно становится равным `0`. Если данные нужны на нескольких страницах, обычный вызов с шаблоном и компонентным кешем эффективнее.

## Разделение ответственности

- `COLLECTION_IDS` определяет источник элементов;
- компонент выполняет выборку, естественную сортировку и необязательную довыборку файлов;
- `result_modifier.php` пользовательского шаблона может подготовить модель галереи или списка документов;
- `template.php` отвечает за безопасный HTML-вывод.

При пустой выборке компонент отменяет кеш и не подключает шаблон. Отдельного параметра для шаблона пустого состояния у него нет, поэтому такое состояние следует обрабатывать во внешнем коде или использовать `ONLY_RETURN_ITEMS`.
