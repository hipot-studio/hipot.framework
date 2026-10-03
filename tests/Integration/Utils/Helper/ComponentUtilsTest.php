<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Loader;
use Bitrix\Main\Server;
use Hipot\Components\IblockList;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\Helper\ComponentUtils;
use Hipot\Utils\Helper\ContextUtils;

function componentUtilsFixture(): object
{
	return new class {
		use ContextUtils;
		use ComponentUtils;
	};
}

function withComponentUtilsRequest(string $scriptName, callable $callback): mixed
{
	$application = Application::getInstance();
	$originalContext = $application->getContext();
	$server = new Server([
		'HTTP_HOST' => 'example.test',
		'REQUEST_METHOD' => 'GET',
		'REQUEST_URI' => $scriptName,
		'QUERY_STRING' => '',
		'SCRIPT_NAME' => $scriptName,
	]);
	$request = new HttpRequest($server, [], [], [], []);
	$context = new Context($application);
	$context->initialize($request, new HttpResponse(), $server);
	$application->setContext($context);
	BitrixEngine::resetInstance();

	try {
		return $callback();
	} finally {
		$application->setContext($originalContext);
		BitrixEngine::resetInstance();
	}
}

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function (): void {
	BitrixEngine::resetInstance();
});

afterEach(function (): void {
	BitrixEngine::resetInstance();
});

it('detects installed components and rejects unknown ones', function (): void {
	expect(componentUtilsFixture()::isComponentExists('hipot:iblock.list'))->toBeTrue()
		->and(componentUtilsFixture()::isComponentExists('hipot:missing.component'))->toBeFalse();
});

it('loads a Hipot component class from its namespace name', function (): void {
	$className = componentUtilsFixture()::loadHipotComponentClass(IblockList::class);

	expect($className)->toBe(IblockList::class)
		->and(class_exists(IblockList::class, false))->toBeTrue()
		->and(componentUtilsFixture()::loadHipotComponentClass('Vendor\\Components\\Unknown'))->toBeNull()
		->and(componentUtilsFixture()::loadHipotComponentClass('Hipot\\Components\\MissingComponent'))->toBeNull();
});

it('captures component output and returns the component result', function (): void {
	$componentResult = null;
	$output = componentUtilsFixture()::getComponent(
		'hipot:iblock.list',
		'edit_example',
		[
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'ORDER' => ['ID' => 'ASC'],
			'FILTER' => ['ID' => PHP_INT_MAX],
			'SELECT' => ['ID', 'NAME'],
			'GET_PROPERTY' => 'N',
			'NTOPCOUNT' => 1,
			'PAGESIZE' => 0,
			'NAV_TEMPLATE' => '',
			'NAV_SHOW_ALWAYS' => 'N',
			'NAV_SHOW_ALL' => 'N',
			'NAV_PAGEWINDOW' => 3,
			'SET_404' => 'N',
			'ALWAYS_INCLUDE_TEMPLATE' => 'Y',
			'SELECT_CHAINS' => 'N',
			'SELECT_CHAINS_DEPTH' => 3,
			'CACHE_TYPE' => 'N',
			'CACHE_TIME' => 0,
			'CACHE_GROUPS' => 'N',
		],
		$componentResult,
	);

	expect(trim($output))->toBe('')
		->and($componentResult)->toBeArray()
		->and($componentResult['CNT_ITEMS'])->toBe(0)
		->and($componentResult['ITEMS'])->toBe([]);
});

it('builds the edit-area id attribute through a Bitrix template', function (): void {
	$component = new CBitrixComponent();
	$component->initComponent('hipot:iblock.list');
	$template = new CBitrixComponentTemplate();
	$template->__component = $component;
	$entity = ['ID' => 42];

	expect(componentUtilsFixture()::getEditAreaAttrId($template, $entity))
		->toBe('id="' . $component->GetEditAreaId(42) . '"');
});

it('extracts variables from the active SEF request path', function (): void {
	$variables = withComponentUtilsRequest(
		'/catalog/section/42/index.php',
		static fn (): array => componentUtilsFixture()::getSefComponentVariables([
			'SEF_FOLDER' => '/catalog/',
			'SEF_URL_TEMPLATES' => [
				'section' => 'section/#SECTION_ID#/index.php',
			],
			'VARIABLE_ALIASES' => [],
		], ['SECTION_ID']),
	);

	expect($variables)->toMatchArray(['SECTION_ID' => '42']);
});

it('returns no include areas for a missing directory', function (): void {
	expect(componentUtilsFixture()::getIncludeAreas('/missing-' . bin2hex(random_bytes(8))))->toBe([]);
});
