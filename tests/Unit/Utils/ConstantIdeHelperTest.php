<?php

declare(strict_types=1);

use Hipot\Utils\ConstantIdeHelper;

if (!function_exists('CheckDirPath')) {
	function CheckDirPath(string $path): bool
	{
		$directory = dirname($path);

		return is_dir($directory) || mkdir($directory, 0777, true);
	}
}

function removeConstantIdeHelperTestDirectory(string $directory): void
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
	ConstantIdeHelper::resetInstance();
	$this->tempDirectory = sys_get_temp_dir()
		. DIRECTORY_SEPARATOR
		. 'hipot-constant-ide-helper-'
		. bin2hex(random_bytes(8));
});

afterEach(function (): void {
	ConstantIdeHelper::resetInstance();
	removeConstantIdeHelperTestDirectory($this->tempDirectory);
});

it('returns only user constants defined after start', function (): void {
	$definedBeforeStart = 'HIPOT_IDE_BEFORE_' . strtoupper(bin2hex(random_bytes(6)));
	$definedAfterStart = 'HIPOT_IDE_AFTER_' . strtoupper(bin2hex(random_bytes(6)));
	define($definedBeforeStart, 'old value');

	$helper = ConstantIdeHelper::getInstance();
	$helper->start();
	define($definedAfterStart, 42);

	expect($helper->getDefined())
		->toHaveKey($definedAfterStart, 42)
		->not->toHaveKey($definedBeforeStart);
});

it('creates an IDE helper file in a missing directory', function (): void {
	$integerName = 'HIPOT_IDE_INTEGER_' . strtoupper(bin2hex(random_bytes(6)));
	$stringName = 'HIPOT_IDE_STRING_' . strtoupper(bin2hex(random_bytes(6)));
	$file = $this->tempDirectory
		. DIRECTORY_SEPARATOR
		. 'nested'
		. DIRECTORY_SEPARATOR
		. 'constants.php';

	$helper = ConstantIdeHelper::getInstance();
	$helper->start();
	define($integerName, 42);
	define($stringName, "O'Reilly\\docs");
	$helper->finish($file);

	$content = file_get_contents($file);
	expect($content)->toStartWith('<?php' . PHP_EOL)
		->and($content)->toContain(
			' * ' . $integerName,
			' * @var integer',
			"define('{$integerName}', 42);",
			' * ' . $stringName,
			' * @var string',
			"define('{$stringName}', 'O\\'Reilly\\\\docs');",
		);
});

it('keeps an existing cache file unchanged', function (): void {
	$file = $this->tempDirectory . DIRECTORY_SEPARATOR . 'constants.php';
	mkdir($this->tempDirectory, 0777, true);
	file_put_contents($file, '<?php // existing cache');
	$constantName = 'HIPOT_IDE_EXISTING_' . strtoupper(bin2hex(random_bytes(6)));

	$helper = ConstantIdeHelper::getInstance();
	$helper->start();
	define($constantName, 'new value');
	$helper->finish($file);

	expect(file_get_contents($file))->toBe('<?php // existing cache');
});
