<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 21.03.2023 18:24
 * @version pre 1.0
 */
namespace Hipot\Services;

use Bitrix\Main\Web\Json;
use Hipot\Utils\UUtils;

/**
 * yum install ffmpeg
 */
class FfmpegExec
{
	static $IS_DEBUG = false;

	/**
	 * 1080p (HD): 1920 x 1080
	 * 720p (HD): 1280 x 720
	 * 480p (SD): 854 x 480
	 */
	public const SIZES_H = [
		'480p' => 480,
		'720p' => 720,
		'1080p' => 1080
	];

	/**
	 * set -1 in width or height to autofill
	 */
	private const RESIZE_SIZE_BY_HEIGHT_SH = 'ffmpeg -v quiet -i #SRC_FILENAME# -filter:v scale="#WIDTH#:#HEIGHT#" -c:a copy #DEST_FILENAME#';
	private const GET_SIZE_SH = 'ffprobe -v quiet -show_entries stream=width,height,codec_type -select_streams v:0 -of json #FILENAME#';

	/**
	 * @return array{'width':int, 'height':int}
	 */
	public static function getVideoSize(string $filename): array
	{
		$sizes = ['width' => 0, 'height' => 0];

		if (!is_file($filename)) {
			return $sizes;
		}

		$cmd = str_replace('#FILENAME#', $filename, self::GET_SIZE_SH);
		try {
			$cmdResult = [];
			self::runCli($cmd, $cmdResult);
			$result = Json::decode( implode('', $cmdResult) );
			$sizes = current($result['streams']);
		} catch (\Error|\Exception $e) {
			UUtils::logException($e);
		}

		return $sizes;
	}

	public static function resizeVideo(string $srcFilename, string $destFilename, array $sizes): bool
	{
		if (is_file($destFilename)) {
			return true;
		}
		if (! is_file($srcFilename)) {
			return false;
		}
		if (!mkdir($concurrentDirectory = dirname($destFilename), constant('BX_DIR_PERMISSIONS'), true) && !is_dir($concurrentDirectory)) {
			return false;
		}

		$cmd = str_replace([
			'#WIDTH#', '#HEIGHT#', '#SRC_FILENAME#', '#DEST_FILENAME#'
		], [
			$sizes['width'], $sizes['height'], $srcFilename, $destFilename
		],
			self::RESIZE_SIZE_BY_HEIGHT_SH
		);
		$cmdResult = [];
		self::runCli($cmd, $cmdResult);

		return is_file($destFilename) && filesize($destFilename) > 1024 * 1024;
	}

	private static function runCli($cmd, &$output)
	{
		@exec($cmd, $output);
		if (self::$IS_DEBUG) {
			echo d($cmd, $output);
		}
	}
}