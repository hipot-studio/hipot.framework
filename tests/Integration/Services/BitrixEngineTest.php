<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\DB\Connection;
use Bitrix\Main\DI\ServiceLocator;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\EventManager;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Request;
use Bitrix\Main\Session\SessionInterface;
use Bitrix\Main\SiteTable;
use Hipot\Services\BitrixEngine;

beforeEach(function (): void {
	BitrixEngine::resetInstance();
});

it('initializes dependencies from the running Bitrix kernel', function (): void {
	$app = Application::getInstance();
	$engine = BitrixEngine::getInstance();

	expect($engine->app)->toBe($app)
		->and($engine->request)->toBe($app->getContext()->getRequest())
		->and($engine->request)->toBeInstanceOf(Request::class)
		->and($engine->user)->toBeInstanceOf(CurrentUser::class)
		->and($engine->user->getId())->toBe(BitrixEngine::getCurrentUser()?->getId())
		->and($engine->cache)->toBeInstanceOf(Cache::class)
		->and($engine->taggedCache)->toBe($app->getTaggedCache())
		->and($engine->taggedCache)->toBeInstanceOf(TaggedCache::class)
		->and($engine->asset)->toBe(Asset::getInstance())
		->and($engine->session)->toBe($app->getSession())
		->and($engine->session)->toBeInstanceOf(SessionInterface::class)
		->and($engine->serviceLocator)->toBe(ServiceLocator::getInstance())
		->and($engine->connection)->toBe(Application::getConnection())
		->and($engine->connection)->toBeInstanceOf(Connection::class)
		->and($engine->eventManager)->toBe(EventManager::getInstance())
		->and($engine->sessionLocalStorageManager)->toBe($app->getSessionLocalStorageManager());
});

it('uses the real connection pool and request context', function (): void {
	$engine = BitrixEngine::getInstance();
	$expectedSiteId = $engine->request->isAdminSection()
		? SiteTable::wakeUpObject(SiteTable::getByDomain(
			$engine->request->getHttpHost(),
			$engine->request->getRequestedPageDirectory(),
		))->getLid()
		: $engine->app->getContext()->getSite();

	expect($engine->getConnection())->toBe($engine->app->getConnectionPool()->getConnection())
		->and($engine->getSiteId())->toBe($expectedSiteId);
});

it('exposes the legacy Bitrix globals initialized by the prolog', function (): void {
	expect(BitrixEngine::getCurrentUserD0())->toBe($GLOBALS['USER'])
		->and(BitrixEngine::getAppD0())->toBe($GLOBALS['APPLICATION'])
		->and(BitrixEngine::getUserFieldManager())->toBe($GLOBALS['USER_FIELD_MANAGER']);
});
