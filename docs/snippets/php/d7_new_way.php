<?php

/** @see http://www.intervolga.ru/blog/projects/d7-analogi-lyubimykh-funktsiy-v-1s-bitriks/ */
/** @see https://github.com/SidiGi/bitrix-info/wiki */

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */
/** @var $GLOBALS array{'DB' : \CDatabase, 'APPLICATION' : \CMain, 'USER' : \CUser, 'USER_FIELD_MANAGER' : \CUserTypeManager, 'CACHE_MANAGER' : \CCacheManager, 'stackCacheManager' : \CStackCacheManager} */

// ALL uses
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\EventManager;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Service\GeoIp;
use Bitrix\Sale\Location\GeoIp as SaleGeoIp;
use Bitrix\Main\Mail\Event as CMailEvent;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\SystemException;

// Old school
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH . "/js/fix.js");
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . "/styles/fix.css");
$APPLICATION->AddHeadString("<link href='http://fonts.googleapis.com/css?family=PT+Sans:400&subset=cyrillic' rel='stylesheet' type='text/css'>");

// D7
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/fix.js");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/styles/fix.css");
Asset::getInstance()->addString("<link href='http://fonts.googleapis.com/css?family=PT+Sans:400&subset=cyrillic' rel='stylesheet' type='text/css'>");

////////////////////////////////////////////////////////////

//D0:
$USER->IsAdmin();

//D7:
\Bitrix\Main\Engine\CurrentUser::get()->isAdmin();

// Old school
CModule::IncludeModule("iblock");
CModule::IncludeModuleEx("intervolga.tips");

// D7
Loader::includeModule("iblock");
Loader::includeSharewareModule("intervolga.tips");


////////////////////////////////////////////////////////////


// Old school
IncludeTemplateLangFile(__FILE__);
echo GetMessage("INTERVOLGA_TIPS.TITLE");

// D7
Loc::loadMessages(__FILE__);
echo Loc::getMessage("INTERVOLGA_TIPS.TITLE");

////////////////////////////////////////////////////////////

// Old school
COption::SetOptionString("main", "max_file_size", "1024");
$size = COption::GetOptionInt("main", "max_file_size");
COption::RemoveOption("main", "max_file_size", "s2");

// D7
Option::set("main", "max_file_size", "1024");
$size = Option::get("main", "max_file_size");
Option::delete("main", array(
		"name" => "max_file_size",
		"site_id" => "s2"
	)
);

/////////////////////////////////////////////////////////////

// Old school
$cacheTime      = 3600;
$cacheId        = md5();
$cacheDir       = "php/some_stuff";

$cache = new CPHPCache();
if ($cache->InitCache($cacheTime, $cacheId, $cacheDir)) {
	$result = $cache->GetVars();
} elseif ($cache->StartDataCache()) {
	$result = array();
	// ...
	$isInvalid = false;
	if ($isInvalid) {
		$cache->AbortDataCache();
	}
	// ...
	$cache->EndDataCache($result);
}

// D7
$cache = Bitrix\Main\Data\Cache::createInstance();
if ($cache->initCache($cacheTime, $cacheId, $cacheDir)) {
	$result = $cache->getVars();
} elseif ($cache->startDataCache()) {
	$result = array();
	// ...
	if ($isInvalid) {
		$cache->abortDataCache();
	}
	// ...
	$cache->endDataCache($result);
}


///////////////////////////////////////////////////////////////////////////////////

// Old school
$handler = AddEventHandler("main",
	"OnUserLoginExternal",
	array(
		"Intervolga\\Test\\EventHandlers\\Main",
		"onUserLoginExternal"
	)
);
RemoveEventHandler(
	"main",
	"OnUserLoginExternal",
	$handler
);
RegisterModuleDependences(
	"main",
	"OnProlog",
	$this->MODULE_ID,
	"Intervolga\\Test\\EventHandlers",
	"onProlog"
);
UnRegisterModuleDependences(
	"main",
	"OnProlog",
	$this->MODULE_ID,
	"Intervolga\\Test\\EventHandlers",
	"onProlog"
);

$handlers = GetModuleEvents("main", "OnProlog", true);

// D7
$handler = EventManager::getInstance()->addEventHandler(
	"main",
	"OnUserLoginExternal",
	array(
		"Intervolga\\Test\\EventHandlers\\Main",
		"onUserLoginExternal"
	)
);
EventManager::addEventHandlerCompatible();

EventManager::getInstance()->removeEventHandler(
	"main",
	"OnUserLoginExternal",
	$handler
);
EventManager::getInstance()->registerEventHandler(
	"main",
	"OnProlog",
	$this->MODULE_ID,
	"Intervolga\\Test\\EventHandlers",
	"onProlog"
);
EventManager::getInstance()->unRegisterEventHandler(
	"main",
	"OnProlog",
	$this->MODULE_ID,
	"Intervolga\\Test\\EventHandlers",
	"onProlog"
);
$handlers = EventManager::getInstance()->findEventHandlers("main", "OnProlog");



