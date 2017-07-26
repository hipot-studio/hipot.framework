<?

/** @see http://www.intervolga.ru/blog/projects/d7-analogi-lyubimykh-funktsiy-v-1s-bitriks/ */

// Old school
$APPLICATION->AddHeadScript(SITE_TEMPLATE_PATH . "/js/fix.js");
$APPLICATION->SetAdditionalCSS(SITE_TEMPLATE_PATH . "/styles/fix.css");
$APPLICATION->AddHeadString("<link href='http://fonts.googleapis.com/css?family=PT+Sans:400&subset=cyrillic' rel='stylesheet' type='text/css'>");

// D7
use Bitrix\Main\Page\Asset;

Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/fix.js");
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/styles/fix.css");
Asset::getInstance()->addString("<link href='http://fonts.googleapis.com/css?family=PT+Sans:400&subset=cyrillic' rel='stylesheet' type='text/css'>");

////////////////////////////////////////////////////////////

// Old school
CModule::IncludeModule("iblock");
CModule::IncludeModuleEx("intervolga.tips");

// D7
use Bitrix\Main\Loader;

Loader::includeModule("iblock");
Loader::includeSharewareModule("intervolga.tips");


////////////////////////////////////////////////////////////


// Old school
IncludeTemplateLangFile(__FILE__);
echo GetMessage("INTERVOLGA_TIPS.TITLE");

// D7
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
echo Loc::getMessage("INTERVOLGA_TIPS.TITLE");

////////////////////////////////////////////////////////////

// Old school
COption::SetOptionString("main", "max_file_size", "1024");
$size = COption::GetOptionInt("main", "max_file_size");
COption::RemoveOption("main", "max_file_size", "s2");

// D7
use Bitrix\Main\Config\Option;

Option::set("main", "max_file_size", "1024");
$size = Option::get("main", "max_file_size");
Option::delete("main", array(
		"name" => "max_file_size",
		"site_id" => "s2"
	)
);

/////////////////////////////////////////////////////////////

// Old school
$cache = new CPHPCache();
if ($cache->InitCache($cacheTime, $cacheId, $cacheDir)) {
	$result = $cache->GetVars();
} elseif ($cache->StartDataCache()) {
	$result = array();
	// ...
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
use Bitrix\Main\EventManager;

$handler = EventManager::getInstance()->addEventHandler(
	"main",
	"OnUserLoginExternal",
	array(
		"Intervolga\\Test\\EventHandlers\\Main",
		"onUserLoginExternal"
	)
);
Bitrix\Main\EventManager::addEventHandlerCompatible();

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
use Bitrix\Main\Application;
use Bitrix\Main\IO\Directory;
use Bitrix\Main\IO\File;

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
global $APPLICATION;
$APPLICATION->ResetException();
$APPLICATION->ThrowException("Error");
//...
if ($exception = $APPLICATION->GetException()) {
	echo $exception->GetString();
}

// D7
use Bitrix\Main\SystemException;

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
use Bitrix\Main\Mail\Event;
Event::send(array(
	"EVENT_NAME" => "NEW_USER",
	"LID" => "s1",
	"C_FIELDS" => array(
		"EMAIL" => "info@intervolga.ru",
		"USER_ID" => 42
	),
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

use Bitrix\Main\Web\HttpClient;

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
use Bitrix\Main\Web\Cookie;
$cookie = new Cookie("TEST", 42);
$cookie->setDomain("example.com");
Application::getInstance()->getContext()->getResponse()->addCookie($cookie);
// Cookie будет доступна только на следующем хите!
echo Application::getInstance()->getContext()->getRequest()->getCookie("TEST");

////////////////////////////////////////////////////////////////////////////////////

// Old school
global $APPLICATION;
$redirect = $APPLICATION->GetCurPageParam("foo=bar", array("baz"));

// D7
use Bitrix\Main\Web\Uri;

$request = Application::getInstance()->getContext()->getRequest();
$uriString = $request->getRequestUri();
$uri = new Uri($uriString);
$uri->deleteParams(array("baz"));
$uri->addParams(array("foo" => "bar"));
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

?>