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

5/ Класс для хранения различных значений (реестр значений), чтобы не использовать $GLOBALS 
<code>\Hipot\Services\Registry</code>

```php
use Hipot\Services\Registry;

Registry::set('key', 'value');
// ...
$value = Registry::get('key');
```

6/ Сервис для чтения xml-файлов через php_xmlreader
<code>\Hipot\Services\SimpleXMLReader</code>

7/ Сервис создания простого excel-файла <code>\Hipot\Services\SimpleXlsx</code> на основе PhpSpreadsheet

8/ Сервис для работы с сервисом Google Recaptcha3 и его внедрением в битрикс
<code>\Hipot\Services\Recaptcha3</code>

9/ Класс-обертка над запуском wkhtmltopdf для создания pdf из страницы:
<code>\Hipot\Services\PdfPageGenerator</code>

10/ Класс-обертка над пакетом ffmpeg для работы с видео
<code>\Hipot\Services\FfmpegExec</code>

11/ Класс для работы с календарем и рабочими (банковскими) днями
<code>\Hipot\Services\BankDayCalc</code>

12/ Сервис для работы с AI <code>\Hipot\Services\OpenAI</code>