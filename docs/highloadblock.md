# Highload-блоки и реестр настроек

[Hipot\BitrixUtils\HiBlock](../src/lib/classes/Hipot/BitrixUtils/HiBlock.php) подключает модуль Bitrix `highloadblock` при загрузке файла. [Hipot\BitrixUtils\HiBlockApps](../src/lib/classes/Hipot/BitrixUtils/HiBlockApps.php) наследует его методы и добавляет установку схемы, чтение реестра настроек и вывод объявления.

## HiBlock

### Поиск блока и чтение строк

- `getInfoByName(string $name): ?array` ищет метаданные по системному `NAME`, удаляя пробелы по краям. `getInfoById(int $id): ?array` ищет по положительному ID. Оба метода приводят `ID` результата к `int` и возвращают `null` для некорректного аргумента или отсутствующего блока.
- `getIdByName(string $name): ?int` извлекает ID из результата поиска по имени.
- `getDataManager(int|string $identifier): ?string` возвращает имя ORM-класса для работы со строками блока. Целое число означает ID, любая строка означает системное имя. Если блока нет, возвращается `null`. Ошибки Bitrix и компиляции сущности могут передаваться вызывающему коду.

```php
use Hipot\BitrixUtils\HiBlock;

$dataClass = HiBlock::getDataManager('Countries');
if ($dataClass !== null) {
    $rows = $dataClass::getList([
        'select' => ['ID', 'UF_NAME'],
        'order'  => ['ID' => 'ASC'],
        'limit'  => 10,
    ])->fetchAll();
}
```

- `getList(array $filter = [], array $select = ['*'], bool $getProps = true, bool $useCache = false): array` перечисляет сами HL-блоки, сортируя их по ID. Добавляет `LOC` с локализацией для текущего языка или резервным `['NAME' => системное имя]`. Для собственной проекции включайте `ID` и `NAME`. При `$getProps = true` добавляет `PROPERTIES` с пользовательскими полями и `VALUE_LIST` для списков. Текущая реализация загружает определения всех пользовательских полей HL-блоков внутри цикла по блокам. Для списка без полей передайте `getProps: false`.
- `getEnumPropertyValues(int $ufPropertyId): array` возвращает список строк вариантов пользовательского поля по его ID, отсортированных по `VALUE ASC`.
- `getLanguageId(): string` возвращает `LANGUAGE_ID` из контекста Bitrix. Метод помечен как внутренний.

