<?php
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Loader;
use Bitrix\Main\EventManager;
use Bitrix\Main\Event;
use Bitrix\Main\Application;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Composite\Engine as CompositeEngine;
use Hipot\BitrixUtils\AssetsContainer;
use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;
use Bitrix\Main\ORM\Data\DataManager;

/**
 * @var $eventManager EventManager
 * @var $request \Bitrix\Main\HttpRequest
 */

// region some SEO fixes in public pages and js-appConfig array

// отрисовка 404 страницы с прерыванием текущего буфера и замены его на содержимое 404
$eventManager->addEventHandler('main', 'OnEpilog', static function () use ($request, $eventManager) {
	if ($request->isAdminSection() || $request->isAjaxRequest()) {
		return;
	}
	global $APPLICATION;
	static $isRun = false;
	
	if (IS_BETA_TESTER) {
		$isRun = true;
	}
	
	// process 404 in content part
	if ((!$isRun && defined('ERROR_404') && ERROR_404 === 'Y' && $APPLICATION->GetCurPage() != '/404.php')) {
		$isRun = true;
		\CHTTP::setStatus('404 Not Found');
		
		CompositePage::getInstance()?->markNonCacheable();
		CompositeEngine::setEnable(false);
		
		// region re-get one time 404 page
		$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$content) use ($request) {
			$contCacheFile = Loader::getDocumentRoot() . sprintf('/upload/404_%s_cache.html', Application::getInstance()?->getContext()?->getSite());
			if (is_file($contCacheFile) && ((time() - filemtime($contCacheFile)) > 3600 * 24 * 7)) {
				unlink($contCacheFile);
			}
			$content = is_file($contCacheFile) ? file_get_contents($contCacheFile) : '';
			if (trim($content) === '') {
				$el      = new HttpClient();
				$content = $el->get(($request->isHttps() ? 'https://' : 'http://') . $request->getServer()->getHttpHost() . '/404.php?last_page=' . urlencode($request->getRequestUri()));
				
				file_put_contents($contCacheFile, $content, LOCK_EX);
			}
		});
		// endregion
	}
});

// lazy loaded css, TOFUTURE: js and js-appConfig array
$eventManager->addEventHandler('main', 'OnEpilog', [AssetsContainer::class, 'onEpilogSendAssets']);

// delete system scripts (Use only when not needed composite dynamic blocks)
$eventManager->addEventHandler('main', 'OnEndBufferContent', static function (&$cont) use ($request) {
	if ($request === null || $request->isAdminSection()) {
		return;
	}
	global $APPLICATION, $USER;
	
	if (is_object($USER) && !$USER->IsAuthorized() && !$request->isPost() && $APPLICATION->GetProperty('JS_core_frame_cache_NEED') != 'Y') {
		$toRemove = [
			'#<script[^>]+src="/bitrix/js/ui/dexie/[^>]+></script>#',
			'#<script[^>]+src="/bitrix/js/main/core/core_frame_cache[^>]+></script>#',
			'#<link[^>]+href="/bitrix/js/ui/design-tokens/dist/ui.design-tokens.min.css\?\d+"[^>]+>#',
			'#<link[^>]+href="/bitrix/panel/main/popup.min.css\?\d+"[^>]+>#',
		];
		$cont = preg_replace($toRemove, "", $cont);
	}
	
	$toInline = [
		'#<script[^>]+src="(?<src>/bitrix/js/main/core/core.min.js)\?\d+"[^>]*></script>#',
		'#<script[^>]+src="(?<src>/bitrix/js/main/core/core_ls.min.js)\?\d+"[^>]*></script>#',
		'#<script[^>]+src="(?<src>/bitrix/cache/js/.*?\.js)\?\d+"[^>]*></script>#'
	];
	$cont = preg_replace_callback($toInline, static function ($matches) {
		$content = file_get_contents(Loader::getDocumentRoot() . $matches['src']);
		if (preg_match('#</head>#', $content)) {
			return $matches[0];
		}
		$scriptHeader = '/////////////////////////////////////' . PHP_EOL
			. '// Script: ' . $matches['src'] . PHP_EOL
			. '/////////////////////////////////////' . PHP_EOL;
		return '<script>' . PHP_EOL
			. (IS_BETA_TESTER ? $scriptHeader : '')
			. $content . PHP_EOL
			. '</script>';
	}, $cont);
	
	// validator.w3.org: The type attribute is unnecessary for JavaScript resources.
	$cont = preg_replace(['#<script([^>]*)type=[\'"]text/javascript[\'"]([^>]*)>#', '#<br\s*/>#i'], ['<script\\1\\2>', '<br>'], $cont);
});

// endregion some SEO fixes