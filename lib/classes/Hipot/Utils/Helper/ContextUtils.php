<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:04
 * @version pre 1.0
 */
namespace Hipot\Utils\Helper;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\Web\Uri;
use Hipot\Services\BitrixEngine;
use Bitrix\Main\Composite\Page as CompositePage;
use Bitrix\Main\Service\GeoIp;

trait ContextUtils
{
	/**
	 * Получить путь к php с учетом особенностей utf8 битрикс
	 * @return string
	 */
	public static function getPhpPath(): string
	{
		$phpPath = 'php';
		if (! defined('BX_UTF')) {
			$phpPath .= ' -d default_charset="cp1251" ';
		} else {
			$phpPath .= ' -d default_charset="utf-8" ';
		}
		return $phpPath;
	}
	
	/**
	 * стартует по классике сессию
	 */
	public static function sessionStart(): void
	{
		//session initialization
		if (session_status() !== PHP_SESSION_ACTIVE) {
			ini_set("session.cookie_httponly", "1");
			ini_set("session.use_strict_mode", "On");
			
			session_start();
		}
	}
	
	/**
	 * Возвращаем ip-адрес посетителя сайта
	 *
	 * @param \Hipot\Services\BitrixEngine $bitrixEngine
	 * @return ?string
	 */
	public static function getUserIp(BitrixEngine $bitrixEngine): ?string
	{
		$serverKeys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'REMOTE_ADDR'
		];
		foreach ($serverKeys as $key) {
			$serverValue = $bitrixEngine->request->getServer()->get($key);
			if (!empty($serverValue)) {
				$arServerValue = explode(',', $serverValue);
				$ipAddress     = trim(end($arServerValue));
				if (\filter_var($ipAddress, FILTER_VALIDATE_IP)) {
					return $ipAddress;
				}
			}
		}
		return null;
	}
	
	/**
	 * Retrieves geographical data for a given IP address.
	 *
	 * @param string $ip The IP address
	 * @return GeoIp\Data|null The geographical data associated with the IP or null if unavailable
	 */
	public static function getGeoIpDataByIp(string $ip): ?GeoIp\Data
	{
		try {
			return GeoIp\Manager::getDataResult($ip, LANGUAGE_ID)?->getGeoData();
		} catch (\Throwable $exception) {
			self::logException($exception);
			return null;
		}
	}
	
	/**
	 * Retrieves the country code associated with the provided IP address.
	 * @param string $ip The IP address
	 */
	public static function getCountryByIp(string $ip): string
	{
		$data = self::getGeoIpDataByIp($ip);
		return (string)$data?->countryCode;
	}
	
	/**
	 * Является ли текущая страница в данный момент страницей постраничной навигации
	 * @return bool
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function isPageNavigation(): bool
	{
		$request = Application::getInstance()->getContext()->getRequest();
		foreach ([1, 2, 3] as $pnCheck) {
			$req_name = 'PAGEN_' . $pnCheck;
			if ((int)$request->getPost($req_name) > 1 || (int)$request->getQuery($req_name) > 1) {
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Получить содержимое по урлу
	 * @param $url
	 * @return bool|false|string
	 */
	public static function getPageContentByUrl($url): string
	{
		$el = new HttpClient();
		return (string)$el->get( $url );
	}
	
	public static function getHttpHeadersByUrl($url): array
	{
		$el = new HttpClient([
			'redirect'                  => false,
			'disableSslVerification'    => true
		]);
		$headers = $el->head( $url )->toArray();
		$headers['status'] = $el->getStatus();
		return $headers;
	}
	
	
	/**
	 * write exception to log
	 * @param \Exception|\Bitrix\Main\SystemException $exception
	 * @return void
	 */
	public static function logException(\Throwable $exception): void
	{
		$application = Application::getInstance();
		$exceptionHandler = $application->getExceptionHandler();
		$exceptionHandler->writeToLog($exception);
	}
	
	public static function getInlineBase64Image(string $img_file): ?string
	{
		if (! file_exists($img_file)) {
			return null;
		}
		
		return 'data:'.mime_content_type($img_file).';base64,'.base64_encode(file_get_contents($img_file));
	}
	
	/**
	 * Останавливаем выполнение SQL- запросов
	 * @return void
	 * @see getQueueStoppedQueryExecution()
	 */
	public static function stopSqlQueryExecution(): void
	{
		\Bitrix\Main\Application::getConnection()->disableQueryExecuting();
	}
	
	/**
	 * Включаем выполнение запросов и получаем дамп накопившихся sql запросов
	 * @return array|null
	 * @see stopSqlQueryExecution()
	 */
	public static function getQueueStoppedQueryExecution(): ?array
	{
		$connection = \Bitrix\Main\Application::getConnection();
		$connection->enableQueryExecuting();
		return $connection->getDisabledQueryExecutingDump();
	}
	
	/**
	 * Use this method to set 404 status + ERROR_404 constant in work-area section of page.
	 * Use with combination of OnEpilog+OnEndBufferContent-handler
	 * @param bool $showPage = false Immediately echo content of 404 page, or try later work with OnEpilog+OnEndBufferContent-handler
	 * @return void
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function setStatusNotFound(bool $showPage = false): void
	{
		if (Loader::includeModule('iblock')) {
			\Bitrix\Iblock\Component\Tools::process404("", true, true, $showPage);
		} else if (is_file(Loader::getDocumentRoot() . SITE_DIR . "/404_inc.php")) {
			ob_start();
			include Loader::getDocumentRoot() . SITE_DIR . "/404_inc.php";
			$content = ob_get_clean();
			if ($showPage) {
				echo $content;
			}
		} else {
			\CHTTP::setStatus('404 Not Found');
			if (!defined('ERROR_404')) {
				define('ERROR_404', 'Y');
			}
		}
	}
	
	/**
	 * Проверяет, находится ли текущая директория в списке директорий для текущего сайта.
	 * @param array $dirs Массив директорий для проверки
	 * @return bool Возвращает true, если текущая директория находится в списке директорий, иначе возвращает false.
	 * @see \CSite::InDir()
	 */
	public static function siteIdDirs(array $dirs): bool
	{
		foreach ($dirs as $dir) {
			if (\CSite::InDir($dir)) {
				return true;
			}
		}
		return false;
	}
	
	/** @noinspection GlobalVariableUsageInspection */
	/**
	 * Set the URI to the current Bitrix Engine page to work menu property
	 *
	 * @param string $uri The URI to be used.
	 *
	 * @return void
	 * @see \Bitrix\Main\Web\Uri
	 */
	public static function setUriToBitrixCurPage(string $uri): void
	{
		$oUri  = new Uri($uri);
		// to work with GetCurPageParam()
		parse_str($oUri->getQuery(), $_GET);
		// to work with menu selected check
		foreach ($_GET as $name => $value) {
			$GLOBALS[$name] = $value;
		}
		BitrixEngine::getAppD0()->SetCurPage(rtrim($oUri->getPath() . '?' . $oUri->getQuery(), '?'));
	}
	
	/**
	 * Возвращает текущую страницу с добавленными параметрами, при этом удаляет некоторые параметры из URL
	 *
	 * @param string $addParams Дополнительные параметры
	 * @param array  $arParamKill Параметры, которые необходимо удалить из URL
	 * @param bool   $get_index_page Флаг, указывающий на необходимость получения индексной страницы
	 *
	 * @return string Текущая страница с добавленными параметрами
	 * @see \Bitrix\Main\Application::getConnection()
	 * @see \Bitrix\Main\HttpRequest::getSystemParameters()
	 * @see \CMain::GetCurPageParam()
	 */
	public static function getBitrixCurPageParam(string $addParams = "", array $arParamKill = [], bool $get_index_page = false): string
	{
		$delParam = array_merge(
			[
				"PAGEN_1", "SIZEN_1", "SHOWALL_1",
				"PAGEN_2", "SIZEN_2", "SHOWALL_2",
				"PAGEN_3", "SIZEN_3", "SHOWALL_3",
				"PHPSESSID", "PageSpeed",
				"testajax", "is_ajax_post", "via_ajax"
			],
			\Bitrix\Main\HttpRequest::getSystemParameters(),
			$arParamKill
		);
		return BitrixEngine::getAppD0()->GetCurPageParam($addParams, $delParam, $get_index_page);
	}
	
	/**
	 * Disable all page process events (ex performance on ajax scripts)
	 *
	 * This function disables specific events in the Bitrix Engine event manager
	 * (page handlers flow: OnPageStart -> OnBeforeProlog -> OnProlog -> OnEpilog -> OnAfterEpilog -> OnEndBufferContent)
	 * use in init.php
	 *
	 * @param BitrixEngine $bitrixEngine The Bitrix engine instance.
	 * @return void
	 */
	public static function disableAllPageProcessEvents(BitrixEngine $bitrixEngine): void
	{
		$bitrixEngine->eventManager->addEventHandler('main', 'OnPageStart', static function() use ($bitrixEngine) {
			$eventsToTurnOff = ['OnPageStart', 'OnBeforeProlog', 'OnProlog', 'OnEpilog', 'OnAfterEpilog', 'OnEndBufferContent'];
			foreach ($eventsToTurnOff as $eventType) {
				$arrResult = $bitrixEngine->eventManager->findEventHandlers('main', $eventType);
				foreach ($arrResult as $k => $event) {
					$bitrixEngine->eventManager->removeEventHandler('main', $eventType, $k);
				}
			}
		}, false, 1);
	}
	
	/**
	 * Determines if the current request is an AJAX request.
	 *
	 * @param BitrixEngine $bitrixEngine The instance of the BitrixEngine class.
	 *
	 * @return bool Returns true if the current request is an AJAX request, false otherwise.
	 */
	public static function isAjaxRequest(BitrixEngine $bitrixEngine): bool
	{
		$request = $bitrixEngine->request;
		$server = $bitrixEngine->request->getServer();
		
		if (
			($request["testajax"] === 'Y') || ($request["is_ajax_post"] === 'Y') || ($request['via_ajax'] === 'Y') ||
			$request->isAjaxRequest() ||
			($server->get('HTTP_X_REQUESTED_WITH') === 'XMLHttpRequest') || ($server->get('HTTP_BX_AJAX') !== null)
		) {
			$bIsAjax = true;
		} else {
			$bIsAjax = false;
		}
		return $bIsAjax;
	}
	
	/**
	 * Clears the composite page cache for a specified URI.
	 *
	 * @param string $uri The URI of the composite page to be cleared.
	 *
	 * @return void
	 */
	public static function clearCompositePage(string $uri): void
	{
		$page = new CompositePage($uri);
		
		/** @var \Bitrix\Main\Composite\Data\AbstractStorage $storage */
		if ($storage = $page->getStorage()) {
			$storage->delete();
		}
	}
	
	/**
	 * Captures the output of a callable.
	 *
	 * @param callable $callback The function whose output needs to be captured.
	 * @return string The output captured from the callable.
	 */
	private static function captureOutput(callable $callback): string
	{
		ob_start();
		$callback();
		return ob_get_clean();
	}
	
	/**
	 * Отключает вход в Битрикс по HTTP авторизации
	 * @see CUser::LoginByHttpAuth()
	 */
	public static function disableHttpAuth(): void
	{
		BitrixEngine::getInstance()->eventManager->addEventHandlerCompatible(
			'main',
			'onBeforeUserLoginByHttpAuth',
			static function (&$auth) {
				return false;
			}
		);
	}
	
	/**
	 * Modifies the current page's URI by adding and deleting specified parameters.
	 *
	 * @param array $arAddParams An array of parameters to add to the URI.
	 * @param array $arDeleteParams An array of parameters to remove from the URI.
	 *
	 * @return string The modified URI as a string.
	 */
	public static function getCurPageParamD7(array $arAddParams = [], array $arDeleteParams = []): string
	{
		$request = BitrixEngine::getInstance()->request;
		$uriString = $request->getRequestUri();
		$uri = new Uri($uriString);
		$uri->deleteParams($arDeleteParams);
		$uri->addParams($arAddParams);
		return $uri->getUri();
	}
}