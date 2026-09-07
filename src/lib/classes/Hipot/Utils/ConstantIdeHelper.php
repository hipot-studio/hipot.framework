<?php

namespace Hipot\Utils;

use Hipot\Types\Singleton;

/**
 * Helper class to handle IDE-friendly generation of PHP constant definitions.
 */
final class ConstantIdeHelper
{
	use Singleton;
	
	private array $definedOnStart = [];
	
	private string $templateOne = '
/**
 * #DESCRIPTION#
 * @var #TYPE#
 */
define(#NAME#, #VALUE#);
';
	
	public function start(): void
	{
		$this->definedOnStart = get_defined_constants(true)['user'];
	}
	
	public function getDefined(): array
	{
		return array_diff_key(get_defined_constants(true)['user'], $this->definedOnStart);
	}
	
	public function finish(string $fileName): void
	{
		if (file_exists($fileName)) {
			return;
		}
		
		$content = '<?php' . PHP_EOL;
		foreach ($this->getDefined() as $name => $value) {
			$type = gettype($value);
			if (is_array($value)) {
				$value = serialize($value);
			}
			if (!is_numeric($value)) {
				$value = '\'' . addslashes($value) . '\'';
			}
			
			$content .= str_replace([
				'#DESCRIPTION#',
				'#NAME#',
				'#TYPE#',
				'#VALUE#',
			], [
				$name,
				'\'' . addslashes($name) . '\'',
				$type,
				$value
			], $this->templateOne);
		}
		CheckDirPath($fileName);
		file_put_contents($fileName, $content, LOCK_EX);
	}
}