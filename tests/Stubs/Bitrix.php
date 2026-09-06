<?php

declare(strict_types=1);

namespace Bitrix\Main {
	use Bitrix\Main\Data\LocalStorage\SessionLocalStorageManager;
	use Bitrix\Main\Data\TaggedCache;
	use Bitrix\Main\DB\Connection;
	use Bitrix\Main\Session\SessionInterface;

	class Request
	{
		public function __construct(
			private readonly bool $adminSection = false,
			private readonly string $httpHost = 'example.test',
			private readonly string $requestedPageDirectory = '/',
		) {
		}

		public function isAdminSection(): bool
		{
			return $this->adminSection;
		}

		public function getHttpHost(): string
		{
			return $this->httpHost;
		}

		public function getRequestedPageDirectory(): string
		{
			return $this->requestedPageDirectory;
		}
	}

	final class Context
	{
		public function __construct(
			private readonly Request $request,
			private readonly string $site,
		) {
		}

		public function getRequest(): Request
		{
			return $this->request;
		}

		public function getSite(): string
		{
			return $this->site;
		}
	}

	final class ConnectionPool
	{
		/** @var array<string, Connection> */
		private array $connections = [];

		public function addConnection(string $name, Connection $connection): void
		{
			$this->connections[$name] = $connection;
		}

		public function getConnection(string $name = ''): ?Connection
		{
			return $this->connections[$name] ?? null;
		}
	}

	class Application
	{
		private static self $instance;
		private static Connection $connection;

		public function __construct(
			private readonly Context $context,
			private readonly TaggedCache $taggedCache,
			private readonly SessionInterface $session,
			private readonly SessionLocalStorageManager $sessionLocalStorageManager,
			private readonly ConnectionPool $connectionPool,
		) {
		}

		public static function setInstance(self $instance): void
		{
			self::$instance = $instance;
		}

		public static function setConnection(Connection $connection): void
		{
			self::$connection = $connection;
		}

		public static function getInstance(): self
		{
			return self::$instance;
		}

		public function getContext(): Context
		{
			return $this->context;
		}

		public function getTaggedCache(): TaggedCache
		{
			return $this->taggedCache;
		}

		public function getSession(): SessionInterface
		{
			return $this->session;
		}

		public function getSessionLocalStorageManager(): SessionLocalStorageManager
		{
			return $this->sessionLocalStorageManager;
		}

		public function getConnectionPool(): ConnectionPool
		{
			return $this->connectionPool;
		}

		public static function getConnection(): Connection
		{
			return self::$connection;
		}
	}

	final class EventManager
	{
		private static ?self $instance = null;

		public static function getInstance(): self
		{
			return self::$instance ??= new self();
		}
	}

	final class SiteObject
	{
		public function __construct(private readonly string $lid)
		{
		}

		public function getLid(): string
		{
			return $this->lid;
		}
	}

	final class SiteTable
	{
		public static string $siteId = 's1';
		/** @var array{host: string, directory: string}|null */
		public static ?array $lastLookup = null;

		public static function getByDomain(string $host, string $directory): string
		{
			self::$lastLookup = ['host' => $host, 'directory' => $directory];
			return self::$siteId;
		}

		public static function wakeUpObject(string $siteId): SiteObject
		{
			return new SiteObject($siteId);
		}
	}
}

namespace Bitrix\Main\DB {
	class Connection
	{
		public function __construct(public readonly string $name = '')
		{
		}
	}
}

namespace Bitrix\Main\Data {
	class Cache
	{
		/** @param array<string, mixed> $options */
		public function __construct(public readonly array $options = [])
		{
		}

		/** @param array<string, mixed> $options */
		public static function createInstance(array $options = []): self
		{
			return new self($options);
		}
	}

	class TaggedCache
	{
	}
}

namespace Bitrix\Main\Data\LocalStorage {
	class SessionLocalStorageManager
	{
	}
}

namespace Bitrix\Main\Engine {
	class CurrentUser
	{
		private static self $instance;

		public function __construct(private readonly mixed $cuser = null)
		{
		}

		public static function set(self $currentUser): void
		{
			self::$instance = $currentUser;
		}

		public static function get(): self
		{
			return self::$instance;
		}
	}
}

namespace Bitrix\Main\Page {
	class Asset
	{
		private static ?self $instance = null;

		public static function getInstance(): self
		{
			return self::$instance ??= new self();
		}
	}
}

namespace Bitrix\Main\Session {
	interface SessionInterface
	{
	}

	final class ArraySession implements SessionInterface
	{
	}
}

namespace Bitrix\Main\DI {
	class ServiceLocator
	{
		private static ?self $instance = null;
		/** @var array<string, mixed> */
		private array $services = [];

		public static function getInstance(): self
		{
			return self::$instance ??= new self();
		}

		public function set(string $name, mixed $service): void
		{
			$this->services[$name] = $service;
		}

		public function has(string $name): bool
		{
			return array_key_exists($name, $this->services);
		}

		public function get(string $name): mixed
		{
			return $this->services[$name];
		}
	}
}

namespace {
	class CDBResult
	{
	}

	class CUser
	{
	}

	class CMain
	{
	}

	class CUserTypeManager
	{
	}
}
