<?php
namespace Hipot\Services;

use Bitrix\Main\Loader;

/**
 * Сервис для создания pdf на основе https://wkhtmltopdf.org/
 */
final class PdfPageGenerator
{
	/**
	 * Директория от корня сайта с кешем pdf-файлов
	 * @var string
	 */
	private static string $pdfCacheDir = '/upload/pdf_cache';

	/**
	 * Время кеширования pdf в минутах
	 * @var int
	 */
	private static int $pdfCacheMins = 5;

	/**
	 * шаблон пути запуска генерации pdf по урлу.
	 * @var string
	 */
	private static string $wkhtmlToPdfPath = '/opt/scripts/wkhtmltopdf.sh --print-media-type --margin-left 0mm --margin-right 0mm --margin-top 0mm --margin-bottom 0mm --footer-center "#FOOTER_TEXT#" "#URL#" "#FILE_PDF_PATH#"';

	/**
	 * удаляем pdf с датой создания файла старше 10 минут
	 */
	private static function deleteOldCachedPdf(): void
	{
		foreach (glob(Loader::getDocumentRoot() . self::$pdfCacheDir . '/*.pdf') as $pdfFile) {
			if ( (int)((time() - filemtime($pdfFile)) / 60) > self::$pdfCacheMins ) {
				unlink($pdfFile);
			}
		}
	}

	private static function getFooterText(): string
	{
		return '[page]/[toPage]';
	}

	/**
	 * Выполнить команду создания pdf
	 *
	 * @param string $url
	 * @param string $fullPdfPath
	 *
	 * @internal param string $fullPdfPatch полный путь
	 */
	public static function runGenerator(string $url, string $fullPdfPath): void
	{
		unlink($fullPdfPath);
		$cmd = str_replace(['#URL#', '#FILE_PDF_PATH#', '#FOOTER_TEXT#'], [$url, $fullPdfPath, self::getFooterText()], self::$wkhtmlToPdfPath);
		/*if (IS_BETA_TESTER) {
			d($cmd);
			exit;
		}*/
		exec($cmd);
	}
}