////////////////////////////////////////////////////////////////////

// Old school
CheckDirPath($_SERVER["DOCUMENT_ROOT"] . "/foo/bar/baz/");
RewriteFile(
	$_SERVER["DOCUMENT_ROOT"] . "/foo/bar/baz/1.txt",
	"hello from old school!"
);
DeleteDirFilesEx("/foo/bar/baz/");

// D7
Directory::createDirectory(
	Application::getDocumentRoot() . "/foo/bar/baz/"
);
File::putFileContents(
	Application::getDocumentRoot() . "/foo/bar/baz/1.txt",
	"hello from D7"
);
Directory::deleteDirectory(
	Application::getDocumentRoot() . "/foo/bar/baz/"
);

$_SERVER["DOCUMENT_ROOT"] = Bitrix\Main\Application::getDocumentRoot();

///////////////////////////////////////////////////////////////////////////////


// Old school
$APPLICATION->ResetException();
$APPLICATION->ThrowException("Error");
//...
if ($exception = $APPLICATION->GetException()) {
	echo $exception->GetString();
}

// D7
try {
	// ...
	throw new SystemException("Error");
} catch (SystemException $exception) {
	echo $exception->getMessage();
}

///////////////////////////////////////////////////////////////////////////////


// Old school
define("LOG_FILENAME", $_SERVER["DOCUMENT_ROOT"]."/bitrix/log-intervolga.txt");

AddMessage2Log($_SERVER);
echo "<pre>" . mydump($_SERVER) . "</pre>";

// D7
use Bitrix\Main\Diag\Debug;

Debug::dumpToFile($_SERVER);
// or
Debug::writeToFile($_SERVER);

Debug::dump($_SERVER);

// D7
Debug::startTimeLabel("foo");
foo();
Debug::endTimeLabel("foo");

Debug::startTimeLabel("bar");
bar();
Debug::endTimeLabel("bar");

print_r(Debug::getTimeLabels());

/////////////////////////////////////////////////////////////////////////////////

// Old school
CEvent::Send(
	"NEW_USER",
	"s1",
	array(
		"EMAIL" => "info@intervolga.ru",
		"USER_ID" => 42
	)
);

// D7
CMailEvent::send(array(
	"EVENT_NAME" => "NEW_USER",
	"LID" => "s1",
	"C_FIELDS" => [
		"EMAIL" => "info@intervolga.ru",
		"USER_ID" => 42
	],
));

//////////////////////////////////////////////////////////////////////////////////

// Old school
$name = $_POST["name"];
$email = htmlspecialchars($_GET["email"]);

// D7
$request = Application::getInstance()->getContext()->getRequest();

$name = $request->getPost("name");
$email = htmlspecialchars($request->getQuery("email"));

//////////////////////////////////////////////////////////////////////////////////
$httpClient = new HttpClient();
$httpClient->setHeader('Content-Type', 'application/json', true);
$response = $httpClient->post('http://www.example.com', json_encode(array('x' => 1)));

$httpClient = new HttpClient();
$httpClient->download('http://www.example.com/robots.txt', $_SERVER['DOCUMENT_ROOT'].'/upload/my.txt');

// Old school
global $APPLICATION;
$APPLICATION->set_cookie("TEST", 42, false, "/", "example.com");
// Cookie будет доступна только на следующем хите!
echo $APPLICATION->get_cookie("TEST");

// D7
$cookie = new Cookie("TEST", 42);
$cookie->setDomain("example.com");
Application::getInstance()->getContext()->getResponse()->addCookie($cookie);
// Cookie будет доступна только на следующем хите!
echo Application::getInstance()->getContext()->getRequest()->getCookie("TEST");

////////////////////////////////////////////////////////////////////////////////////

// Old school
global $APPLICATION;
$redirect = $APPLICATION->GetCurPageParam("foo=bar", ["baz"]);

// D7
$request = Application::getInstance()->getContext()->getRequest();
$uriString = $request->getRequestUri();
$uri = new Uri($uriString);
$uri->deleteParams(["baz"]);
$uri->addParams(["foo" => "bar"]);
$redirect = $uri->getUri();

///////////////////////////////////////////////////////////////////////////////////

// Old school
global $DB;
$record = $DB->Query("select 1+1;")->Fetch();
AddMessage2Log($record);

// D7
$record = Application::getConnection()
	->query("select 1+1;")
	->fetch();
Debug::writeToFile($record);


// Используйте \Bitrix\Iblock\PropertyTable::getList. Расширять CIBlockProperty::GetList не планируется.
\Bitrix\Iblock\PropertyTable::getList();


///////////////////////////// sale /////////////////////////////////////////////////

