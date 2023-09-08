<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:04
 * @version pre 1.0
 */
namespace Hipot\Utils;

use Bitrix\Main\Application;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Web\HttpClient;

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
	 * Возвращает код, сгенерированный компонентом Битрикс
	 * @param string $name Имя компонента
	 * @param string $template Шаблон компонента
	 * @param array $params Параметры компонента
	 * @param mixed $componentResult Данные, возвращаемые компонентом
	 * @return string
	 * @see \CMain::IncludeComponent()
	 */
	public static function getComponent($name, $template = '', $params = [], &$componentResult = null): string
	{
		/** @var $GLOBALS array{'DB':\CDatabase, 'APPLICATION':\CMain, 'USER':\CUser, 'USER_FIELD_MANAGER':\CUserTypeManager, 'CACHE_MANAGER':\CCacheManager, 'stackCacheManager':\CStackCacheManager} */
		ob_start();
		$componentResult = $GLOBALS['APPLICATION']->IncludeComponent($name, $template, $params, null, [], true);
		return ob_get_clean();
	}

	/**
	 * Возвращает код, сгенерированный включаемой областью Битрикс
	 * @param string $path Путь до включаемой области
	 * @param array $params Массив параметров для подключаемого файла
	 * @param array $functionParams Массив настроек данного метода
	 * @return string
	 * @see \CMain::IncludeFile()
	 */
	public static function getIncludeArea($path, $params = [], $functionParams = []): string
	{
		/** @var $GLOBALS array{'DB':\CDatabase, 'APPLICATION':\CMain, 'USER':\CUser, 'USER_FIELD_MANAGER':\CUserTypeManager, 'CACHE_MANAGER':\CCacheManager, 'stackCacheManager':\CStackCacheManager} */
		ob_start();
		$GLOBALS['APPLICATION']->IncludeFile($path, $params, $functionParams);
		return ob_get_clean();
	}

	/**
	 * Подключает видео-проигрыватель битрикса
	 *
	 * @param string $videoFile
	 * @param int $width
	 * @param int $height
	 * @param \CBitrixComponent|null $component
	 * @param bool $adaptiveFixs = true установка ширины плеера согласно текущей ширины блока, но с соотношением переданной ширины/высоты
	 *
	 * @return void
	 */
	public static function insertVideoBxPlayer(string $videoFile, int $width = 800, int $height = 450, $component = null, bool $adaptiveFixs = true): void
	{
		global $APPLICATION;

		$videoId = 'video_' . md5($videoFile . randString());
		if ($adaptiveFixs) {
			?>
			<script>
				BX.ready(() => {
					<?php
					/*
					BX.addCustomEvent('PlayerManager.Player:onAfterInit', (player) => {
						if (typeof $(player.getElement()).data('resize_koef') === 'undefined') {
							$(player.getElement()).data('resize_koef', $(player.getElement()).height() / $(player.getElement()).width()).css({
								'width': '100%',
							});
						}
						$(window).resize(() => {
							$(player.getElement()).css({
								'width' : '100%',
								'height': $(player.getElement()).width() * $(player.getElement()).data('resize_koef')
							});
						}).resize();
					});
					*/?>
					BX.addCustomEvent('PlayerManager.Player:onBeforeInit', (player) => {
						// https://docs.videojs.com/player#fluid
						player.params['fluid'] = true;
					});
				});
			</script>
			<?
		}

		$APPLICATION->IncludeComponent(
			"bitrix:player",
			"",
			[
				//"PREVIEW" => "",
				"ADVANCED_MODE_SETTINGS" => "N",
				"AUTOSTART" => "N",
				"AUTOSTART_ON_SCROLL" => "N",

				"WIDTH" => $width,
				"HEIGHT" => $height,
				'PLAYER_ID' => $videoId,
				"PATH" => trim($videoFile),

				"MUTE" => "N",
				"PLAYBACK_RATE" => "1",
				"PLAYER_TYPE" => "auto",
				"PRELOAD" => "N",
				"BUFFER_LENGTH" => "15",
				"REPEAT" => "none",
				"SHOW_CONTROLS" => "Y",
				"SIZE_TYPE" => "absolute",
				"SKIN" => "",
				"SKIN_PATH" => "/bitrix/js/fileman/player/videojs/skins",
				"START_TIME" => "0",
				"VOLUME" => 60,

			], $component, ["HIDE_ICONS" => "Y"]
		);
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

		return 'data: '.mime_content_type($img_file).';base64,'.base64_encode(file_get_contents($img_file));
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

	public static function getCurrentUser(): ?CurrentUser
	{
		/**
		 * CurrentUser::get()->isAdmin() вызывает ошибку Uncaught Error: Call to a member function isAdmin() on null, когда нет $USER
		 * (везде в порядке выполнения страницы https://dev.1c-bitrix.ru/api_help/main/general/pageplan.php до п.1.9) и агентах.
		 *
		 * геттера к внутреннему приватному полю нет, чтобы можно было проверять так:
		 *
		 * CurrentUser::getCUser() !== null && CurrentUser::get()->isAdmin()
		 *
		 * а по хорошему сделать проверку на инварианты: не создавать объект CurrentUser в методе get(), если global $USER === null
		 * тогда можно было бы использовать nullsafe-operator:
		 *
		 * CurrentUser::get()?->isAdmin()
		 */
		$bInternalUserExists = ( (function () {
				return $this->cuser;
			})->bindTo(CurrentUser::get(), CurrentUser::get()) )() !== null;
		return $bInternalUserExists ? CurrentUser::get() : null;
	}
}