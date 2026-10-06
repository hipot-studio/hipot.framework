<?php

declare(strict_types=1);

use Bitrix\Main\IO\Directory;
use Bitrix\Main\Loader;
use Hipot\Utils\Img;

function imgIntegrationResizer(string $tag, array $config = []): Img
{
	return Img::getInstance(array_merge([
		'tag' => $tag,
		'decodeToFormat' => 'jpg',
		'saveAlpha' => false,
		'jpgQuality' => 90,
	], $config));
}

function imgIntegrationBrightnessCallback(Img $img): void
{
	$GLOBALS['imgIntegrationCallbackCalls']++;
	$img->getProcessEngine()->brightness(1);
}

$imgIntegrationFixtures = [];
$imgIntegrationCacheTag = 'hipot_img_integration_' . bin2hex(random_bytes(6));

beforeAll(function () use (&$imgIntegrationFixtures): void {
	Loader::requireModule('iblock');

	$elementIds = [];
	$idsResult = CIBlockElement::GetList(
		['ID' => 'ASC'],
		[
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'ACTIVE' => 'Y',
			'!DETAIL_PICTURE' => false,
			'!PROPERTY_MORE_PHOTO' => false,
		],
		false,
		['nTopCount' => 2],
		['ID'],
	);
	while ($element = $idsResult->Fetch()) {
		$elementIds[] = (int)$element['ID'];
	}

	if (count($elementIds) < 2) {
		throw new RuntimeException('The catalog must contain two active products with DETAIL_PICTURE and MORE_PHOTO.');
	}

	$elementsById = [];
	$elementsResult = CIBlockElement::GetList(
		[],
		[
			'IBLOCK_ID' => CATALOG_IBLOCK_ID,
			'ID' => $elementIds,
		],
		false,
		false,
		['ID', 'NAME', 'DETAIL_PICTURE'],
	);
	while ($element = $elementsResult->Fetch()) {
		$elementsById[(int)$element['ID']] = $element;
	}

	$propertiesById = [];
	CIBlockElement::GetPropertyValuesArray(
		$propertiesById,
		CATALOG_IBLOCK_ID,
		['ID' => $elementIds],
		['CODE' => ['MORE_PHOTO']],
	);

	foreach ($elementIds as $elementId) {
		$detailPictureId = (int)($elementsById[$elementId]['DETAIL_PICTURE'] ?? 0);
		$morePhotoIds = array_values(array_filter(array_map(
			'intval',
			(array)($propertiesById[$elementId]['MORE_PHOTO']['VALUE'] ?? []),
		)));
		$morePhotoId = $morePhotoIds[0] ?? 0;
		$detailPicture = CFile::GetFileArray($detailPictureId);
		$morePhoto = CFile::GetFileArray($morePhotoId);

		if (!is_array($detailPicture) || !is_array($morePhoto)) {
			throw new RuntimeException("Unable to load catalog images for element {$elementId}.");
		}

		foreach ([$detailPicture, $morePhoto] as $file) {
			$filePath = Loader::getDocumentRoot() . $file['SRC'];
			if (!is_file($filePath)) {
				throw new RuntimeException("Catalog image does not exist: {$filePath}.");
			}
		}

		$imgIntegrationFixtures[] = [
			'ELEMENT_ID' => $elementId,
			'DETAIL_PICTURE' => $detailPicture,
			'MORE_PHOTO' => $morePhoto,
		];
	}
});

beforeEach(function (): void {
	$GLOBALS['imgIntegrationCallbackCalls'] = 0;
	Img::getInstance()->restoreResizeParams();
});

afterEach(function (): void {
	Img::getInstance()->restoreResizeParams();
	unset($GLOBALS['imgIntegrationCallbackCalls']);
});

afterAll(function () use (&$imgIntegrationCacheTag): void {
	$cacheDirectory = Loader::getDocumentRoot() . '/upload/himg_cache/' . $imgIntegrationCacheTag;
	if (Directory::isDirectoryExists($cacheDirectory)) {
		Directory::deleteDirectory($cacheDirectory);
	}
});

