<?php

declare(strict_types=1);

use Bitrix\Main\Loader;
use Hipot\IbAbstractLayer\Types\ValueFile;

beforeEach(function (): void {
	Loader::$documentRoot = 'D:/sites/example';
});

it('exposes the file id and paths', function (): void {
	$file = new ValueFile([
		'ID' => '42',
		'SRC' => '/upload/iblock/example/photo.jpg',
	]);

	expect($file->getId())->toBe(42)
		->and($file->getPath())->toBe('/upload/iblock/example/photo.jpg')
		->and($file->getAbsolutePath())->toBe('D:/sites/example/upload/iblock/example/photo.jpg');
});

it('returns empty values when file identity and path are absent', function (): void {
	$file = new ValueFile([]);

	expect($file->getId())->toBeNull()
		->and($file->getPath())->toBe('')
		->and($file->getAbsolutePath())->toBe('')
		->and($file->getMimeType())->toBe('')
		->and($file->isImage())->toBeFalse();
});

it('uses the stored mime type and detects images', function (): void {
	$file = new ValueFile([
		'SRC' => '/upload/photo.bin',
		'CONTENT_TYPE' => 'image/webp',
	]);

	expect($file->getMimeType())->toBe('image/webp')
		->and($file->isImage())->toBeTrue();
});

it('detects the mime type from the filename when it is not stored', function (): void {
	$file = new ValueFile([
		'SRC' => '/upload/document.pdf',
	]);

	expect($file->getMimeType())->toBe('application/pdf')
		->and($file->isImage())->toBeFalse();
});

it('exports the original non-empty fields', function (): void {
	$file = new ValueFile([
		'ID' => 42,
		'SRC' => '/upload/photo.jpg',
		'DESCRIPTION' => '',
	]);

	expect($file->toArray())->toBe([
		'ID' => 42,
		'SRC' => '/upload/photo.jpg',
	]);
});
