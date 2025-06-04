<?php

namespace Hipot\Services;

use Bitrix\Main\Application;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Request;
use Bitrix\Main\Session\SessionInterface;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\EventManager;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorageManager;
use Hipot\Types\Singleton;

final class BitrixEngine
{
	use Singleton;

		public ?Application                $app = null;
		/**
		 * @var \Bitrix\Main\Request|\Bitrix\Main\HttpRequest
		 */
		public ?Request                    $request = null;
		public ?CurrentUser                $user = null;
		public ?Cache                      $cache = null;
		public ?TaggedCache                $taggedCache = null;
		public ?Asset                      $asset = null;
		public ?SessionInterface           $session = null;
		public ?ServiceLocator             $serviceLocator = null;
		public ?Connection                 $connection = null;
		public ?EventManager               $eventManager = null;
		public ?SessionLocalStorageManager $sessionLocalStorageManager = null;

	public function __construct(
		Application $app,
		Request $request,
		?CurrentUser $user,
		Cache $cache,
		TaggedCache $taggedCache,
		Asset $asset,
		SessionInterface $session,
		ServiceLocator $serviceLocator,
		Connection $connection,
		EventManager $eventManager,
		SessionLocalStorageManager $sessionLocalStorageManager
	)
	{
		$this->app = $app;
		$this->request = $request;
		$this->user = $user;
		$this->cache = $cache;
		$this->taggedCache = $taggedCache;
		$this->asset = $asset;
		$this->session = $session;
		$this->serviceLocator = $serviceLocator;
		$this->connection = $connection;
		$this->eventManager = $eventManager;
		$this->sessionLocalStorageManager = $sessionLocalStorageManager;
	}

	public static function initInstance(): self
	{
		return new self(
			Application::getInstance(),
			Application::getInstance()->getContext()->getRequest(),
			self::getCurrentUser(),
			Cache::createInstance(['actual_data' => false]), // "locking mode" @see https://dev.1c-bitrix.ru/community/blogs/rns/interesnye-izmeneniya-v-main-2400.php
			Application::getInstance()->getTaggedCache(),
			Asset::getInstance(),
			Application::getInstance()->getSession(),
			ServiceLocator::getInstance(),
			Application::getConnection(),
			EventManager::getInstance(),
			Application::getInstance()->getSessionLocalStorageManager()
		);
	}

	/**
	 * CurrentUser::get()->isAdmin() вызывает ошибку Uncaught Error: Call to a member function isAdmin() on null, когда нет $USER
	 * (везде в порядке выполнения страницы https://dev.1c-bitrix.ru/api_help/main/general/pageplan.php до п.1.9) и агентах.<br><br>
	 *
	 * геттера к внутреннему приватному полю нет, чтобы можно было проверять так:<br><br>
	 *
	 * <code>CurrentUser::getCUser() !== null && CurrentUser::get()->isAdmin()</code><br><br>
	 *
	 * а по хорошему сделать проверку на инварианты: не создавать объект CurrentUser в методе get(), если global $USER === null
	 * тогда можно было бы использовать nullsafe-operator:<br><br>
	 *
	 * <code>CurrentUser::get()?->isAdmin()</code>
	 */
	public static function getCurrentUser(): ?CurrentUser
	{
		$bInternalUserExists = ( (function () {
				return $this->cuser;
			})->bindTo(CurrentUser::get(), CurrentUser::get()) )() !== null;
		return $bInternalUserExists ? CurrentUser::get() : null;
	}

	/**
	 * Retrieves the current user from the global $USER variable.
	 *
	 * @return \CUser The current user if found, an instance of \CUser otherwise.
	 */
	public static function getCurrentUserD0(): \CUser
	{
		global $USER;
		if (! is_a($USER, \CUser::class)) {
			$USER = new \CUser();
		}
		return $USER;
	}

	/**
	 * Retrieves a service by its name from the service locator.
	 *
	 * @param string $serviceName The name of the service to retrieve
	 * @return mixed|null The retrieved service if found, null otherwise.
	 */
	public function getService(string $serviceName)
	{
		return $this->serviceLocator->has($serviceName) ? $this->serviceLocator->get($serviceName) : null;
	}

	/**
	 * Static method returns database connection for the specified name.
	 * If name is empty - default connection is returned.
	 * @param string $name Name of database connection. If empty - default connection.
	 * @return \Bitrix\Main\Data\Connection|\Bitrix\Main\DB\Connection|null
	 */
	public function getConnection(string $name = "")
	{
		return $this->app->getConnectionPool()->getConnection($name);
	}

	/**
	 * Retrieves the main application object on global $APPLICATION variable
	 *
	 * @return \CMain The main application object.
	 */
	public static function getAppD0(): \CMain
	{
		global $APPLICATION;
		if (! is_a($APPLICATION, \CMain::class)) {
			$APPLICATION = new \CMain();
		}
		return $APPLICATION;
	}
}