<?php

declare(strict_types=1);

use Bitrix\Iblock\SectionTable;
use Bitrix\Main\Loader;
use Hipot\Components\IblockSection;

$fixtureElementIds = [];
$fixtureSectionIds = [];

function includeIblockSectionForIntegration(array $params): array
{
	/** @var CMain $APPLICATION */
	global $APPLICATION;

	ob_start();
	try {
		$result = $APPLICATION->IncludeComponent(
			'hipot:iblock.section',
			'edit_example',
			array_merge([
				'IBLOCK_ID' => CATALOG_IBLOCK_ID,
				'ORDER' => ['SORT' => 'ASC'],
				'FILTER' => [],
				'SELECT' => [],
				'ID_FIRST_QUERY' => 'N',
				'SELECTED_SECTION_ID' => 0,
				'SELECTED_SECTION_CODE' => '',
				'SECTION_CODE_PATH' => 'N',
				'SELECT_COUNT' => 'N',
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
			], $params),
			false,
			['HIDE_ICONS' => 'Y'],
			true,
		);
	} finally {
		$rendered = (string)ob_get_clean();
	}

	return [$result, $rendered];
}

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

it('hydrates catalog sections by ids and keeps the first query order', function (): void {
	$sections = SectionTable::query()
		->setSelect(['ID', 'NAME', 'CODE'])
		->where('IBLOCK_ID', CATALOG_IBLOCK_ID)
		->where('ACTIVE', 'Y')
		->where('DEPTH_LEVEL', 1)
		->setOrder(['NAME' => 'DESC'])
		->setLimit(3)
		->fetchAll();
	expect($sections)->toHaveCount(3);

	[$result, $rendered] = includeIblockSectionForIntegration([
		'ORDER' => ['NAME' => 'DESC'],
		'FILTER' => ['ID' => array_column($sections, 'ID')],
		'SELECT' => ['NAME', 'CODE'],
		'ID_FIRST_QUERY' => 'Y',
		'SELECTED_SECTION_ID' => (int)$sections[0]['ID'],
	]);

	expect((int)$result['CUR_SECTION']['ID'])->toBe((int)$sections[0]['ID'])
		->and($result['CUR_SECTION']['CODE'])->toBe($sections[0]['CODE'])
		->and($rendered)->toContain($sections[0]['NAME'], $sections[1]['NAME'], $sections[2]['NAME'])
		->and(strpos($rendered, $sections[0]['NAME']))->toBeLessThan(strpos($rendered, $sections[1]['NAME']))
		->and(strpos($rendered, $sections[1]['NAME']))->toBeLessThan(strpos($rendered, $sections[2]['NAME']));
});

it('selects nested catalog sections by their parent section id', function (): void {
	$sections = SectionTable::query()
		->setSelect(['ID', 'NAME', 'LEFT_MARGIN', 'RIGHT_MARGIN', 'DEPTH_LEVEL'])
		->where('IBLOCK_ID', CATALOG_IBLOCK_ID)
		->where('ACTIVE', 'Y')
		->setOrder(['LEFT_MARGIN' => 'ASC'])
		->fetchAll();

	$parent = null;
	$descendants = [];
	foreach ($sections as $candidate) {
		$candidateDescendants = array_values(array_filter(
			$sections,
			static fn(array $section): bool => (int)$section['LEFT_MARGIN'] > (int)$candidate['LEFT_MARGIN']
				&& (int)$section['RIGHT_MARGIN'] < (int)$candidate['RIGHT_MARGIN']
				&& (int)$section['DEPTH_LEVEL'] > (int)$candidate['DEPTH_LEVEL'],
		));
		if (count($candidateDescendants) >= 2) {
			$parent = $candidate;
			$descendants = $candidateDescendants;
			break;
		}
	}
	expect($parent)->toBeArray()
		->and($descendants)->not->toBeEmpty();

	[$result, $rendered] = includeIblockSectionForIntegration([
		'ORDER' => ['LEFT_MARGIN' => 'ASC'],
		'FILTER' => ['SECTION_ID' => (int)$parent['ID']],
		'SELECT' => ['ID', 'NAME', 'LEFT_MARGIN'],
		'ID_FIRST_QUERY' => 'Y',
		'SELECTED_SECTION_ID' => (int)$descendants[0]['ID'],
	]);

	expect((int)$result['CUR_SECTION']['ID'])->toBe((int)$descendants[0]['ID'])
		->and($rendered)->not->toContain($parent['NAME']);
	foreach ($descendants as $descendant) {
		expect($rendered)->toContain($descendant['NAME']);
	}
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
