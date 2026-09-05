# `hipot:medialibrary.collection.list`: выборка коллекций медиабиблиотеки

`hipot:medialibrary.collection.list` переносит общий подход семейства выборочных компонентов на коллекции медиабиблиотеки Битрикса. Параметры задают сортировку и фильтр, компонент выполняет `CMedialibCollection::GetList`, кеширует результат и передаёт массив `COLLECTIONS` в шаблон.

Компонент подходит для списка альбомов, рубрикатора документов, меню разделов медиабиблиотеки и блоков вида «коллекция с её файлами». Во втором случае он составляет пайплайн с [`hipot:medialibrary.items.list`](hipot_medialibrary_items_list.md): получает элементы медиабиблиотеки, группирует их по `COLLECTION_ID` и добавляет в соответствующие коллекции.

## Конвейер данных

1. Использует сортировку `ID => ASC` и базовый фильтр `ACTIVE => Y` либо применяет переданные `ORDER` и `FILTER`.
2. При `SELECT_WITH_ITEMS => 'Y'` вызывает дочерний компонент элементов в режиме `ONLY_RETURN_ITEMS`.
3. Группирует полученные элементы по `COLLECTION_ID`.
4. Загружает коллекции через `CMedialibCollection::GetList`.
5. При необходимости добавляет каждой коллекции собственный массив `ITEMS`.
6. Кеширует общий результат и подключает шаблон.

В отличие от [`hipot:iblock.list`](hipot_iblock_list.md), этот компонент не поддерживает произвольный `SELECT`, постраничную навигацию и мутацию callback-функцией. Подготовку данных для конкретного представления следует выполнять в `result_modifier.php` шаблона.

## Требования

- установлен и доступен модуль `fileman`;
- медиабиблиотека и нужные коллекции созданы в административной части Битрикса;
- для режима с элементами доступен `hipot:medialibrary.items.list` либо совместимый компонент, указанный в `ITEMS_LIST_COMPONENT_NAME`.

## Параметры

| Параметр | Тип | По умолчанию | Описание |
|---|---:|---|---|
| `ORDER` | `array` | `['ID' => 'ASC']` | Сортировка в формате `CMedialibCollection::GetList`. |
| `FILTER` | `array` | `['ACTIVE' => 'Y']` | Дополнительный фильтр коллекций. Переданные условия объединяются с базовым фильтром и могут заменить `ACTIVE`. |
| `SELECT_WITH_ITEMS` | `Y/N` | `N` | Добавить в каждую коллекцию принадлежащие ей элементы медиабиблиотеки. |
| `ITEMS_LIST_COMPONENT_NAME` | `string` | `hipot:medialibrary.items.list` | Имя дочернего компонента для получения элементов. Используется только при `SELECT_WITH_ITEMS => 'Y'`. |
| `CACHE_TYPE` | `A/N/Y` | стандартное | Тип стандартного кеша компонента. |
| `CACHE_TIME` | `int` | — | Время кеширования результата в секундах. Рекомендуется задавать явно. |

Поддерживаемые поля фильтра коллекций: `ID`, `NAME`, `DESCRIPTION`, `ACTIVE`, `DATE_UPDATE`, `OWNER_ID`, `PARENT_ID`, `SITE_ID`, `KEYWORDS`, `ITEMS_COUNT` и `ML_TYPE`.

Произвольные дополнительные параметры не участвуют в выборке, но доступны в `$arParams` шаблона. Например, параметр `SELECTED_COLLECTION_ID` можно использовать для подсветки текущего пункта меню.

## Результат

Без довыборки элементов компонент возвращает:

```php
$arResult = [
    'COLLECTIONS' => [
        [
            'ID' => 10,
            'NAME' => 'Документы',
            'DESCRIPTION' => 'Файлы для скачивания',
            'PARENT_ID' => 0,
            'ITEMS_COUNT' => 5,
            // Остальные поля CMedialibCollection::GetList.
        ],
    ],
];
```

При `SELECT_WITH_ITEMS => 'Y'` в каждой записи появляется вложенный список:

```php
$arResult['COLLECTIONS'][0]['ITEMS'] = [
    [
        'ID' => 100,
        'COLLECTION_ID' => 10,
        'NAME' => 'Инструкция',
        'PATH' => '/upload/medialibrary/example/manual.pdf',
        // Остальные поля CMedialibItem::GetList.
    ],
];
```

У коллекции без элементов ключ `ITEMS` в текущей реализации может содержать `null`. В пользовательском шаблоне безопасно нормализовать его выражением `(array)($collection['ITEMS'] ?? [])`.

## Пример: коллекции вместе с файлами

