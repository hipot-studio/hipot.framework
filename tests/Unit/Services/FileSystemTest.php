<?php

declare(strict_types=1);

use Hipot\Services\FileSystem;

function removeFileSystemTestDirectory(string $directory): void
{
	if (!is_dir($directory)) {
		return;
	}

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
		RecursiveIteratorIterator::CHILD_FIRST,
	);

	foreach ($iterator as $item) {
		if ($item->isDir()) {
			rmdir($item->getPathname());
		} else {
			unlink($item->getPathname());
		}
	}

	rmdir($directory);
}

beforeEach(function (): void {
	$this->tempDirectory = sys_get_temp_dir()
		. DIRECTORY_SEPARATOR
		. 'hipot-filesystem-'
		. bin2hex(random_bytes(8));

	mkdir($this->tempDirectory, 0777, true);
});

afterEach(function (): void {
	removeFileSystemTestDirectory($this->tempDirectory);
});

it('recursively yields files from all nested directories', function (): void {
	$nestedDirectory = $this->tempDirectory . DIRECTORY_SEPARATOR . 'nested';
	$deepDirectory = $nestedDirectory . DIRECTORY_SEPARATOR . 'deep';
	mkdir($deepDirectory, 0777, true);

	file_put_contents($this->tempDirectory . DIRECTORY_SEPARATOR . 'root.txt', 'root');
	file_put_contents($nestedDirectory . DIRECTORY_SEPARATOR . 'child.txt', 'child');
	file_put_contents($deepDirectory . DIRECTORY_SEPARATOR . 'last.txt', 'last');

	$files = iterator_to_array(
		FileSystem::getRecursiveDirIterator($this->tempDirectory),
		false,
	);

	$relativePaths = array_map(
		function (SplFileInfo $file): string {
			expect($file->isFile())->toBeTrue();

			$path = substr(
				$file->getPathname(),
				strlen($this->tempDirectory) + 1,
			);

			return str_replace(DIRECTORY_SEPARATOR, '/', $path);
		},
		$files,
	);
	sort($relativePaths);

	expect($relativePaths)->toBe([
		'nested/child.txt',
		'nested/deep/last.txt',
		'root.txt',
	]);
});

it('returns no entries for an empty directory', function (): void {
	expect(iterator_to_array(
		FileSystem::getRecursiveDirIterator($this->tempDirectory),
		false,
	))->toBe([]);
});

it('throws when the root directory does not exist', function (): void {
	iterator_to_array(
		FileSystem::getRecursiveDirIterator(
			$this->tempDirectory . DIRECTORY_SEPARATOR . 'missing',
		),
		false,
	);
})->throws(UnexpectedValueException::class);

it('prepends text while preserving the original file contents', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'content.txt';
	file_put_contents($file, 'original content');

	FileSystem::filePutPrepend($file, 'prefix: ');

	expect(file_get_contents($file))->toBe('prefix: original content');
});

it('supports repeated prepend operations', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'content.txt';
	file_put_contents($file, 'body');

	FileSystem::filePutPrepend($file, 'first-');
	FileSystem::filePutPrepend($file, 'second-');

	expect(file_get_contents($file))->toBe('second-first-body');
});

it('prepends content to an empty file', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'empty.txt';
	file_put_contents($file, '');

	FileSystem::filePutPrepend($file, 'content');

	expect(file_get_contents($file))->toBe('content');
});

it('uses the byte length when prepending UTF-8 text', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'utf8.txt';
	file_put_contents($file, 'исходный текст');

	FileSystem::filePutPrepend($file, 'Привет: ');

	expect(file_get_contents($file))->toBe('Привет: исходный текст');
});

it('leaves a file unchanged when the prepended string is empty', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'content.txt';
	file_put_contents($file, 'original');

	FileSystem::filePutPrepend($file);

	expect(file_get_contents($file))->toBe('original');
});

