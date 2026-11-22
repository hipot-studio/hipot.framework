# Кеширование и поиск справочных данных

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
