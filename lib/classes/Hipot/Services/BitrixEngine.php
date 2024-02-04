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
use Hipot\Types\Singleton;

final class BitrixEngine
{
	use Singleton;

	public function __construct(
		public ?Application      $app = null,
		public ?Request          $request = null,
		public ?CurrentUser      $user = null,
		public ?Cache            $cache = null,
		public ?TaggedCache      $taggedCache = null,
		public ?Asset            $asset = null,
		public ?SessionInterface $session = null,
		public ?ServiceLocator   $serviceLocator = null,
		public ?Connection       $connection = null
	)
	{
	}

	public static function initInstance(): self
	{
		return new self(
			Application::getInstance(),
			Application::getInstance()?->getContext()?->getRequest(),
			self::getCurrentUser(),
			Cache::createInstance(),
			Application::getInstance()->getTaggedCache(),
			Asset::getInstance(),
			Application::getInstance()->getSession(),
			ServiceLocator::getInstance(),
			Application::getConnection()
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
}