```php
<?php

/** @global CMain $APPLICATION */

$APPLICATION->IncludeComponent(
    'hipot:medialibrary.collection.list',
    'documents.collections',
    [
        'ORDER' => [
            'NAME' => 'ASC',
        ],
        'FILTER' => [
            'PARENT_ID' => 0,
            'SITE_ID' => $siteId,
        ],
        'SELECT_WITH_ITEMS' => 'Y',
        'ITEMS_LIST_COMPONENT_NAME' => 'hipot:medialibrary.items.list',
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 36000,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Обезличенный шаблон:

```php
<?php
defined('B_PROLOG_INCLUDED') || die();

foreach ($arResult['COLLECTIONS'] ?? [] as $collection):
    $items = (array)($collection['ITEMS'] ?? []);
    ?>
    <section>
        <h2><?=htmlspecialcharsbx((string)$collection['NAME'])?></h2>

        <?php if ((string)($collection['DESCRIPTION'] ?? '') !== ''): ?>
            <p><?=htmlspecialcharsbx((string)$collection['DESCRIPTION'])?></p>
        <?php endif; ?>

        <?php if ($items !== []): ?>
            <ul>
                <?php foreach ($items as $item): ?>
                    <li>
                        <a href="<?=htmlspecialcharsbx((string)$item['PATH'])?>">
                            <?=htmlspecialcharsbx((string)$item['NAME'])?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
```

## Пример: меню коллекций без файлов

Если для блока нужны только коллекции, не включайте `SELECT_WITH_ITEMS`:

```php
<?php

$APPLICATION->IncludeComponent(
    'hipot:medialibrary.collection.list',
    'collections.menu',
    [
        'ORDER' => ['NAME' => 'ASC'],
        'FILTER' => [
            'PARENT_ID' => $parentCollectionId,
            'ACTIVE' => 'Y',
        ],
        'SELECT_WITH_ITEMS' => 'N',
        'SELECTED_COLLECTION_ID' => (int)$selectedCollectionId,
        'CACHE_TYPE' => 'A',
        'CACHE_TIME' => 36000,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

В шаблоне текущая коллекция определяется по `$arParams['SELECTED_COLLECTION_ID']`, а пункты строятся из `$arResult['COLLECTIONS']`.

## Дочерний компонент элементов

Стандартный `hipot:medialibrary.items.list` вызывается без `COLLECTION_IDS`. Поэтому он получает элементы всей медиабиблиотеки одним запросом, после чего родитель группирует их в памяти и оставляет только группы выбранных коллекций. Это устраняет отдельный запрос для каждой коллекции, но при большой медиабиблиотеке увеличивает объём загружаемых данных.

Компонент из `ITEMS_LIST_COMPONENT_NAME` должен соблюдать следующий контракт:

- принимать `ONLY_RETURN_ITEMS => 'Y'`;
- не подключать шаблон в этом режиме;
- возвращать массив с ключом `ITEMS`;
- добавлять каждому элементу `COLLECTION_ID`.

Родитель передаёт дочернему компоненту только `ONLY_RETURN_ITEMS` и `CACHE_TIME => 0`. Если нужны дополнительные сведения о файлах или более узкая выборка, потребуется совместимый дочерний компонент с собственной логикой.

## Кеширование и пустая выборка

В режиме `SELECT_WITH_ITEMS => 'Y'` дочерний компонент работает без собственного кеша. Коллекции и вложенные элементы сохраняются вместе в кеше `hipot:medialibrary.collection.list`, заданном через `CACHE_TYPE` и `CACHE_TIME`.

После изменения коллекций или файлов кеш компонента нужно очистить либо дождаться окончания TTL. Для явной очистки можно использовать:

```php
CBitrixComponent::clearComponentCache('hipot:medialibrary.collection.list');
```

Если коллекции не найдены, компонент отменяет запись кеша и не подключает шаблон. Отдельного режима для вывода пустого состояния у него нет.

У компонента нет `.parameters.php`, поэтому параметры вызова задаются в PHP-коде. При включённой довыборке текущая реализация ожидает, что дочерний компонент всегда вернёт ключ `ITEMS`; полностью пустая медиабиблиотека может привести к предупреждению об отсутствующем ключе.

## Разделение ответственности

- `ORDER` и `FILTER` задают выборку коллекций;
- `hipot:medialibrary.collection.list` выполняет запрос, связывает коллекции с элементами и кеширует общий результат;
- `hipot:medialibrary.items.list` служит источником вложенных элементов;
- `result_modifier.php` готовит данные конкретного блока;
- `template.php` отвечает за безопасный HTML-вывод.
