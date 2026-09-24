<?php

declare(strict_types=1);

use Bitrix\Main\Data\Cache;
use Bitrix\Main\Loader;

$fixtureCacheDirs = [];
$fixtureSectionIds = [];

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function () use (&$fixtureCacheDirs, &$fixtureSectionIds): void {
	$fixtureCacheDirs = [];
	$fixtureSectionIds = [];
});

afterEach(function () use (&$fixtureCacheDirs, &$fixtureSectionIds): void {
	foreach ($fixtureCacheDirs as $cacheDir) {
		Cache::createInstance()->cleanDir($cacheDir);
	}
	foreach (array_reverse($fixtureSectionIds) as $sectionId) {
		CIBlockSection::Delete($sectionId);
	}

	$fixtureCacheDirs = [];
	$fixtureSectionIds = [];
});

it('builds and caches an iblock menu from active catalog sections', function () use (
	&$fixtureCacheDirs,
	&$fixtureSectionIds,
): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$suffix = bin2hex(random_bytes(6));
	$section = new CIBlockSection();
	$sectionFixtures = [
		'parent' => [
			'ACTIVE' => 'Y',
			'NAME' => "A menu parent {$suffix}",
			'CODE' => "menu-parent-{$suffix}",
			'IBLOCK_SECTION_ID' => 0,
		],
		'child' => [
			'ACTIVE' => 'Y',
			'NAME' => "B menu child {$suffix}",
			'CODE' => "menu-child-{$suffix}",
			'IBLOCK_SECTION_ID' => null,
		],
		'inactive' => [
			'ACTIVE' => 'N',
			'NAME' => "C inactive menu section {$suffix}",
			'CODE' => "menu-inactive-{$suffix}",
			'IBLOCK_SECTION_ID' => null,
		],
	];
	$sectionIds = [];

	foreach ($sectionFixtures as $key => $fixture) {
		$parentSectionId = $fixture['IBLOCK_SECTION_ID'];
		if ($parentSectionId === null) {
			$parentSectionId = $sectionIds['parent'];
		}

		$sectionId = $section->Add([
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'IBLOCK_SECTION_ID' => $parentSectionId,
			'ACTIVE' => $fixture['ACTIVE'],
			'NAME' => $fixture['NAME'],
			'CODE' => $fixture['CODE'],
		]);

		expect($sectionId)->not->toBeFalse($section->LAST_ERROR);
		$sectionIds[$key] = (int)$sectionId;
		$fixtureSectionIds[] = (int)$sectionId;
	}

	$cacheTag = "integration_iblock_menu_ext_{$suffix}";
	$fixtureCacheDirs[] = "php/{$cacheTag}/";
	$componentParams = [
		'TYPE' => 'sections',
		'CACHE_TAG' => $cacheTag,
		'CACHE_TIME' => 3600,
		'IBLOCK_ID' => CATALOG_IBLOCK_ID,
		'ORDER' => ['NAME' => 'DESC'],
		'FILTER' => ['ID' => array_values($sectionIds)],
		'SELECT' => ['SORT'],
		'ADDON_URL_TO_SELECT_ITEM' => '/catalog/#SECTION_CODE#/',
	];

	$menu = $APPLICATION->IncludeComponent(
		'hipot:iblock.menu_ext',
		'',
		$componentParams,
		false,
		['HIDE_ICONS' => 'Y'],
	);
	$updatedName = "Changed menu child {$suffix}";
	expect($section->Update($sectionIds['child'], ['NAME' => $updatedName]))
		->toBeTrue($section->LAST_ERROR);

	$cachedMenu = $APPLICATION->IncludeComponent(
		'hipot:iblock.menu_ext',
		'',
		$componentParams,
		false,
		['HIDE_ICONS' => 'Y'],
	);

	expect($menu)
		->toBeArray()
		->toHaveCount(2)
		->and($menu[0][0])->toBe("B menu child {$suffix}")
		->and($menu[0][1])->toBe($menu[0][3]['SECTION_PAGE_URL'])
		->and($menu[0][2])->toBe(["/catalog/menu-child-{$suffix}/"])
		->and((int)$menu[0][3]['ID'])->toBe($sectionIds['child'])
		->and($menu[0][3]['CODE'])->toBe("menu-child-{$suffix}")
		->and((int)$menu[0][3]['DEPTH_LEVEL'])->toBe(2)
		->and($menu[1][0])->toBe("A menu parent {$suffix}")
		->and($menu[1][1])->toBe($menu[1][3]['SECTION_PAGE_URL'])
		->and($menu[1][2])->toBe(["/catalog/menu-parent-{$suffix}/"])
		->and((int)$menu[1][3]['ID'])->toBe($sectionIds['parent'])
		->and($menu[1][3]['CODE'])->toBe("menu-parent-{$suffix}")
		->and((int)$menu[1][3]['DEPTH_LEVEL'])->toBe(1)
		->and(array_column($menu, 0))->not->toContain("C inactive menu section {$suffix}")
		->and($cachedMenu)->toBe($menu)
		->and(array_column($cachedMenu, 0))->not->toContain($updatedName);
});