it('resizes a Bitrix catalog file by ID and reuses the cached result', function () use (
	&$imgIntegrationFixtures,
	&$imgIntegrationCacheTag,
): void {
	$fileId = (int)$imgIntegrationFixtures[0]['DETAIL_PICTURE']['ID'];
	$resizer = imgIntegrationResizer($imgIntegrationCacheTag, ['decodeToFormat' => 'webp']);

	$first = $resizer->doResize(
		$fileId,
		120,
		80,
		Img::M_CROP,
		true,
		'imgIntegrationBrightnessCallback',
	);
	$second = $resizer->doResize(
		$fileId,
		120,
		80,
		Img::M_CROP,
		true,
		'imgIntegrationBrightnessCallback',
	);

	expect($first)->toMatchArray([
		'ID' => $fileId,
		'WIDTH' => 120,
		'HEIGHT' => 80,
		'width' => 120,
		'height' => 80,
	])
		->and($first['SRC'])->toBe($first['src'])->toEndWith('.webp')
		->and($second)->toBe($first)
		->and($GLOBALS['imgIntegrationCallbackCalls'])->toBe(1)
		->and(is_file(Loader::getDocumentRoot() . $first['SRC']))->toBeTrue();
});

it('creates the requested fixed canvas for each transformation method', function (string $method) use (
	&$imgIntegrationFixtures,
	&$imgIntegrationCacheTag,
): void {
	$result = imgIntegrationResizer($imgIntegrationCacheTag)->doResize(
		(int)$imgIntegrationFixtures[0]['DETAIL_PICTURE']['ID'],
		96,
		64,
		$method,
		true,
	);

	expect($result['WIDTH'])->toBe(96)
		->and($result['HEIGHT'])->toBe(64)
		->and(getimagesize(Loader::getDocumentRoot() . $result['SRC']))->toMatchArray([96, 64]);
})->with([
	'center crop' => Img::M_CROP,
	'top crop' => Img::M_CROP_TOP,
	'full canvas' => Img::M_FULL,
	'full canvas without upsize' => Img::M_FULL_S,
	'stretch' => Img::M_STRETCH,
]);

it('accepts Bitrix file arrays and relative and absolute paths for proportional resize', function () use (
	&$imgIntegrationFixtures,
	&$imgIntegrationCacheTag,
): void {
	$file = $imgIntegrationFixtures[0]['MORE_PHOTO'];
	$relativePath = (string)$file['SRC'];
	$absolutePath = Loader::getDocumentRoot() . $relativePath;

	$fromArray = imgIntegrationResizer($imgIntegrationCacheTag)
		->doResize($file, 70, null, Img::M_PROPORTIONAL, true);
	$fromRelativePath = imgIntegrationResizer($imgIntegrationCacheTag)
		->doResize($relativePath, 80, null, Img::M_PROPORTIONAL, true);
	$fromAbsolutePath = imgIntegrationResizer($imgIntegrationCacheTag)
		->doResize($absolutePath, 90, null, Img::M_PROPORTIONAL, true);

	expect($fromArray['WIDTH'])->toBe(70)
		->and($fromArray['HEIGHT'])->toBeGreaterThan(0)
		->and($fromRelativePath['WIDTH'])->toBe(80)
		->and($fromRelativePath['HEIGHT'])->toBeGreaterThan(0)
		->and($fromAbsolutePath['WIDTH'])->toBe(90)
		->and($fromAbsolutePath['HEIGHT'])->toBeGreaterThan(0);
});

it('uses a MORE_PHOTO image as an overlay', function () use (
	&$imgIntegrationFixtures,
	&$imgIntegrationCacheTag,
): void {
	$sourceId = (int)$imgIntegrationFixtures[0]['DETAIL_PICTURE']['ID'];
	$overlayPath = (string)$imgIntegrationFixtures[1]['MORE_PHOTO']['SRC'];
	$result = imgIntegrationResizer($imgIntegrationCacheTag)->doResizeOverlay(
		$sourceId,
		$overlayPath,
		'bottom-right',
		140,
		100,
		Img::M_CROP,
		true,
	);

	expect($result['WIDTH'])->toBe(140)
		->and($result['HEIGHT'])->toBe(100)
		->and(is_file(Loader::getDocumentRoot() . $result['SRC']))->toBeTrue();
});

it('resizes images inside HTML and optionally links to the original', function () use (
	&$imgIntegrationFixtures,
	&$imgIntegrationCacheTag,
): void {
	$source = (string)$imgIntegrationFixtures[1]['DETAIL_PICTURE']['SRC'];
	$html = '<p><img class="product" width="999" height="888" src="' . $source . '" alt="Product"></p>';
	$result = Img::resizeImagesInHtml(
		$html,
		static fn (string $src): array => imgIntegrationResizer($imgIntegrationCacheTag)->doResize(
			$src,
			64,
			40,
			Img::M_STRETCH,
			true,
		),
		true,
	);

	expect($result)->toContain(
		'<a href="' . $source . '">',
		'width="64" height="40"',
		'/upload/himg_cache/' . $imgIntegrationCacheTag . '/',
		'alt="Product"',
	)
		->not->toContain('width="999"', 'height="888"');
});