Поиск отдельного блока использует ORM-кеш с `CACHE_TTL = 3600` секунд. В `getList()` параметр `useCache` включает такой же TTL для списка блоков и локализаций. См. также [кеширование справочных данных](reference_data_cache.md#поиск-highload-блоков).

### Прежние методы поиска

- `getHightloadBlockTable(int $hlblockId, string $hiBlockName, bool $returnEntityClass = false)` отдаёт приоритет положительному ID. При поиске по имени пробелы учитываются в самом запросе. В режиме метаданных возвращает строку таблицы, `false` при отсутствии совпадения или `null`, если поиск пропущен. При `$returnEntityClass = true` компилирует результат и возвращает имя класса. В проверенном ядре компиляция отсутствующего блока приводит к исключению.
- `getDataManagerByHiId($hlblockId)` и `getDataManagerByHiCode(string $hiBlockName)` возвращают `false` для неположительного ID и пустого имени соответственно. При остальных аргументах работают через режим компиляции `getHightloadBlockTable()`. Для проверки наличия блока удобнее nullable-контракт `getDataManager()`.

### Схема блока

- `addHiBlock(string $hiBlockName, string $tableName, array $addField = []): AddResult` сохраняет метаданные через `HighloadBlockTable::add()`. Ключи `$addField` могут переопределить `NAME` и `TABLE_NAME`. Проверяйте успех через `isSuccess()`, затем получайте ID через `getId()`.
- `addHiBlockField(array $arFields)` принимает `HLBLOCK_ID` и `CODE` без префикса `UF_`. Добавляет поле через `CUserTypeEntity` и возвращает его ID или `false`. `TYPE` принимает строковый идентификатор Bitrix или `UserFieldTypes`. Пустой или отсутствующий тип означает строку. По умолчанию `SORT = 500`, `REQUIRED`, `MULTIPLE`, `IS_SEARCHABLE` равны `N`, а `SHOW_FILTER`, `SHOW_IN_LIST`, `EDIT_IN_LIST` равны `Y`. Настройки по умолчанию — `['SIZE' => 60, 'ROWS' => 2]`. `NAME` (по умолчанию код) и `HELP` задают подписи и подсказку текущего языка. Можно передать `XML_ID`.
- `deleteUserField(int $hlId, string $fieldName): void` ищет поле по `HLBLOCK_<ID>` и полному имени с префиксом `UF_`. Если поле найдено, вызывает штатное удаление. Результат удаления не возвращается.

## HiBlockApps

### Установка

- `installHiBlock(string $hiBlockName, string $tableName, array $fields, ?string $displayName = null): array` создаёт блок, добавляет имя для текущего языка и последовательно создаёт поля. Каждый элемент `$fields` принимает параметры `addHiBlockField()`. ID блока проставляется автоматически. Результат — карта `[CODE => ID поля]`. Пустые имена блока или таблицы, пустой код поля и отсутствие положительного ID созданного блока или поля приводят к `RuntimeException`. Результат записи локализации отдельно не проверяется. При ошибке уже созданные части схемы остаются в базе.
- `installCustomSettingsHiBlock(string $tableName = self::CS_TABLE_NAME, string $hiBlockName = self::CS_HIBLOCK_NAME): array` устанавливает реестр. По умолчанию используются таблица `hi_custom_settings` и системное имя `CustomSettings`. Возвращает карту ID полей по кодам `NAME`, `CODE`, `VALUE`.

В реестре создаются три строковых поля. `UF_NAME` содержит подпись настройки, `UF_CODE` служит ключом, `UF_VALUE` хранит значение. Имя и код обязательны, значение может быть пустым. Установка создаёт схему. Записи с настройками добавляются отдельно.

### Чтение и сброс кеша

`getCustomSettingsList(string $hiBlockName = self::CS_HIBLOCK_NAME, int $cacheTtl = self::CACHE_TTL): bool|array` возвращает карту `[UF_CODE => UF_VALUE]`. Код должен быть уникален на уровне данных проекта. При дубликате поздняя строка перезаписывает значение, а порядок среди одинаковых кодов не задан. На промахе кеша указанный блок должен существовать и содержать оба поля. Отрицательный TTL приводит к `false` от [PhpCacher](cacher.md).

```php
use Hipot\BitrixUtils\HiBlockApps;

$settings = HiBlockApps::getCustomSettingsList();
$supportEmail = is_array($settings) ? ($settings['support_email'] ?? '') : '';
```

Чтение использует отдельный ключ для каждого имени блока и общий тег `CS_CACHE_TAG = 'hi_custom_settings'`. Сброс тега очищает кеш всех реестров. Для чтения одного значения из стандартного реестра доступна [функция __hiCs()](functions.md#для-работы-с-hl-блоками). Она хранит первую загруженную карту до конца текущего выполнения PHP и применяет непустое значение по умолчанию также к значениям, которые PHP считает пустыми, в том числе к строке `'0'`.

- `clearCustomSettingsCacheHandler(Event $event): void` завершает работу, если среди результатов события есть результат другого типа, кроме `SUCCESS`. Иначе очищает кеш реестра, кеш компонента `hipot:hiblock.list` и ORM-кеш сущности, а при композитном хранилище Memcached удаляет весь композитный кеш. Стандартная регистрация в [hi_block.php](../src/lib/handlers/hi_block.php) подписывает метод на `CustomSettingsOnAfterAdd`, `CustomSettingsOnAfterUpdate` и `CustomSettingsOnAfterDelete`. Для другого имени блока события регистрируются отдельно.
- `clearEntityOrmCacheHandler(Event $event): void` вызывает `cleanCache()` у сущности события, если та предоставляет этот метод.

### Объявление по датам

`showPostersHtml(string $hlBlockname = self::SUPPORT_POSTER_HIBLOCK_NAME): void` читает блок `SupportPoster` по умолчанию. Выбирает одну запись с максимальным ID, у которой текущий момент попадает в диапазон `UF_DATE_FROM` — `UF_DATE_TO`. Пустая граница допускается. При совпадении выводит `UF_MESSAGE` без экранирования внутри `div.global_alert_message`. Поле рассчитано на доверенный HTML.
