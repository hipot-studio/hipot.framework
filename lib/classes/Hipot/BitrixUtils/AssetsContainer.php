<?php

namespace Hipot\BitrixUtils;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Page\AssetLocation;
use Bitrix\Main\Web\Json;

class AssetsContainer
{
	public const CSS = 1;
	public const CSS_INLINE = 1;
	public const CSS_DEFER = 2;

	private static array $CSS = [];
	private static array $CSS_INLINE = [];
	private static array $CSS_DEFER = [];

	private static array $siteJsConfigs = [];

	public static function addCss(string $path, int $type = self::CSS): void
	{
		match ($type) {
			self::CSS_INLINE        => self::$CSS_INLINE[] = $path,
			self::CSS_DEFER         => self::$CSS_DEFER[] = $path,
			default                 => self::$CSS[] = $path,
		};
	}

	public static function addJsConfig(array $config, bool $rewrite = false): void
	{
		if (!is_array(self::$siteJsConfigs)) {
			$initJsConfigs = [
				'SITE_TEMPLATE_PATH' => SITE_TEMPLATE_PATH,
				'IS_DEV'             => IS_BETA_TESTER,
				'lang'               => [],
				'requireJSs'         => [],
				'requireCss'         => []
			];
			self::$siteJsConfigs = $initJsConfigs;
		}
		foreach ($config as $key => $value) {
			if ($rewrite) {
				self::$siteJsConfigs[$key] = $value;
			} else {
				if (!is_array($value)) {
					$value = [$value];
				}
				self::$siteJsConfigs[$key] = array_merge(self::$siteJsConfigs[$key] ?? [], $value);
			}
		}
	}

	public static function onEpilogSendAssets(): void
	{
		if (Application::getInstance()?->getContext()?->getRequest()->isAdminSection()
			|| Application::getInstance()?->getContext()?->getRequest()->isAjaxRequest()
		) {
			return;
		}

		self::$CSS_INLINE = array_unique(self::$CSS_INLINE);
		self::$CSS_DEFER  = array_unique(self::$CSS_DEFER);
		self::$CSS        = array_unique(self::$CSS);

		// region CSS_INLINE
		ob_start();
		if (count(self::$CSS_INLINE)) {
			echo PHP_EOL;
			echo '<style>';
		}
		$fileSize = 0;
		foreach (self::$CSS_INLINE as $css) {
			$testMinCss = str_replace('.css', '.min.css', $css);
			if (is_file(Loader::getDocumentRoot() . $testMinCss)) {
				$css = $testMinCss;
			}
			echo sprintf('/* __%s__ */ ', basename($css)) . str_replace(['url(../'], 'url(' . SITE_TEMPLATE_PATH . '/', file_get_contents(Loader::getDocumentRoot() . $css)) . PHP_EOL;
			$fileSize += filesize(Loader::getDocumentRoot() . $css);
		}
		unset($testMinCss);
		if (count(self::$CSS_INLINE)) {
			echo '/* __CSS_INLINE_SIZE__ = ' . \CFile::FormatSize($fileSize) . ' */';
			echo '</style>';
		}
		Asset::getInstance()?->addString(ob_get_clean(), true, AssetLocation::BEFORE_CSS);
		// endregion

		// region CSS Defer
		ob_start();
		$fileSize = 0;
		foreach (self::$CSS_DEFER as $css) {
			$testMinCss = str_replace('.css', '.min.css', $css);
			if (is_file(Loader::getDocumentRoot() . $testMinCss)) {
				$css = $testMinCss;
			}
			?>
			<link rel="preload" href="<?=\CUtil::GetAdditionalFileURL($css)?>" as="style" onload="this.onload=null;this.rel='stylesheet'">
			<noscript><link rel="stylesheet" href="<?=\CUtil::GetAdditionalFileURL($css)?>"></noscript>
			<?
			$fileSize += filesize(Loader::getDocumentRoot() . $css);
		}
		echo '<!-- __CSS_DEFER_SIZE__ = ' . \CFile::FormatSize($fileSize) . ' -->';
		unset($testMinCss);
		Asset::getInstance()?->addString(ob_get_clean(), true, AssetLocation::AFTER_CSS);
		// endregion

		// region typical CSS
		foreach (self::$CSS as $css) {
			Asset::getInstance()?->addCss($css);
		}
		// endregion

		self::sendJsParamsAsset();
	}

	/**
	 * need change on concrete site
	 * @override
	 * @return void
	 */
	public static function sendJsParamsAsset(): void
	{
		if (count(self::$siteJsConfigs) == 0) {
			// init base js config
			self::addJsConfig([]);
		}

		ob_start();
		?>
		<script data-skip-moving="true">
			/**
			 * @type {{
			 * SITE_TEMPLATE_PATH:string, IS_DEV:boolean, lang:{},
			 * requireJSs:{},
			 * requireCss:{}
			 * }}
			 */
			const appParams = <?=Json::encode(self::$siteJsConfigs)?>;
		</script>
		<?
		Asset::getInstance()?->addString(ob_get_clean(), true, AssetLocation::AFTER_JS_KERNEL);
	}
}