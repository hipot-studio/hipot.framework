<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\Components\IblockSection;

$fixtureElementIds = [];
$fixtureSectionIds = [];

beforeAll(function (): void {
	Loader::requireModule('iblock');
});

beforeEach(function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
	$fixtureElementIds = [];
	$fixtureSectionIds = [];
});

afterEach(function () use (&$fixtureElementIds, &$fixtureSectionIds): void {
	foreach (array_reverse($fixtureElementIds) as $elementId) {
		CIBlockElement::Delete($elementId);
	}
	foreach (array_reverse($fixtureSectionIds) as $sectionId) {
		CIBlockSection::Delete($sectionId);
	}

	$fixtureElementIds = [];
	$fixtureSectionIds = [];
});

it('selects active iblock sections and prepares the current section', function () use (
	&$fixtureElementIds,
	&$fixtureSectionIds,
): void {
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	$suffix = bin2hex(random_bytes(6));
	$section = new CIBlockSection();
	$sectionFixtures = [
		'a' => ['ACTIVE' => 'Y', 'NAME' => "A component section {$suffix}"],
		'b' => ['ACTIVE' => 'Y', 'NAME' => "B component section {$suffix}"],
		'c' => ['ACTIVE' => 'N', 'NAME' => "C inactive section {$suffix}"],
	];
	$sectionIds = [];

	foreach ($sectionFixtures as $key => $fixture) {
		$sectionId = $section->Add([
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'ACTIVE' => $fixture['ACTIVE'],
			'NAME' => $fixture['NAME'],
			'CODE' => "iblock-section-{$key}-{$suffix}",
		]);

		expect($sectionId)->not->toBeFalse($section->LAST_ERROR);
		$sectionIds[$key] = (int)$sectionId;
		$fixtureSectionIds[] = (int)$sectionId;
	}

	$element = new CIBlockElement();
	foreach (['Y', 'N'] as $active) {
		$elementId = $element->Add([
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'IBLOCK_SECTION_ID' => $sectionIds['b'],
			'ACTIVE' => $active,
			'NAME' => "Section count {$active} {$suffix}",
			'CODE' => strtolower("section-count-{$active}-{$suffix}"),
		]);

		expect($elementId)->not->toBeFalse($element->LAST_ERROR);
		$fixtureElementIds[] = (int)$elementId;
	}

	ob_start();
	try {
		$result = $APPLICATION->IncludeComponent(
			'hipot:iblock.section',
			'edit_example',
			[
				'IBLOCK_ID' => CATALOG_IBLOCK_ID,
				'ORDER' => ['NAME' => 'DESC'],
				'FILTER' => ['ID' => array_values($sectionIds)],
				'SELECT' => [],
				'SELECTED_SECTION_ID' => 0,
				'SELECTED_SECTION_CODE' => "iblock-section-b-{$suffix}",
				'SECTION_CODE_PATH' => 'N',
				'SELECT_COUNT' => 'Y',
				'SELECT_COUNT_ELEM_FILTER' => [],
				'PAGESIZE' => 0,
				'NAV_TEMPLATE' => '',
				'NAV_SHOW_ALWAYS' => 'N',
				'NAV_SHOW_ALL' => 'N',
				'NAV_PAGEWINDOW' => 3,
				'INCLUDE_SEO' => 'N',
				'ADDON_PRE_CHAINS' => [],
				'SET_404' => 'N',
				'INCLUDE_TEMPLATE_WITH_EMPTY_ITEMS' => 'Y',
				'CACHE_TYPE' => 'N',
				'CACHE_TIME' => 0,
			],
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	$firstRenderedSection = "B component section {$suffix}";
	$secondRenderedSection = "A component section {$suffix}";

	expect(class_exists(IblockSection::class, false))
		->toBeTrue()
		->and($result)->toBeArray()
		->and((int)$result['CUR_SECTION']['ID'])->toBe($sectionIds['b'])
		->and($result['CUR_SECTION']['CODE'])->toBe("iblock-section-b-{$suffix}")
		->and($result['CUR_SECTION']['ELEMENT_CNT_FROM_ELEMS'])->toBe(1)
		->and($rendered)->toContain($firstRenderedSection, $secondRenderedSection)
		->and($rendered)->not->toContain("C inactive section {$suffix}")
		->and(strpos($rendered, $firstRenderedSection))->toBeLessThan(strpos($rendered, $secondRenderedSection));
});
