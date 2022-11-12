<?php
namespace Hipot\BitrixUtils;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Engine\CurrentUser;

/**
 * Класс для работы с кешированием (как обертка над логикой в виде анонимной функции, возвращающей данные)
 * @version 3.0
 */
class PhpCacher
{
	/**
	 * @var \Bitrix\Main\Data\Cache
	 */
	private $cache;
	/**
	 * @var \Bitrix\Main\Data\TaggedCache
	 */
	private $taggedCache;
	/**
	 * @var \Bitrix\Main\Engine\CurrentUser
	 */
	private $user;
	/**
	 * @var \Bitrix\Main\HttpRequest|\Bitrix\Main\Request
	 */
	private $request;

	/**
	 * Последняя ошибка при попытке записи в кеш
	 * @var string
	 */
	public static string $LAST_ERROR = '';

	public function __construct()
	{
		$app = Application::getInstance();
		$this->cache = Cache::createInstance();
		$this->taggedCache = $app->getTaggedCache();
		$this->user = CurrentUser::get();
		$this->request = $app->getContext()->getRequest();
	}

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
	 * @return boolean|array|null
	 */
	public static function cache(string $tagName, int $cacheTime, callable $callbackFunction, array $params = [])
	{
		$cacher = new self();
		self::$LAST_ERROR = '';

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

		$cacheId     = 'cacher_' . md5(serialize($params) . $tagName);
		$cacheDir    = self::getCacheDir($tagName);

		// clear cache now clear folder
		if ($cacher->request->get('clear_cache') === 'Y' && $cacher->canCurrentUserDropCache()) {
			$cacher->cache->cleanDir($cacheDir);
		}

		if ($cacher->cache->startDataCache($cacheTime, $cacheId, $cacheDir)) {
			$cacher->taggedCache->startTagCache($cacheDir);

			// is_callable tests above
			$params['cacher'] = $cacher;
			$data = $callbackFunction($params);

			$cacher->taggedCache->endTagCache();

			if ($data !== null) {
				$cacher->cache->endDataCache($data);
			} else {
				$cacher->cache->abortDataCache();
				self::$LAST_ERROR = 'NO DATA PUSH TO CACHE...';
				return false;
			}
		} else {
			$data = $cacher->cache->getVars();
		}
		return $data;
	}

	/**
	 * Для кеширования объектов по id (или code) нужно их складировать в файловую систему по хешу,
	 * т.е. тег передавать в виде PhpCacher::cache('some_tag' . getCacheSubDirById($id), ...)
	 *
	 * @param int|string $id
	 * @return string
	 */
	public static function getCacheSubDirById($id): string
	{
		return '/' . substr(md5($id), 2, 2) . '/' . $id;
	}

	/**
	 * Удаление кеша по тегу (через API и не только файлы удаляет)
	 *
	 * @param string $tagName
	 */
	public static function clearDirByTag(string $tagName): void
	{
		$path = self::getCacheDir($tagName);
		$cacher = new self();
		$cacher->cache->cleanDir($path);
	}

	/**
	 * Удаление кеша по тегам из управляемого кеша
	 * @param string|array $tags
	 */
	public static function clearByManagedTags($tags): void
	{
		if (! defined("BX_COMP_MANAGED_CACHE")) {
			return;
		}
		if (! is_array($tags)) {
			$tags = [$tags];
		}
		$cacher = new self();
		foreach ($tags as $tag) {
			$cacher->taggedCache->clearByTag($tag);
		}
	}

	/////////////////////////////////

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

	private function canCurrentUserDropCache(): bool
	{
		// if cacher used in init.php
		if (! is_object($this->user)) {
			return true;
		}
		return $this->user->isAdmin();
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
	public static function returnCacheDataAndSave($tagName, $cacheTime, $callbackFunction, array $params = [])
	{
		return self::cache($tagName, $cacheTime, $callbackFunction, $params);
	}
} //end class


