# Кеширование и поиск справочных данных

### Поиск типов цен

`Hipot\BitrixUtils\PriceType` ищет типы цен по точному полю `NAME` таблицы `b_catalog_group`.
Это символьный код, а не локализованное название и не `XML_ID`.

```php
use Hipot\BitrixUtils\PriceType;

$priceTypeId = PriceType::getIdByCode('RETAIL'); // ?int
$priceType = PriceType::getInfoByCode('RETAIL'); // ?array
```

Пробелы по краям удаляются. Для пустого кода или ненайденного типа возвращается `null`.
При дублировании кода выбирается минимальный ID. Метаданные содержат `ID`, `NAME`, `BASE`, `SORT`, `XML_ID`;
`ID` и `SORT` приводятся к `int`. Права доступа к ценам этим поиском не проверяются.

ORM-кеш использует `PriceType::CACHE_TTL` (3600 секунд). Отдельного кеша в памяти нет.
Для непустого кода загружается обязательный модуль `catalog`; если он недоступен, выбрасывается `LoaderException`.
Штатный API `CCatalogGroup` в проверенном ядре очищает ORM-кеш.
После прямой записи SQL вызывайте `Bitrix\Catalog\GroupTable::cleanCache()`.
Работа на реальном Bitrix и влияние на производительность не проверялись.

### Поиск групп пользователей

`Hipot\BitrixUtils\UserGroup` ищет группы по точному значению поля `STRING_ID`:

```php
use Hipot\BitrixUtils\UserGroup;

$groupId = UserGroup::getIdByCode('EDITORS'); // ?int
$group = UserGroup::getInfoByCode('EDITORS'); // ?array
```

Пробелы по краям кода удаляются. Для пустого кода или ненайденной группы возвращается `null`.
Учитываются и неактивные группы; при дублировании `STRING_ID` выбирается группа с минимальным ID.
Метаданные содержат `ID`, `STRING_ID`, `NAME`, `DESCRIPTION`, `ACTIVE`, `C_SORT`;
`ID` и `C_SORT` приводятся к `int`. Это поиск справочника, а не проверка прав пользователя.

ORM-кеш имеет TTL `UserGroup::CACHE_TTL` (3600 секунд); отдельного кеша в памяти нет.
Штатные `CGroup::Add/Update/Delete` в проверенном ядре используют ORM-запись и её сброс кеша.
После прямой записи SQL сбрасывайте ORM-кеш через `Bitrix\Main\GroupTable::cleanCache()`.
Реальный кеш на Bitrix и влияние на производительность не проверялись.

### Кеш справочных данных инфоблоков

`Hipot\BitrixUtils\Iblock::CACHE_TTL` задаёт TTL ORM-кеша (3600 секунд) для
`getInfoByCode()` (также вызывается из `getIdByCode()`), `getPropertiesByCode()`
и поиска значений списка через `getEnumIdByXmlId()`, `getEnumXmlIdById()`, `getEnumValueByXmlId()`.

Карта свойств повторно используется в памяти запроса отдельно для каждого инфоблока
и режима `activeOnly`. Значения списка загружаются общей картой для пары «инфоблок + код свойства»;
поиск по ID, XML_ID и значения по умолчанию использует одну загрузку. Пустые карты также запоминаются.

При подключённом `lib/handlers_add.php` события добавления, изменения и удаления свойств
сбрасывают локальные карты. Если обработчики не подключены, после изменения свойств вызовите
`Iblock::clearPropertiesByCodeCache()` — он очищает обе карты в памяти.
После прямого изменения вариантов списка через `CIBlockPropertyEnum` перед повторным чтением
в том же запросе вызовите `Iblock::clearPropertyEnumCache()`.
Эти методы сбрасывают только память: ORM-кеш инвалидируется штатными API Bitrix.
При прямой записи SQL нужно отдельно сбросить ORM-кеш затронутых таблиц.
В долгоживущем процессе очищайте локальные карты между заданиями.

Работа с ORM-кешем на реальном Bitrix и влияние на производительность не измерялись.

### Поиск highload-блоков

`Hipot\BitrixUtils\HiBlock` предоставляет единый nullable-контракт:

- `getInfoByName(string $name): ?array` — метаданные по точному `NAME`, пробелы по краям удаляются;
- `getIdByName(string $name): ?int` — ID по `NAME`;
- `getInfoById(int $id): ?array` — метаданные по положительному ID;
- `getDataManager(int|string $identifier): ?string` — имя класса DataManager, не экземпляр.

При отсутствии блока или пустом имени новые методы возвращают `null`; поле `ID` в метаданных — `int`.
В `getDataManager()` целое число означает ID, строка — имя, даже если содержит только цифры.
Ошибки Bitrix/БД не преобразуются в `null`. Выборки используют ORM-кеш с `HiBlock::CACHE_TTL`.

```php
$id = HiBlock::getIdByName('Countries');
$info = HiBlock::getInfoById(3);
$dataClass = HiBlock::getDataManager('Countries');
if ($dataClass !== null) {
    $rows = $dataClass::query()->setLimit(10)->fetchAll();
}
```

Методы `getHightloadBlockTable()`, `getDataManagerByHiId()` и `getDataManagerByHiCode()`
сохранены с прежними сигнатурами и поведением. В частности, старый поиск по имени не удаляет пробелы,
а `getHightloadBlockTable()` без критериев возвращает `null`, при ненайденном блоке в режиме метаданных — `false`.
Старые методы компиляции не гарантируют безопасный результат при ненайденном блоке.
Их прежний PHPDoc ошибочно указывал объект DataManager: фактически возвращается имя класса.
