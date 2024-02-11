<?php
namespace Hipot\BitrixUtils;

use Hipot\Services\BitrixEngine;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\CacheEngine;

/**
 * Класс для работы с кешированием (как обертка над логикой в виде анонимной функции, возвращающей данные)
 * @version 4.0
 */
final class PhpCacher
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

	public function __construct(BitrixEngine $engine, ?CacheEngine $cacheEngine = null)
	{
		if ($cacheEngine === null) {
			$this->cache = clone $engine->cache;
		} else {
			$this->cache = new Cache($cacheEngine);
		}
		$this->taggedCache = $engine->taggedCache;
		$this->user        = $engine->user;
		$this->request     = $engine->request;
	}

	public static function getInstance(string $cacheServiceName = ''): self
	{
		return new self(BitrixEngine::getInstance(), BitrixEngine::getInstance()->getService($cacheServiceName));
	}

	/**
	 * Записываем и возвращает данные в кэш по пути /bitrix/cache/php/$tagName/ с возможностью указать в функции $callbackFunction теги для кэша.
	 *
	 * В случае ошибки в статичной переменной $LAST_ERROR - будет строка с ошибкой
	 *
	 * @param string                   $tagName  тег хранения по пути /bitrix/cache/php/$tagName/
	 * @param int                      $cacheTime время кеша
	 * @param callable                 $callbackFunction в анонимной функции можно регистрировать и теги для управляемого кеша:
	 * <code>
	 *     BitrixEngine::getInstance()->taggedCache->registerTag("iblock_id_43");
	 *     BitrixEngine::getInstance()->taggedCache->registerTag("iblock_id_new");
	 *
	 *     // Либо отключить их использование глобальной константой PHPCACHER_TAGGED_CACHE_AUTOSTART = false
	 *     // или только в конкретном замыкании использовать конструкцию:
	 *     BitrixEngine::getInstance()->taggedCache->abortTagCache();</code>
	 * @param string $cacheServiceName '' Использовать DI для службы кэширования (настроить в .settings_extra.php службу 'cache.apc'). Глобально задается
	 * константой <code>PHPCACHER_DEFAULT_CACHE_SERVICE = 'cache.apc'</code>)
	 *
	 * @param array $params массив параметров функции (передаются в <code>$callbackFunction({'cacher':\WeakReference})</code> а также влияют на идентификатор кеша $cacheId)
	 *
	 * @return false|array|mixed
	 */
	public static function cache(string $tagName, int $cacheTime, callable $callbackFunction, string $cacheServiceName = '', array $params = [])
	{
		$useCacheServiceName = defined(PHPCACHER_DEFAULT_CACHE_SERVICE) ? PHPCACHER_DEFAULT_CACHE_SERVICE : $cacheServiceName;
		return self::getInstance($useCacheServiceName)->cacheInternal($tagName, $cacheTime, $callbackFunction, $params);
	}

	/**
	 * @return false|array|mixed
	 */
	private function cacheInternal(string $tagName, int $cacheTime, callable $callbackFunction, array $params = [])
	{
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
		if ($this->request->get('clear_cache') === 'Y' && $this->canCurrentUserDropCache()) {
			$this->cache->cleanDir($cacheDir);
		}

		$this->cache->noOutput();
		$bTaggedCacheUse = (!defined('PHPCACHER_TAGGED_CACHE_AUTOSTART') || PHPCACHER_TAGGED_CACHE_AUTOSTART === true);

		if ($this->cache->startDataCache($cacheTime, $cacheId, $cacheDir)) {
			if ($bTaggedCacheUse) {
				$this->taggedCache->startTagCache($cacheDir);
			}

			$params['cacher'] = \WeakReference::create($this);
			$data = $callbackFunction($params);
			unset($params['cacher']);

			if ($bTaggedCacheUse) {
				$this->taggedCache->endTagCache();
			}

			if ($data !== null) {
				$this->cache->endDataCache($data);
			} else {
				$this->cache->abortDataCache();
				self::$LAST_ERROR = 'NO DATA PUSH TO CACHE...';
				return false;
			}
		} else {
			$data = $this->cache->getVars();
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
		$id = (string)$id;
		return '/' . substr(md5($id), 2, 2) . '/' . $id;
	}

	/**
	 * Удаление кеша по тегу (через API и не только файлы удаляет)
	 *
	 * @param string $tagName
	 */
	public static function clearDirByTag(string $tagName): void
	{
		$cacher = self::getInstance();
		$path = self::getCacheDir($tagName);
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
		$cacher = new self( BitrixEngine::getInstance() );
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
	private static function getCacheDir(string $tagName): string
	{
		if (($tagName = trim($tagName)) === '') {
			return false;
		}
		return '/php/' . $tagName;
	}

	/**
	 * @return bool
	 * @internal
	 */
	private function canCurrentUserDropCache(): bool
	{
		// if cacher used in init.php
		if (!is_object($this->user)) {
			return true;
		}
		try {
			return $this->user->isAdmin();
		} catch (\Throwable) {
			// anonymous user
			return false;
		}
	}

	/**
	 * @deprecated use PhpCacher::cache(...)
	 */
	public static function returnCacheDataAndSave($tagName, $cacheTime, $callbackFunction, array $params = [])
	{
		return self::cache($tagName, $cacheTime, $callbackFunction, '', $params);
	}

	/**
	 * Prevents output caching for a specific instance of CPHPCache.
	 * @param \CPHPCache $cache The instance of CPHPCache to disable output caching for.
	 * @return void
	 */
	public static function noOutputCacheD0(\CPHPCache $cache): void
	{
		((function () {
			$this->cache->noOutput();
		})->bindTo($cache, $cache))();
	}

} //end class


