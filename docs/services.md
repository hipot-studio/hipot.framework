## Сервисный слой для коммуникации с внешними системами

1/ Движок битрикса, как самый большой и тотальный монолитный сервис для фрейморка, класс
<code>\Hipot\Services\BitrixEngine</code>

```php
use Hipot\Services\BitrixEngine;

if (! BitrixEngine::getCurrentUser()->isAdmin()) {
    die('Need admin access');
}

BitrixEngine::getAppD0()->setPageProperty('title', 'Hello world');
BitrixEngine::getCurrentUserD0()->IsAdmin();
BitrixEngine::getInstance()->app->addBackgroundJob();
BitrixEngine::getInstance()->request->isAjaxRequest();
BitrixEngine::getInstance()->eventManager->addEventHandler();
BitrixEngine::getInstance()->connection->query('SELECT * FROM some_table'); // or
BitrixEngine::getInstance()->getConnection()->query('SELECT * FROM some_table');
BitrixEngine::getInstance()->session->set('key', 'value');
BitrixEngine::getInstance()->asset->addCss('/file.css');
BitrixEngine::getInstance()->cache->startDataCache(3600);
BitrixEngine::getInstance()->taggedCache->registerTag('tag');
BitrixEngine::getInstance()->serviceLocator->get('someService'); // or
BitrixEngine::getInstance()->getService('someService');
BitrixEngine::getInstance()->sessionLocalStorageManager->get('key');
```

2/ Итератор над результатами-выборками в битриксе
<code>\Hipot\Services\DbResultGenerator</code>

```php
use Hipot\BitrixUtils\Iblock;
use Hipot\Services\DbResultGenerator;
use Hipot\Types\ObjectArItem;

$rs = Iblock::selectElementsByFilter(
    ['ID' => 'ASC'], 
    ['IBLOCK_ID' => IBLOCK_ID_BLOG, 'PROPERTY_TYPE_EL' => false], 
    false, false,
    ['ID', 'IBLOCK_ID', 'PROPERTY_TYPE_EL']
);
foreach (new DbResultGenerator($rs, returnObjects: true) as $ar) {
    /** @var ObjectArItem $ar */
    CIBlockElement::SetPropertyValuesEx($ar['ID'], $ar['IBLOCK_ID'], ['TYPE_EL' => 209887]); // Articles
    d( $ar );
}

// old d0-CDBResult get all items like in new d7 way
$allList = (new DbResultGenerator($rs, returnObjects: true))->fetchAll();
```

3/ Класс для удобства взаимодействия с файловой системой
<code>\Hipot\Services\FileSystem</code>

```php
use Bitrix\Main\Application;
use Hipot\Services\FileSystem;
use SplFileInfo;

foreach (FileSystem::getRecursiveDirIterator(Application::getDocumentRoot() . $dirToCheck) as $file) {
    /* @var SplFileInfo $file */
    if ($file->isDir() || !in_array(mb_strtolower($file->getExtension()), ['jpg', 'jpeg', 'png', 'pdf', 'docx', 'pptx'])) {
        continue;
    }
}
```

4/ Класс для работы с memcached через интерфейс ArrayAccess
<code>\Hipot\Services\MemcacheWrapper</code>

```php
use Bitrix\Main\Application,
    Bitrix\Main\Data\MemcacheConnection,
    Hipot\Services\MemcacheWrapper,
    Hipot\Utils\UUtils;	
	
try {
    /** @var MemcacheConnection $mc */
    $mc = Application::getConnection('memcache');

    $registry = new MemcacheWrapper(
        'CACHE_ID',
        $mc->getResource()
    );
    
    // ...    
    $registry['key'] = 'value';
    d($registry['key']);
} catch (\Throwable $e) {
    UUtils::logException($e);
    d($e);
}
```

5/ Класс для переноса array-like глобальных справочников Bitrix во внешний кеш
<code>\Hipot\Services\GlobalsCacher</code>. Для каждого глобала передаются инициализатор
и фабрика его обёртки с интерфейсом <code>ArrayAccess</code>. Благодаря этому разные
глобалы могут использовать разные кеширующие движки.

Для вложенного глобального кеша `BX_IBLOCK_PROP_CACHE`, который используется
`CIBlockElement::SetPropertyValuesEx()`, применяется
`Hipot\Services\ApcuNestedArrayWrapper`, если APCu доступен. Между запросами сохраняется
готовая структура свойств каждого инфоблока; при отсутствии APCu используется
`MemcacheNestedArrayWrapper`.

Чтобы включить только этот кеш через APCu, до подключения фреймворка отключите
отдельный кеш `CIBlockProperty::GetPropertyArray()`:

```php
define('HIPOT_IBLOCK_CACHE_PROPERTY_ENABLED', false);
```

Сам `BX_IBLOCK_PROP_CACHE` можно отключить константой
`HIPOT_BX_IBLOCK_PROP_CACHE_ENABLED=false`. Оба кеша по умолчанию включены при
наличии подходящего движка.

Для статических array-like свойств классов используется отдельный помощник
<code>\Hipot\Services\StaticPropertiesCacher</code>. Обёртка
<code>\Hipot\Services\ManagedCacheArrayWrapper</code> хранит каждый ключ в Bitrix Managed Cache и корректно
отличает сохранённое значение <code>false</code> от отсутствующего ключа.

6/ Класс для хранения различных значений (реестр значений), чтобы не использовать $GLOBALS
<code>\Hipot\Services\Registry</code>

```php
use Hipot\Services\Registry;

Registry::set('key', 'value');
// ...
$value = Registry::get('key');
```

7/ Сервис для чтения xml-файлов через php_xmlreader
<code>\Hipot\Services\SimpleXMLReader</code>

8/ Сервис создания простого excel-файла <code>\Hipot\Services\SimpleXlsx</code> на основе PhpSpreadsheet

9/ Сервис для работы с сервисом Google Recaptcha3 и его внедрением в битрикс
<code>\Hipot\Services\Recaptcha3</code>

10/ Класс-обертка над запуском wkhtmltopdf для создания pdf из страницы:
<code>\Hipot\Services\PdfPageGenerator</code>

11/ Класс-обертка над пакетом ffmpeg для работы с видео
<code>\Hipot\Services\FfmpegExec</code>

12/ Класс для работы с календарем и рабочими (банковскими) днями
<code>\Hipot\Services\BankDayCalc</code>. Календарь принимает полные даты в формате
`Y-m-d`; явно заданный рабочий день имеет приоритет над выходным и праздником.

```php
use Hipot\Services\BankDayCalc;
use Hipot\Services\IblockWorkCalendarProvider;

$provider = new IblockWorkCalendarProvider(
	iblockId: 17,
	datePropertyCode: 'DATE',
	typePropertyCode: 'TYPE',
	holidayType: 'выходной',
	workdayType: 'рабочий',
	sourceDateFormat: 'd.m.Y',
);
$calendar = BankDayCalc::fromProvider($provider);

$deliveryDate = $calendar->getEndDate(new DateTimeImmutable('2026-09-11'), 2);
$isWorkingSaturday = $calendar->isWorkDay(new DateTimeImmutable('2026-09-19'));
```

`IblockWorkCalendarProvider` читает только активные элементы и один раз загружает
данные на экземпляр. Значения свойств типа сопоставляются без учёта регистра и
могут начинаться с переданной строки, например `выходной день`.

13/ Сервис для работы с AI <code>\Hipot\Services\OpenAI</code>
