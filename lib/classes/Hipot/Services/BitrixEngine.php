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
use Bitrix\Main\SiteTable;
use Hipot\Types\Singleton;

final class BitrixEngine
{
	use Singleton;

	public function __construct(
		public ?Application                $app = null,
		/**
		 * @var \Bitrix\Main\Request|\Bitrix\Main\HttpRequest
		 */
		public ?Request                    $request = null,
		public ?CurrentUser                $user = null,
		public ?Cache                      $cache = null,
		public ?TaggedCache                $taggedCache = null,
		public ?Asset                      $asset = null,
		public ?SessionInterface           $session = null,
		public ?ServiceLocator             $serviceLocator = null,
		public ?Connection                 $connection = null,
		public ?EventManager               $eventManager = null,
		public ?SessionLocalStorageManager $sessionLocalStorageManager = null,
	)
	{
	}

	public static function initInstance(): self
	{
		return new self(
			Application::getInstance(),
			Application::getInstance()?->getContext()?->getRequest(),
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
	 * Retrieves the global user field manager instance.
	 * @return \CUserTypeManager The user field manager instance.
	 * @global \CUserTypeManager $USER_FIELD_MANAGER The global user field manager instance.
	 */
	public static function getUserFieldManager(): \CUserTypeManager
	{
		/** @noinspection GlobalVariableUsageInspection */
		return $GLOBALS['USER_FIELD_MANAGER'];
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
	 * Retrieves the site identifier based on the current request context.
	 * @return string The site ID determined from the admin section or application context.
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getSiteId(): string
	{
		if ($this->request->isAdminSection()) {
			$site = SiteTable::getByDomain($this->request->getHttpHost(), $this->request->getRequestedPageDirectory());
			return SiteTable::wakeUpObject($site)->getLid();
		}
		return $this->app->getContext()->getSite();
	}
}