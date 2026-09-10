<?php

declare(strict_types=1);

namespace Bitrix\Main {
	use Bitrix\Main\Data\LocalStorage\SessionLocalStorageManager;
	use Bitrix\Main\Data\TaggedCache;
	use Bitrix\Main\DB\Connection;
	use Bitrix\Main\Session\SessionInterface;

	final class Loader
	{
		/** @var array<string, bool> */
		public static array $modules = ['form' => true, 'iblock' => true, 'highloadblock' => true];
		public static array $includedModules = [];
		public static string $documentRoot = '';

		public static function includeModule(string $module): bool
		{
			self::$includedModules[] = $module;
			return self::$modules[$module] ?? false;
		}

		public static function requireModule(string $module): bool
		{
			return self::includeModule($module);
		}

		public static function getDocumentRoot(): string
		{
			return self::$documentRoot;
		}
	}

	final class UserFieldResult
	{
		public function __construct(private array $rows)
		{
		}

		public function fetch(): array|false
		{
			return array_shift($this->rows) ?? false;
		}
	}

	final class UserFieldTable
	{
		public static array $rows = [];
		public static array $lastQuery = [];

		public static function getList(array $query): UserFieldResult
		{
			self::$lastQuery = $query;
			$rows = self::$rows;
			foreach ($query['filter'] ?? [] as $field => $value) {
				$field = ltrim($field, '=');
				if ($field === 'MAIN_USER_FIELD_TITLE_LANGUAGE_ID') {
					continue;
				}
				$rows = array_values(array_filter(
					$rows,
					static fn(array $row): bool => ($row[$field] ?? null) === $value,
				));
			}
			return new UserFieldResult($rows);
		}
	}

	class ParameterDictionary
	{
		public function __construct(private array $values = [])
		{
		}

		public function get(string $name): mixed
		{
			return $this->values[$name] ?? null;
		}
	}

	class Request implements \ArrayAccess
	{
		public function __construct(
			private readonly bool $adminSection = false,
			private readonly string $httpHost = 'example.test',
			private readonly string $requestedPageDirectory = '/',
			private readonly array $values = [],
			private readonly array $serverValues = [],
			private readonly array $postValues = [],
			private readonly array $queryValues = [],
			private readonly bool $ajaxRequest = false,
			private readonly string $requestUri = '/',
		) {
		}

		public function getServer(): ParameterDictionary
		{
			return new ParameterDictionary($this->serverValues);
		}

		public function getPost(string $name): mixed
		{
			return $this->postValues[$name] ?? null;
		}

		public function getQuery(string $name): mixed
		{
			return $this->queryValues[$name] ?? null;
		}

		public function isAjaxRequest(): bool
		{
			return $this->ajaxRequest;
		}

		public function getRequestUri(): string
		{
			return $this->requestUri;
		}

		public function offsetExists(mixed $offset): bool
		{
			return array_key_exists($offset, $this->values);
		}

		public function offsetGet(mixed $offset): mixed
		{
			return $this->values[$offset] ?? null;
		}

		public function offsetSet(mixed $offset, mixed $value): void
		{
			throw new \LogicException('Request stub is immutable.');
		}

		public function offsetUnset(mixed $offset): void
		{
			throw new \LogicException('Request stub is immutable.');
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
			private readonly mixed $exceptionHandler = null,
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

		public function getExceptionHandler(): mixed
		{
			return $this->exceptionHandler;
		}

		public static function getConnection(): Connection
		{
			return self::$connection;
		}
	}

	final class EventManager
	{
		private static ?self $instance = null;
		public array $handlers = [];

		public static function getInstance(): self
		{
			return self::$instance ??= new self();
		}

		public function addEventHandler(string $module, string $event, callable $handler, bool $includeFile = false, int $sort = 100): int
		{
			$this->handlers[$module][$event][] = $handler;
			return array_key_last($this->handlers[$module][$event]);
		}

		public function addEventHandlerCompatible(string $module, string $event, callable $handler): int
		{
			return $this->addEventHandler($module, $event, $handler);
		}

		public function findEventHandlers(string $module, string $event): array
		{
			return $this->handlers[$module][$event] ?? [];
		}

		public function removeEventHandler(string $module, string $event, int $key): void
		{
			unset($this->handlers[$module][$event][$key]);
		}

