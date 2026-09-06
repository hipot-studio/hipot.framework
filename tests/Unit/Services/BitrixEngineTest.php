<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\ConnectionPool;
use Bitrix\Main\Context;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\LocalStorage\SessionLocalStorageManager;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Request;
use Bitrix\Main\Session\ArraySession;
use Bitrix\Main\SiteTable;
use Hipot\Services\BitrixEngine;

beforeEach(function (): void {
	BitrixEngine::resetInstance();
	CurrentUser::set(new CurrentUser());
});

afterEach(function (): void {
	unset($GLOBALS['USER'], $GLOBALS['APPLICATION'], $GLOBALS['USER_FIELD_MANAGER']);
});

it('initializes the facade with services from Bitrix', function (): void {
	$request = new Request();
	$taggedCache = new TaggedCache();
	$session = new ArraySession();
	$localStorage = new SessionLocalStorageManager();
	$defaultConnection = new Connection('default');
	$connectionPool = new ConnectionPool();
	$app = new Application(
		new Context($request, 's1'),
		$taggedCache,
		$session,
		$localStorage,
		$connectionPool,
	);
	$user = new CurrentUser(new CUser());

	Application::setInstance($app);
	Application::setConnection($defaultConnection);
	CurrentUser::set($user);

	$engine = BitrixEngine::getInstance();

	expect($engine->app)->toBe($app)
		->and($engine->request)->toBe($request)
		->and($engine->user)->toBe($user)
		->and($engine->cache)->toBeInstanceOf(Cache::class)
		->and($engine->cache->options)->toBe(['actual_data' => false])
		->and($engine->taggedCache)->toBe($taggedCache)
		->and($engine->asset)->toBe(Asset::getInstance())
		->and($engine->session)->toBe($session)
		->and($engine->serviceLocator)->toBe(ServiceLocator::getInstance())
		->and($engine->connection)->toBe($defaultConnection)
		->and($engine->eventManager)->toBe(EventManager::getInstance())
		->and($engine->sessionLocalStorageManager)->toBe($localStorage);
});

it('returns the current user only when a CUser is available', function (): void {
	CurrentUser::set(new CurrentUser());
	expect(BitrixEngine::getCurrentUser())->toBeNull();

	$currentUser = new CurrentUser(new CUser());
	CurrentUser::set($currentUser);

	expect(BitrixEngine::getCurrentUser())->toBe($currentUser);
});

it('returns a registered service and null for an unknown service', function (): void {
	$serviceLocator = new ServiceLocator();
	$service = new stdClass();
	$serviceLocator->set('example', $service);
	$engine = new BitrixEngine(serviceLocator: $serviceLocator);

	expect($engine->getService('example'))->toBe($service)
		->and($engine->getService('missing'))->toBeNull();
});

it('returns a named connection from the application pool', function (): void {
	$connection = new Connection('analytics');
	$connectionPool = new ConnectionPool();
	$connectionPool->addConnection('analytics', $connection);
	$app = new Application(
		new Context(new Request(), 's1'),
		new TaggedCache(),
		new ArraySession(),
		new SessionLocalStorageManager(),
		$connectionPool,
	);
	$engine = new BitrixEngine(app: $app);

	expect($engine->getConnection('analytics'))->toBe($connection);
});

it('uses the context site outside the admin section', function (): void {
	$request = new Request();
	$app = new Application(
		new Context($request, 'public-site'),
		new TaggedCache(),
		new ArraySession(),
		new SessionLocalStorageManager(),
		new ConnectionPool(),
	);
	$engine = new BitrixEngine(app: $app, request: $request);

	expect($engine->getSiteId())->toBe('public-site');
});

it('resolves the site by host and directory in the admin section', function (): void {
	$request = new Request(true, 'admin.example.test', '/catalog/');
	$app = new Application(
		new Context($request, 'context-site'),
		new TaggedCache(),
		new ArraySession(),
		new SessionLocalStorageManager(),
		new ConnectionPool(),
	);
	SiteTable::$siteId = 'admin-site';
	$engine = new BitrixEngine(app: $app, request: $request);

	expect($engine->getSiteId())->toBe('admin-site')
		->and(SiteTable::$lastLookup)->toBe([
			'host' => 'admin.example.test',
			'directory' => '/catalog/',
		]);
});

it('initializes and reuses legacy global objects', function (): void {
	$user = BitrixEngine::getCurrentUserD0();
	$app = BitrixEngine::getAppD0();

	expect($user)->toBeInstanceOf(CUser::class)
		->and(BitrixEngine::getCurrentUserD0())->toBe($user)
		->and($app)->toBeInstanceOf(CMain::class)
		->and(BitrixEngine::getAppD0())->toBe($app);
});

it('returns the global user field manager', function (): void {
	$manager = new CUserTypeManager();
	$GLOBALS['USER_FIELD_MANAGER'] = $manager;

	expect(BitrixEngine::getUserFieldManager())->toBe($manager);
});
