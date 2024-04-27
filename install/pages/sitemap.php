<?
// performance fixs
define("STOP_STATISTICS",       true);
define("NO_KEEP_STATISTIC",     true);
define("NO_AGENT_STATISTIC",    "Y");
define("NOT_CHECK_PERMISSIONS", true);
define("DisableEventsCheck",    true);
define("BX_SECURITY_SHOW_MESSAGE", true);
define('BX_SECURITY_SESSION_VIRTUAL', true);    // Виртуальная сессия
define('BX_SECURITY_SESSION_READONLY', true);   // Неблокирующие сессии
const DISABLE_PAGE_EVENTS = true;

require $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php";

use Bitrix\Main\Context;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\DateTime;

/**
 * Использование:
 * 1/
 * Генерируем карту сайта модулем СЕО, указываем карту сайта как sitemap_index.xml и не добавляем в robots.txt
 * 2/
 * добавляем правило в urlrewrite
 *     array (
'CONDITION' => '#^/sitemap.xml#',
'RULE' => '',
'ID' => NULL,
'PATH' => '/sitemap.php',
'SORT' => 100,
),
 * 3/
 * Получаем по урлу sitemap.xml склеенную карту сайта
 *
 * @version 1.0
 * @author hipot studio
 */
function MakeCombineSiteMap(array|string $sitemapOlds): string
{
	if (is_string($sitemapOlds)) {
		$sitemapOlds = [$sitemapOlds];
	}

	$finalDoc = new DOMDocument('1.0', 'UTF-8');
	$urlset = $finalDoc->appendChild($finalDoc->createElement('urlset'));
	$urlset->setAttribute("xmlns", "http://www.sitemaps.org/schemas/sitemap/0.9");
	foreach ($sitemapOlds as $sitemapOld) {
		$doc = new DOMDocument();
		$doc->load($sitemapOld);
		foreach ($doc->getElementsByTagName("loc") as $arItemLoc) {
			if (!empty($arItemLoc->nodeValue)) {
				$parentDoc = new DOMDocument();
				$parentDoc->load($arItemLoc->nodeValue);

				foreach ($parentDoc->getElementsByTagName("urlset") as $arItem) {
					foreach ($arItem->childNodes as $arNode) {
						$urlset->appendChild($finalDoc->importNode($arNode, true));
					}
				}
			}
		}
	}
	return (string)$finalDoc->saveHTML();
}
$sitemapFile = Loader::getDocumentRoot() . '/' . SITE_DIR . '/sitemap-index.xml';

$response = Context::getCurrent()->getResponse();
$response->addHeader('Content-type', 'text/xml');
$response->setLastModified(DateTime::createFromTimestamp(filemtime($sitemapFile)));
$response->setContent( MakeCombineSiteMap($sitemapFile) );
$response->send();
