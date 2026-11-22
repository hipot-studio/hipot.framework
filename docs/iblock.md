# Инфоблоки — Hipot\BitrixUtils\Iblock

При загрузке [класса Iblock](../src/lib/classes/Hipot/BitrixUtils/Iblock.php) подключается модуль Bitrix `iblock`. Методы элементов и разделов предоставляют трейты [Element](../src/lib/classes/Hipot/BitrixUtils/Iblock/Element.php) и [Section](../src/lib/classes/Hipot/BitrixUtils/Iblock/Section.php). В примерах `IblockUtils` — псевдоним при импорте. Для объектной выборки связанных элементов см. [Abstract Iblock Elements Layer](ABSTRACT_IBLOCK_ELEMENT_LAYER.MD).

## Поиск инфоблока и описание свойств

- `getIdByCode(string $code, ?string $type = null): ?int` возвращает ID инфоблока по `CODE` и, если передан тип, по `IBLOCK_TYPE_ID`.
- `getInfoByCode(string $code, ?string $type = null): ?array` выполняет тот же поиск и возвращает поля `ID`, `IBLOCK_TYPE_ID`, `CODE`, `XML_ID`, `NAME`, `ACTIVE`, `SORT`, `VERSION`. Поля `ID`, `SORT` и `VERSION` приводятся к `int`. Оба метода удаляют пробелы по краям аргументов, возвращают `null` для пустого кода, явно переданного пустого типа и отсутствующего инфоблока. При совпадениях выбирается наименьший ID, включая неактивные инфоблоки.
- `getPropertiesByCode(int $iblockId, bool $activeOnly = true): array` возвращает метаданные свойств по символьным кодам. При `false` включает неактивные свойства. Коды без содержимого пропускаются, а при повторе кода остаётся последняя строка в порядке `SORT ASC, ID ASC`. Для неположительного ID возвращается `[]`.
- `clearPropertiesByCodeCache(): void` очищает локальные карты свойств и их вариантов. `clearPropertyEnumCache(): void` очищает только карту вариантов. Константа `CACHE_TTL` задаёт время жизни ORM-кеша в 3600 секунд. Правила сброса после изменений описаны в [документации кеша](reference_data_cache.md#кеш-справочных-данных-инфоблоков).

```php
use Hipot\BitrixUtils\Iblock as IblockUtils;

$iblockId = IblockUtils::getIdByCode('news', 'content');
if ($iblockId !== null) {
    $properties = IblockUtils::getPropertiesByCode($iblockId);
}
```

## Выборка элементов и разделов

- `selectElementsByFilter($arOrder, $arFilter, $arGroupBy = false, $arNavParams = false, $arSelect = [])` передаёт аргументы в `CIBlockElement::GetList()` и возвращает штатный результат, включая число для подсчёта.
- `selectElementsByFilterArray()` принимает те же первые пять аргументов, добавляет `ID` и `IBLOCK_ID` к списку полей и читает строки через `GetNext()`. Если найден один элемент, по умолчанию возвращает его строку. Передайте `returnOneIfOnlyOneSelected: false`, чтобы получить список строк. При отсутствии строк возвращает `[]`, при подсчёте передаёт число из Bitrix.
- `selectSectionsByFilter($arOrder, $arFilter, bool $bIncCnt = false, $arSelect = [], $arNavStartParams = false)` добавляет `ID` и `IBLOCK_ID` и возвращает штатный результат `CIBlockSection::GetList()`. `selectSectionsByFilterArray()` с теми же аргументами возвращает `[]`, одну строку или список строк в зависимости от числа результатов. У секций сворачивание одной строки отключить параметром нельзя.
- `getElementIblockId(int $elementId): int` возвращает ID инфоблока элемента или `0` при отсутствии элемента.
- `getElementSections(int $elementId): array` возвращает напрямую назначенные разделы в порядке `SORT ASC, ID ASC`, включая неактивные. Привязки через свойства исключаются. `getElementSectionPaths(int $elementId): array` для каждого такого раздела возвращает список ID от корня до самого раздела. Оба метода возвращают `[]` для элемента без прямых привязок.
- `selectSubsectionByParentSection(array $parentSectionFilter = [], array $arSelect = ['ID'], array $filter = ['ACTIVE' => 'Y']): array` берёт первый раздел по фильтру в порядке `SORT ASC` и возвращает его потомков по границам дерева. Если родитель не найден, возвращает `[]`. Пустой фильтр приводит к `RuntimeException`.
- `getNextPrevElementsByElementId(int $ELEMENT_ID, $rowSort = [], $prevNextSelect = [], $dopFilter = ['ACTIVE' => 'Y'], $cntSelect = 1)` ищет соседей через навигацию `nElementID`, по умолчанию с сортировкой `SORT ASC`. Возвращает `false` для неположительного ID или отсутствующего инфоблока, `[]`, если текущий элемент не вошёл в выборку, либо массив с `CURRENT` и найденными соседями в `PREV_NEXT`, `PREV` и `NEXT`.
- `queryIblockItemsSearch(string $query, $iblockId): array` ищет по индексу модуля `search` и возвращает значения `ITEM_ID` как их отдаёт поиск, в порядке убывания `RANK`. Ищет в заданном инфоблоке, исключает разделы, удаляет кавычки из запроса. При недоступном модуле возвращает `[]`.

```php
$items = IblockUtils::selectElementsByFilterArray(
    ['SORT' => 'ASC', 'ID' => 'ASC'],
    ['IBLOCK_ID' => $iblockId, 'ACTIVE' => 'Y', 'CHECK_PERMISSIONS' => 'Y'],
    arNavParams: ['nTopCount' => 10],
    arSelect: ['NAME', 'DETAIL_PAGE_URL'],
    returnOneIfOnlyOneSelected: false,
);
```

## Свойства элемента и варианты списков

`selectElementProperties($ID, $IBLOCK_ID = 0, $onlyValue = false, $exFilter = [], $obChainBuilder = null, $selectChainsDepth = IblockElemLinkedChains::DEFAULT_SELECT_LEVEL, $initializeChainBuilder = true)` читает по умолчанию непустые свойства в порядке сортировки. Если инфоблок не передан, определяет его по элементу. Для неположительного ID элемента или невозможности определить инфоблок возвращает `false`. Ключи результата — коды свойств, а при отсутствии кода используются ID. Для множественного свойства возвращается список. При `$onlyValue = true` возвращаются только значения. В полном режиме файловые свойства содержат `FILE_PARAMS`, а свойства привязки могут содержать `CHAIN`, если передать построитель цепочек.

Флаг `SelectAllProps: true` у `selectElementsByFilterArray()` добавляет отдельную загрузку свойств на каждую строку. `OnlyPropsValue` по умолчанию равен `true`. При `bSelectChains: true` метод загружает полные данные свойств и строит цепочки до глубины `selectChainsDepth`.

- `selectPropertyEnumArray(string|int $propCode, array $arFilterEx = [], array $aSort = ['DEF' => 'DESC', 'SORT' => 'ASC'], bool $indexed = true): array` читает варианты списка по ID свойства для числового аргумента или по коду. Дополнительный фильтр может задавать `IBLOCK_ID`. По умолчанию ключи результата — ID вариантов. При `$indexed = false` возвращается обычный список.
- `getEnumIdByXmlId(int $iblockId, ?string $xmlId, string $propertyCode): ?int` возвращает ID варианта. `getEnumValueByXmlId(int $iblockId, ?string $xmlId, string $propertyCode): ?string` возвращает его текст. `getEnumXmlIdById(int $iblockId, ?int $id, string $propertyCode): ?string` возвращает `XML_ID` по ID варианта. В этих трёх методах `null` вместо искомого ID или `XML_ID` выбирает вариант с `DEF = Y`. Если нужного варианта нет, результат — `null`. При совпадении `XML_ID` или нескольких значениях по умолчанию используется порядок `DEF DESC, SORT ASC, ID ASC`.
- `setEnumPropertyByXmlId(int $elementId, int $iblockId, string $propertyCode, ?string $xmlId): Bitrix\Main\Result` записывает одиночный вариант, а `null` очищает значение. Для записи проверяются аргументы и принадлежность элемента инфоблоку. Для непустого `XML_ID` проверяется наличие варианта. Успешный результат содержит `elementId`, `propertyCode` и `enumId`. Штатная запись через `SetPropertyValuesEx()` вызывается без последующего чтения сохранённого значения.
- `checkExistsEnumByVal(string $propCode, int $iblockId, $val)` ищет вариант по тексту и при отсутствии пытается добавить его. Возвращает ID варианта или `false` для пустого текста, отсутствующего свойства или ошибки добавления. Локальная карта хранится отдельно для каждого инфоблока и кода свойства.
- `returnCml2AttributesFromPropVal(array $propCml2Value): array` строит карту `[DESCRIPTION => VALUE]` из строк множественного свойства CommerceML. Пробелы по краям удаляются, пустые значения пропускаются, поздняя строка заменяет значение с таким же описанием.
- `addMultipleFileValue(int $ID, int $IBLOCK_ID, $arFiles = [], string $prop_code = '')` — метод экземпляра. Принимает карту `[источник файла => описание]`, повторно собирает имеющиеся файлы через `CFile::MakeFileArray()` и записывает итоговый набор во множественное свойство. Возвращает `true` или строку `'partial'` при частичной подготовке файлов. Для каждого нового файла выполняется до десяти попыток подготовки.

## Запись элементов и разделов

При успешной записи методы ниже возвращают `Hipot\Types\UpdateResult` со статусом `STATUS_OK` и ID в поле `RESULT`. При ошибке вызова Bitrix возвращается `STATUS_ERROR` с текстом ошибки в `RESULT`.

| Метод | Поведение |
| --- | --- |
| `addElementToDb(array $arAddFields = [], bool $bUpdateSearch = false)` | Добавляет элемент. Значения свойств передаются в `PROPERTY_VALUES`. |
| `updateElementToDb(int $ID, array $arAddFields = [], bool $bUpdateSearch = false)` | Обновляет поля, затем отдельно записывает `PROPERTY_VALUES` через `SetPropertyValuesEx()` и обновляет индекс свойств, если обновление полей успешно. |
| `addSectionToDb(array $arAddFields = [], bool $bResort = true, bool $bUpdateSearch = false)` | Добавляет раздел. По умолчанию перестраивает дерево. |
| `updateSectionToDb(int $ID, array $arAddFields = [], bool $bResort = true, bool $bUpdateSearch = false)` | Обновляет раздел. По умолчанию перестраивает дерево. |

Обе обёртки обновления при `$ID <= 0` возвращают `UpdateResult` со статусом `STATUS_ERROR`.

- `checkExistsByNameOrCode(string $field, int $iblockId, string $fieldType = 'name', string $table = 'b_iblock_element'): bool|int` возвращает ID первой найденной записи без заданной сортировки либо `false`. `checkElementExistsByNameOrCode()`, `checkSectionExistsByNameOrCode()` и `checkPropertyExistsByNameOrCode()` фиксируют таблицу соответственно для элементов, разделов и свойств. Поддерживаемые распространённые поля поиска — `name`, `code` и `xml_id`. Эти методы проверяют наличие записи через прямой запрос, без фильтра активности и прав.
- `addToHelperAndReturnElementId(string $fieldVal, int $iblockId)` ищет элемент справочника по имени и, если его нет, создаёт активный элемент с транслитерированным кодом и случайным суффиксом. Возвращает ID либо `false` при некорректных аргументах или ошибке добавления. Повторный вызов с тем же именем и ID инфоблока использует локальный кеш. Поиск и добавление выполняются отдельно.
- `setGroupToElement(int $elId, int $sectionId): bool` добавляет раздел к текущим прямым привязкам элемента через `SetElementSection()`. При неположительном ID элемента или раздела возвращает `false`. Значение `true` означает, что метод дошёл до вызова записи.

## Пакетное обновление и тегированный кеш

- `startMultipleElemsUpdate(int $iblockId): void` включает отложенную индексацию свойств, при доступном модуле `catalog` включает отложенный расчёт SKU и отключает сброс тегированного кеша для указанного инфоблока.
- `endMultipleElemsUpdate(int $iblockId): void` снова включает сброс тегов для этого инфоблока, очищает его тег, запускает расчёт SKU при доступном каталоге и выполняет отложенную индексацию. Обрамляйте пакет одной парой вызовов для одного ID. Это управление индексами и кешем, а изменения данных сохраняются при каждом вызове записи.
- `disableIblockCacheClear(): bool` и `enableIblockCacheClear(): bool` управляют общим состоянием сброса тегов `CIBlock`, без привязки к ID. Обе обёртки возвращают `true` после переключения. `enableIblockCacheClear()` включает возможность сброса, а сам кеш нужно очищать соответствующим вызовом.

## SEO, панель редактирования и цена

- `returnSeoValues(int $IBLOCK_ID, int $ID): array` и `returnSectionSeoValues(int $IBLOCK_ID, int $ID): array` возвращают вычисленные наследуемые SEO-поля элемента и раздела.
- `setSeoValues(int $IBLOCK_ID, int $ID, array $arTemplates = []): void` и `setSectionSeoValues(int $IBLOCK_ID, int $ID, array $arTemplates = []): void` сохраняют шаблоны наследуемых полей. Для метатегов используются ключи `ELEMENT_META_TITLE`, `ELEMENT_META_DESCRIPTION`, `ELEMENT_META_KEYWORDS` и соответствующие ключи с префиксом `SECTION_`.
- `setElementPanelButtons(array &$element): void` добавляет `EDIT_LINK` и `DELETE_LINK` в массив элемента с полями `ID`, `IBLOCK_ID`, `IBLOCK_SECTION_ID`. `setSectionPanelButtons(array &$section): void` добавляет такие же поля в массив раздела с `ID` и `IBLOCK_ID`. `addComponentPanelButtons(\CBitrixComponent $component, array $arResult): void` добавляет действия через `UUtils::setComponentEdit()` с отключёнными кнопками раздела.
- `getElementMinPrice(array &$element, array $priceCodes = ['BASE'], bool $bVATInclude = true): array` рассчитывает цены через `CIBlockPriceTools` в валюте из опции `sale/default_currency` (по умолчанию `RUB`). При необходимости перечитывает и заменяет переданный массив элемента, записывает `PRICES` и при наличии цен — `MIN_PRICE`. Возвращает минимальную цену либо `[]`. Неположительный ID или пустой список кодов цен вызывает `InvalidArgumentException`. Метаданные типов цен кешируются отдельно для каждого сочетания ID инфоблока и списка кодов.