EventManager::getInstance()->addEventHandler(
	'sale',
	'OnSaleOrderBeforeSaved',
	'saleOrderBeforeSaved'
);
/**
 * @param \Bitrix\Main\Event $event
 *
 * @throws \Bitrix\Main\ArgumentException
 * @throws \Bitrix\Main\ArgumentOutOfRangeException
 * @throws \Bitrix\Main\NotImplementedException
 * @throws \Bitrix\Main\ObjectPropertyException
 * @throws \Bitrix\Main\SystemException
 */
function saleOrderBeforeSaved(Event $event)
{
	/** @var \Bitrix\Sale\Order $order */
	$order = $event->getParameter("ENTITY");

	/** @var \Bitrix\Sale\PropertyValueCollection $propertyCollection */
	$propertyCollection = $order->getPropertyCollection();

	$propsData = [];

	/**
	 * Собираем все свойства и их значения в массив
	 * @var \Bitrix\Sale\PropertyValue $propertyItem
	 */
	foreach ($propertyCollection as $propertyItem) {
		if (!empty($propertyItem->getField("CODE"))) {
			$propsData[$propertyItem->getField("CODE")] = trim($propertyItem->getValue());
		}
	}

	/**
	 * Перебираем свойства и изменяем нужные значения
	 * @var \Bitrix\Sale\PropertyValue $propertyItem
	 */
	foreach ($propertyCollection as $propertyItem) {
		switch ($propertyItem->getField("CODE")) {
			// Прописываем ФИО в одно поле
			case 'FIO_HIDDEN':
				$val = $propsData['SURNAME'] . ' ' . $propsData['NAME'] . ' ' . $propsData['PATRONYMIC'];
				$propertyItem->setField("VALUE", $val);
				break;
		}
	}
}


//////////////////////////////////////////////////////
$cache = Application::getInstance()->getManagedCache();
$cache->clean("b_module_to_module");


////// Определение геолокации пользователя
/// sale 17.0.13+ Настройки -> Настройки продукта -> Геолокация
// own
$eventManager = EventManager::getInstance();
$eventManager->addEventHandler(
	'main',
	'onMainGeoIpHandlersBuildList',
	'GeoIpHandler'
);
function GeoIpHandler()
{
	return new EventResult(
		EventResult::SUCCESS,
		array(
			'YourClass' => '/path/to/your/class.php',
		)
	);
}

// geocoding example
$ipAddress = GeoIp\Manager::getRealIp();
$result = GeoIp\Manager::getDataResult($ipAddress, "ru")->getGeoData();
$country = $result->countryCode;

// relay to sale locations
SaleGeoIp::getLocationId($ipAddress, $lang);
SaleGeoIp::getLocationCode($ipAddress, $lang);
SaleGeoIp::getZipCode($ipAddress, $lang);

?>


<?php
foreach (EventManager::getInstance()->findEventHandlers('main', 'OnEndBufferContent') as $i => $evt) {
	EventManager::getInstance()->removeEventHandler('main', 'OnEndBufferContent', $i);
}
?>

<?php
// work with session
\Bitrix\Main\Application::getInstance()->getSession()["CACHE_STAT"]["scanned"]++;
?>

<?
// очистка кеша компонента
$GLOBALS["CACHE_MANAGER"]->CleanDir("menu");
CBitrixComponent::clearComponentCache("bitrix:menu");
?>

<?
$page = \Bitrix\Main\Composite\Page::getInstance();
$page->deleteAll();
?>

<?
\Bitrix\Main\Text\HtmlFilter::encode();
htmlspecialcharsbx();
?>

<?
$connection = \Bitrix\Main\Application::getConnection();
// останавливаем выполнение запросов
$connection->disableQueryExecuting();

// выполняем свой код, который как бы генерирует и исполняет запросы, но БД их не получит, а результат запроса будет пустым
Bitrix\Iblock\ElementTable::getList( /*...*/ );
$connection->createTable( /*...*/ );
// и т.п.

// включаем выполнение запросов
$connection->enableQueryExecuting();

// и получаем дамп (массив) накопившихся sql запросов
$queries = $connection->getDisabledQueryExecutingDump();

// трекинг выполненных запросов пост-фактум

\Bitrix\Main\Application::getConnection()->startTracker(false);
$rsData = Bitrix\Iblock\ElementTable::getList( /*...*/ );
\Bitrix\Main\Application::getConnection()->stopTracker();
$sql = $rsData->getTrackerQuery()->getSql();
// or
print_r( \Bitrix\Main\Application::getConnection()->getTracker() );
?>

<?
// 1 флаг
global $DB;
$DB->ShowSqlStat = true;

// 2 выполнение запросов

// 3 Отображаем статистику:
$shortInfo = array_reduce(
	$DB->arQueryDebug,
	static function ($carry, \Bitrix\Main\Diag\SqlTrackerQuery $info) {
		$carry[] = $info->getTime() . ' : ' . $info->getSql();
		return $carry;
	}, []
);
?>