		public function send(string $module, string $event, mixed &$argument = null): array
		{
			$results = [];
			foreach ($this->findEventHandlers($module, $event) as $handler) {
				$results[] = $handler($argument);
			}
			return $results;
		}

		public function reset(): void
		{
			$this->handlers = [];
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
		public bool $queryExecuting = true;
		public ?array $disabledQueryDump = null;

		public function __construct(public readonly string $name = '')
		{
		}

		public function disableQueryExecuting(): void
		{
			$this->queryExecuting = false;
		}

		public function enableQueryExecuting(): void
		{
			$this->queryExecuting = true;
		}

		public function getDisabledQueryExecutingDump(): ?array
		{
			return $this->disabledQueryDump;
		}
	}
}

namespace Bitrix\Main\Data {
	class ManagedCache
	{
		public function read($ttl, $uniqueId, $tableId = false): bool
		{
			return false;
		}

		public function get($uniqueId): mixed
		{
			return false;
		}

		public function set($uniqueId, $value): void
		{
		}

		public function clean($uniqueId, $tableId = false): void
		{
		}
	}

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
		public bool $optimizeCss = true;
		public bool $optimizeJs = true;
		public bool $jsToBody = true;

		public static function getInstance(): self
		{
			return self::$instance ??= new self();
		}

		public function disableOptimizeCss(): void
		{
			$this->optimizeCss = false;
		}

		public function disableOptimizeJs(): void
		{
			$this->optimizeJs = false;
		}

		public function setJsToBody(bool $value): void
		{
			$this->jsToBody = $value;
		}

		public static function canUseMinifiedAssets(): bool
		{
			return \Bitrix\Main\Config\Option::get('main', 'use_minified_assets', 'Y') === 'Y';
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

	final class CUserFieldEnumResult extends CDBResult
	{
		public function __construct(private array $rows)
		{
		}

		public function Fetch(): array|false
		{
			return array_shift($this->rows) ?? false;
		}
	}

	class CUserFieldEnum
	{
		/** @var array<int, array<int, string>> */
		public static array $values = [];
		public static array $rows = [];
		public static array $lastOrder = [];
		public static array $lastFilter = [];

		public static function GetList(array $order, array $filter): CUserFieldEnumResult
		{
			self::$lastOrder = $order;
			self::$lastFilter = $filter;
			$rows = self::$rows;
			if ($rows === []) {
				foreach (self::$values[(int)$filter['USER_FIELD_ID']] ?? [] as $id => $value) {
					$rows[] = ['ID' => $id, 'VALUE' => $value];
				}
			}
			return new CUserFieldEnumResult($rows);
		}

		public function SetEnumValues(int $fieldId, array $values): bool
		{
			foreach ($values as $value) {
				$currentValues = self::$values[$fieldId] ?? [];
				$nextId = $currentValues === [] ? 1 : max(array_keys($currentValues)) + 1;
				self::$values[$fieldId][$nextId] = $value['VALUE'];
			}
			return true;
		}
	}

	if (!class_exists('Memcache')) {
		class Memcache
		{
		}
	}

	class CUser
	{
	}

	class CMain
	{
		public bool $showIncludeAreas = true;
		public string $currentPage = '/';
		public array $lastCurPageParam = [];
		public array $includedComponents = [];
		public array $includedFiles = [];

		public function GetShowIncludeAreas(): bool
		{
			return $this->showIncludeAreas;
		}

		public function GetPublicShowMode(): string
		{
			return 'view';
		}

		public function SetCurPage(string $page): void
		{
			$this->currentPage = $page;
		}

		public function GetCurPageParam(string $addParams, array $deleteParams, bool $getIndexPage): string
		{
			$this->lastCurPageParam = compact('addParams', 'deleteParams', 'getIndexPage');
			return $this->currentPage . ($addParams === '' ? '' : '?' . $addParams);
		}

		public function IncludeComponent(string $name, string $template, array $params, mixed $component = null, array $options = [], bool $returnResult = false): mixed
		{
			$this->includedComponents[] = compact('name', 'template', 'params', 'component', 'options', 'returnResult');
			echo "component:{$name}";
			return ['name' => $name, 'params' => $params];
		}

		public function IncludeFile(string $path, array $params = [], array $functionParams = []): void
		{
			$this->includedFiles[] = compact('path', 'params', 'functionParams');
			echo "include:{$path}";
		}
	}

	class CUserTypeManager
	{
	}
}
