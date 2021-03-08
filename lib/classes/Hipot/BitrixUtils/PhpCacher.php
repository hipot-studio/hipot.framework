<?
/** @noinspection PhpIllegalPsrClassPathInspection */
namespace Hipot\BitrixUtils;

use CPHPCache;

/**
 * Класс для работы с кешированием (как обертка над логикой в виде анонимной функции, возвращающей данные)
 * @version 2.4
 */
class PhpCacher
{
	/**
	 * Последняя ошибка при попытке записи в кеш
	 * @var string
	 */
	public static $LAST_ERROR = '';

	/**
	 * Массив параметров функции для проброса параметров в анонимную функцию
	 * @var array
	 */
	public static $params = [];

	/**
	 * Записываем и возвращает данные в кеш по пути /bitrix/cache/php/$tagName/ с возможностью указать
	 * в функции $callbackFunction теги для кеша.
	 *
	 * В случае ошибки в статичной переменной $LAST_ERROR - будет строка с ошибкой
	 *
	 * @param string                   $tagName - тег (массив тегов)
	 * @param int                      $cacheTime - время кеша
	 * @param callable                 $callbackFunction в анонимной функции можно регистрировать и теги для управляемого кеша:
	 * <pre>global $CACHE_MANAGER;
	 * $CACHE_MANAGER->RegisterTag("iblock_id_43");
	 * $CACHE_MANAGER->RegisterTag("iblock_id_new");</pre>
	 * @param array                    $params - массив параметров функции (deprecated, для старых версий php)
	 * данные параметры влияют на идентификатор кеша $CACHE_ID
	 *
	 * @return boolean|array
	 */
	public static function cache(string $tagName, int $cacheTime, callable $callbackFunction, array $params = [])
	{
		self::$LAST_ERROR = '';

		if (! is_string($tagName)) {
			self::$LAST_ERROR = 'BAD TAG NAME TO BE STRING...';
			return false;
		}

		if (($tagName = trim($tagName)) == '') {
			self::$LAST_ERROR = 'BAD TAG NAME ARGUMENT...';
			return false;
		}

		if ($cacheTime < 0) {
			self::$LAST_ERROR = 'BAD CACHE TIME ARGUMENT...';
			return false;
		}

		if (!is_callable($callbackFunction)) {
			self::$LAST_ERROR = 'BAD CALLBACK FUNC ARGUMENT...';
			return false;
		}

		if (!is_array($params) || empty($params)) {
			$params = [];
		}

		$CACHE_ID     = 'cacher_' . md5(serialize($params) . $tagName);
		$CACHE_DIR    = self::getCacheDir($tagName);

		$obCache = new CPHPCache();

		// clear cache now clear folder
		if ($_REQUEST['clear_cache'] === 'Y' && self::canCurrentUserDropCache()) {
			$obCache->CleanDir($CACHE_DIR);
		}

		if ($obCache->StartDataCache($cacheTime, $CACHE_ID, $CACHE_DIR)) {

			if (defined('BX_COMP_MANAGED_CACHE')) {
				$GLOBALS['CACHE_MANAGER']->StartTagCache($CACHE_DIR);
			}

			self::$params = $params;

			// is_callable tests above
			$data = $callbackFunction($params);

			self::$params = [];

			if (defined('BX_COMP_MANAGED_CACHE')) {
				$GLOBALS['CACHE_MANAGER']->EndTagCache();
			}

			if ($data !== null) {
				$obCache->EndDataCache($data);
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

	/**
	 * @param       $tagName
	 * @param       $cacheTime
	 * @param       $callbackFunction
	 * @param array $params
	 *
	 * @return array|bool
	 * @deprecated use PhpCacher::cache(...)
	 */
	public static function returnCacheDataAndSave($tagName, $cacheTime, $callbackFunction, $params = [])
	{
		return self::cache($tagName, $cacheTime, $callbackFunction, $params);
	}

	/**
	 * Получить путь к папке для записи кеша
	 * путь задается относительно папки /bitrix/cache/
	 *
	 * Далее определяется каталог относительно /bitrix/cache в котором будут сохранятся файлы кеша с разными значениями $params.
	 * Важно, что этот путь начинается со слеша и им не заканчивается.
	 * При использовании в качестве кеша memcached или APC это будет критичным при сбросе кеша.
	 *
	 * @param string $tagName
	 * @return boolean|string
	 */
	private static function getCacheDir(string $tagName)
	{
		if (($tagName = trim($tagName)) == '') {
			return false;
		}

		return '/php/' . $tagName;
	}

	/**
	 * Удаление кеша по тегу (через API и не только файлы удаляет)
	 *
	 * @param string $tagName
	 */
	public static function clearDirByTag(string $tagName): void
	{
		$path = self::getCacheDir($tagName);

		$obCache = new CPHPCache();
		$obCache->CleanDir($path);
	}

	/**
	 * Удаление кеша по тегам из управляемого кеша
	 *
	 * @param string|array $tags
	 */
	public static function clearByManagedTags($tags): void
	{
		if (defined('BX_COMP_MANAGED_CACHE')) {
			if (! is_array($tags)) {
				$tags = array($tags);
			}
			foreach ($tags as $tag) {
				$GLOBALS['CACHE_MANAGER']->ClearByTag($tag);
			}
		}
	}

	private static function canCurrentUserDropCache(): bool
	{
		// if cacher used in init.php
		if (! is_object($GLOBALS['USER'])) {
			return true;
		}
		return $GLOBALS['USER']->IsAdmin();
	}

} //end class


