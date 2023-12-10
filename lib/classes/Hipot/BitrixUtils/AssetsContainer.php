<?php

namespace Hipot\BitrixUtils;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;

final class AssetsContainer
{
	public const CSS = 1;
	public const CSS_INLINE = 1;
	public const CSS_DEFER = 2;

	private static array $CSS = [];
	private static array $CSS_INLINE = [];
	private static array $CSS_DEFER = [];

	public static function addCss(string $path, int $type = self::CSS): void
	{
		match ($type) {
			self::CSS_INLINE        => self::$CSS_INLINE[] = $path,
			self::CSS_DEFER         => self::$CSS_DEFER[] = $path,
			default                 => self::$CSS[] = $path,
		};
	}

	public static function onEpilogSendAssets(): void
	{
		if (Application::getInstance()?->getContext()?->getRequest()->isAdminSection()
			|| Application::getInstance()?->getContext()?->getRequest()->isAjaxRequest()
		) {
			return;
		}

		// region CSS_INLINE
		ob_start();
		if (count(self::$CSS_INLINE)) {
			echo '<style>';
		}
		foreach (self::$CSS_INLINE as $css) {
			$testMinCss = str_replace('.css', '.min.css', $css);
			if (is_file(Loader::getDocumentRoot() . $testMinCss)) {
				$css = $testMinCss;
			}
			echo str_replace(['url(../'], 'url(' . SITE_TEMPLATE_PATH . '/', file_get_contents(Loader::getDocumentRoot() . $css)) . PHP_EOL;
		}
		unset($testMinCss);
		if (count(self::$CSS_INLINE)) {
			echo '</style>';
		}
		Asset::getInstance()?->addString(ob_get_clean(), true, AssetLocation::BEFORE_CSS);
		// endregion

		// region CSS Defer
		ob_start();
		foreach (self::$CSS_DEFER as $css) {
			$testMinCss = str_replace('.css', '.min.css', $css);
			if (is_file(Loader::getDocumentRoot() . $testMinCss)) {
				$css = $testMinCss;
			}
			?>
			<link rel="preload" href="<?=\CUtil::GetAdditionalFileURL($css)?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
			<noscript><link rel="stylesheet" href="<?=\CUtil::GetAdditionalFileURL($css)?>"></noscript>
			<?
		}
		unset($testMinCss);
		Asset::getInstance()?->addString(ob_get_clean(), true, AssetLocation::AFTER_CSS);
		// endregion

		// region typical CSS
		foreach (self::$CSS as $css) {
			Asset::getInstance()?->addCss($css);
		}
		// endregion
	}
}