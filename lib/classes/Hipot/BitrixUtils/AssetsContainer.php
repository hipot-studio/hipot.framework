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

	/**
	 * Adds a CSS file path to the appropriate asset queue based on the specified type.
	 *
	 * This method organizes CSS assets into three categories:
	 * - Inline CSS (processed and inserted directly into the page's `<style>` tags).
	 * - Deferred CSS (loaded asynchronously to optimize performance).
	 * - Standard CSS (linked in the conventional way for immediate loading).
	 *
	 * @param string $path The file path of the CSS resource to be added.
	 * @param int    $type The type of CSS inclusion, which determines how the CSS will be processed.
	 *                  Valid options are:
	 *                  - self::CSS_INLINE: Adds the CSS file path to the inline CSS collection.
	 *                  - self::CSS_DEFER: Adds the CSS file path to the deferred CSS collection.
	 *                  - self::CSS: Adds the CSS file path to the standard CSS collection. Default is `self::CSS`.
	 *
	 * @return void
	 */
	public static function addCss(string $path, int $type = self::CSS): void
	{
		match ($type) {
			self::CSS_INLINE        => self::$CSS_INLINE[] = $path,
			self::CSS_DEFER         => self::$CSS_DEFER[] = $path,
			default                 => self::$CSS[] = $path,
		};
	}

	/**
	 * Adds or updates the JavaScript configuration for the site.
	 *
	 * This method initializes the JavaScript configuration if it hasn't been set already, and then adds or updates
	 * the configuration with the values provided in the input array. Existing configuration values can either
	 * be overwritten completely or merged, based on the specified rewrite flag.
	 *
	 * @param array $config The configuration data to be added or updated. Keys represent configuration names, with corresponding values.
	 * @param bool  $rewrite A flag indicating whether existing configuration values should be completely
	 *                       overwritten. If false, values will be merged instead. Default is false.
	 *
	 * @return void
	 */
	public static function addJsConfig(array $config, bool $rewrite = false): void
	{
		if (!is_array(self::$siteJsConfigs) || count(self::$siteJsConfigs) == 0) {
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

	/**
	 * Method to process and correctly include CSS assets at the epilog stage of the application lifecycle.
	 *
	 * This method ensures that CSS resources are handled efficiently by:
	 * - Detecting and skipping processing in admin sections or AJAX contexts.
	 * - Consolidating and organizing inline, deferred, and typical CSS assets.
	 * - Preloading and inlining CSS as needed while tuning relative URLs within stylesheets.
	 * - Using the Asset system to append styles in appropriate locations for optimized loading.
	 *
	 * @return void
	 */
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
			$content = file_get_contents(Loader::getDocumentRoot() . $css);
			$content = str_replace(['url(../'], 'url(' . SITE_TEMPLATE_PATH . '/', $content);
			$content = preg_replace('#url\([\'"]\.\./([^\'"]+)[\'"]\)#', 'url("' . SITE_TEMPLATE_PATH . '/\1")', $content);
			echo sprintf('/* __%s__ */ ', basename($css)) . $content . PHP_EOL;
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