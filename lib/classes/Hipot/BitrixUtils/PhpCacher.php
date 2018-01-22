<?
namespace Hipot\BitrixUtils;

/**
 * Класс для работы с кешированием
 */
class PhpCacher
{
	/**
	 * Массив параметров функции для проброса параметров в анонимную функцию
	 *
	 * @var array
	 */
	public static $params = array();

	/**
	 * Последняя ошибка при попытке записи в кеш
	 *
	 * @var string
	 */
	public static $LAST_ERROR = '';


	/**
	 * Удаление по тегу
	 *
	 * @param string $tagName
	 *
	 * @return bool
	 */
	public static function clearDirByTag($tagName)
	{
		$path = self::getCacheDir($tagName, true);
		if ($path) {
			return \DeleteDirFilesEx($path);
		}

		return false;
	}

	/**
	 * Получить путь к папке для записи кеша
	 *
	 * @param string $tagName
	 * @param bool $relRootPath - false - относительно '/bitrix/cache/', true - относительно корня сайта
	 * @return boolean|string
	 */
	private static function getCacheDir($tagName, $relRootPath = false)
	{
		if (($tagName = trim($tagName)) == '') {
			return false;
		}

		$path = '/php/' . $tagName . '/';

		if ($relRootPath) {
			$path = '/bitrix/cache' . $path;
		}

		return $path;
	}

	/**
	 * Записываем данные в кеш
	 *
	 * В случае ошибки в переменной $LAST_ERROR - будет строка с ошибкой
	 *
	 * @param string $tagName - тег (массив тегов)
	 * @param int $cacheTime - время кеша
	 * @param callable function $callbackFunction
	 * @param array $params - массив параметров функции
	 * @return boolean|array
	 */
	public static function returnCacheDataAndSave($tagName, $cacheTime, $callbackFunction, $params = array())
	{
		self::$LAST_ERROR = '';

		if (is_array($tagName)) {
			self::$LAST_ERROR = 'BAD TAG NAME TO BE STRING...';
			return false;
		}

		if (($tagName = trim($tagName)) == '') {
			self::$LAST_ERROR = 'BAD TAG NAME ARGUMENT...';
			return false;
		}

		if (($cacheTime = (int)$cacheTime) < 0) {
			self::$LAST_ERROR = 'BAD CACHE TIME ARGUMENT...';
			return false;
		}

		if (! is_callable($callbackFunction)) {
			self::$LAST_ERROR = 'BAD CALLBACK FUNC ARGUMENT...';
			return false;
		}

		if (! is_array($params) || empty($params)) {
			$params = array();
		}

		$CACHE_ID = 'cacher_' . md5(
			serialize($params) . $tagName . $cacheTime
		);

		// путь задается относительно папки /bitrix/cache/
		$CACHE_DIR = self::getCacheDir( $tagName );

		$obCache = new \CPHPCache;
		if ($obCache->StartDataCache($cacheTime, $CACHE_ID, $CACHE_DIR)) {

			self::$params = $params;
			// если передавать через array($params) получаем сразу захват всех параметров
			$data = call_user_func_array($callbackFunction, array($params));
			self::$params = array();

			if (! empty($data)) {
				$obCache->EndDataCache( $data );
			} else {
				$obCache->AbortDataCache();
				self::$LAST_ERROR = 'NO DATA PUSH TO CACHE...';
				return false;
			}
		} else {
			$data = $obCache->GetVars();
		}
		return $data;
	}


}



