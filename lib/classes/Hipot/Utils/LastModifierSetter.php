<?php
/**
 * Класс для установки корректного заголовка Last-Modified для статики и динамики.
 * Также обработка заголовка HTTP_IF_MODIFIED_SINCE
 *
 * nginx composite config: add_header Last-Modified $date_gmt;
 *
 * @version 2.0
 * @author hipot 2019
 */
namespace Hipot\Utils;

/**
 * Установка заголовка Last-Modified для статики и динамики.
 * Также обработка заголовка HTTP_IF_MODIFIED_SINCE
 */
class LastModifierSetter
{
	/**
	 * запуск в файле init.php
	 * можно сказать, что это единая точка входа в установщик заголовка
	 */
	public static function initRunner(): void
	{
		global $APPLICATION;

		$curPage	= $APPLICATION->GetCurPage(true);
		$file		= $_SERVER['DOCUMENT_ROOT'] . $curPage;

		// если это не статика, вешаем проверяльщик перед выводом страницы
		// (компонент может не установить заголовок, тогда нужно отослать текущий)
		if (! file_exists($file)) {
			self::setEndBufferPageChecker();
			return;
		} else {
			// если на странице вызван компонент, то тоже вешаем проверяльщик перед выводом страницы
			// (компонент может не установить, тогда нужно отослать текущий)
			$str = file_get_contents($file);
			$str = preg_replace('~[\n\t\r\s ]+~', '', $str);
			if (preg_match('~\$APPLICATION\s*->\s*IncludeComponent~i', $str)) {
				self::setEndBufferPageChecker();
			} else {
				// работа со статикой
				$LastModified_unix = filemtime($file);
				self::sendHeader($LastModified_unix);
			}
		}
	}


	/**
	 * посылка заголовка Last-Modified
	 * @param bool|int $timeStamp = time() дата изменения в юникс-формате
	 * @param bool $checkModifiedSince = true проверять ли запрос вида HTTP_IF_MODIFIED_SINCE
	 *        в этом случае выдавать страницу не нужно, а нужно сообщить изменилась она или нет
	 */
	public static function sendHeader($timeStamp = false, $checkModifiedSince = true): void
	{
		if ((int)$timeStamp <= 0) {
			$timeStamp = time();
		}

		$IfModifiedSince = false;
		if (isset($_ENV['HTTP_IF_MODIFIED_SINCE'])) {
			$IfModifiedSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5));
		}
		if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
			$IfModifiedSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
		}
		if ($IfModifiedSince && $IfModifiedSince >= $timeStamp && $checkModifiedSince) {
			header($_SERVER['SERVER_PROTOCOL'] . ' 304 Not Modified');
			exit;
		}

		header('Last-Modified: '. gmdate("D, d M Y H:i:s \G\M\T", $timeStamp));
	}


	/**
	 * Вешаем проверяльщик на событие модуля main - OnEndBufferContent.
	 */
	protected static function setEndBufferPageChecker(): void
	{
		\AddEventHandler(
			"main", "OnEndBufferContent",
			[__CLASS__, "LastModifierSetterEndBufferContent"]
		);
	}


	/**
	 * Обработчик события модуля main - OnEndBufferContent.
	 * Если заголовка Last-Modified в заголовках к отправке не найдено, то высылает текущий
	 * @param string $content - содержимое всей страницы
	 */
	public static function LastModifierSetterEndBufferContent(&$content): void
	{
		// если компонент устанавил дату, то
		if ((int)self::$LastModified_unix_Component > 0) {
			self::sendHeader(self::$LastModified_unix_Component);
		} else {
			// иначе ищем, устанавливал ли кто-то такой заголовок
			$bFind = false;
			foreach (headers_list() as $header) {
				if (preg_match('#Last\-Modified#i', $header)) {
					$bFind = true;
					break;
				}
			}
			if (! $bFind) {
				self::sendHeader();
			}
		}
	}

	/***********************************************************************************
	        установка в динамике для компонентов iblock.list и iblock.detail
	***********************************************************************************/

	/**
	 * Время последнего изменения даты компонента (в component_epilog.php устанавливается)
	 * @var int
	 */
	protected static $LastModified_unix_Component = false;


	/**
	 * Для вызова в файле result_modifier.php компонентов iblock.list и iblock.detail
	 * Добавляет в кеш компонента дату LAST_MODIFIED.
	 * Обязательно у элементов отбирать TIMESTAMP_X!!!
	 * Формат массива $arResult - это либо один элемент инфоблока, либо массив элементов $arResult['ITEMS']
	 *
	 * @param array $arResult массив $arResult компонента iblock.list и iblock.detail
	 * @param CBitrixComponentTemplate $cbt объект шаблона компонента, передавать просто $this
	 */
	public static function componentResultModifierRunner(&$arResult, $cbt): void
	{
		self::$LastModified_unix_Component = false;

		$arResultEx = $arResult;
		if (count($arResultEx['ITEMS']) == 0) {
			$arResultEx = array(
				'ITEMS'		=> array(
					$arResult
				)
			);
		}

		// находим из всех выбранных элементов инфоблока самое последнее время изменения
		$LastModified_unix = 0;

		foreach ($arResultEx['ITEMS'] as $arItem) {
			if (trim($arItem['TIMESTAMP_X']) == '') {
				continue;
			}

			$arTime = ParseDateTime($arItem['TIMESTAMP_X']);
			$timeTmp = mktime($arTime['HH'], $arTime['MI'], $arTime['SS'], $arTime['MM'], $arTime['DD'], $arTime['YYYY']);
			if ($timeTmp > $LastModified_unix) {
				$LastModified_unix = $timeTmp;
			}
		}

		// объект компонента
		$cp = $cbt->__component;
		if (is_object($cp) && (int)$LastModified_unix > 0) {
			$cp->arResult['LAST_MODIFIED'] = $LastModified_unix;
			$cp->SetResultCacheKeys(['LAST_MODIFIED']);
			if (! isset($arResult['LAST_MODIFIED'])) {
				$arResult['LAST_MODIFIED'] = $cp->arResult['LAST_MODIFIED'];
			}
		}
	}

	/**
	 * Для вызова в файле шаблона component_epilog.php
	 * Шлет выбранный LAST_MODIFIED из $arResult (если он выбрался через
	 * LastModirierSetter::componentResultModifierRunner)
	 *
	 * @param array $arResult массив $arResult компонента
	 */
	public static function componentEpilogRunner($arResult): void
	{
		if ($arResult["LAST_MODIFIED"] > 0) {
			self::setLastModified($arResult["LAST_MODIFIED"]);
		}
	}

	/**
	 * Произвольная установка заголовка LAST_MODIFIED в любом месте
	 *
	 * @param int $unix_ts - время в формате unix
	 */
	public static function setLastModified($unix_ts): void
	{
		if ($unix_ts > 0) {
			self::$LastModified_unix_Component = $unix_ts;
		}
	}

} // \end class
