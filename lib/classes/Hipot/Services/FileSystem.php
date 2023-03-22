<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 21.03.2023 18:27
 * @version pre 1.0
 */
namespace Hipot\Services;

use RecursiveDirectoryIterator;
use FilesystemIterator;

class FileSystem
{
	public static function getRecursiveDirIterator(string $dirPath): \Generator
	{
		$directory = new RecursiveDirectoryIterator($dirPath,
			FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS
		);
		$filter = new class ($directory) extends \RecursiveFilterIterator
		{
			public function accept(): bool
			{
				/* @var $file \SplFileInfo */
				$file = $this->current();
				return $file->isReadable();
			}
		};
		foreach (new \RecursiveIteratorIterator($filter) as $file) {
			yield $file;
		}
	}
}