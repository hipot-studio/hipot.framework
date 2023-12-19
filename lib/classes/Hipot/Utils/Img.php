<?php
namespace Hipot\Utils;

use Intervention\Image\ImageManagerStatic as iiImage;
use Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	CMainPage,
	Bitrix\Main\IO;
use RuntimeException,
	Bitrix\Main\ArgumentException;

/**
 * Обработка изображений aka CImg 2.0.
 * Использует библиотеку трансформации \Intervention\Image 2.X *
 *
 * Необходимо: php 8.0, Extensions: fileinfo, GD (лучше Imagick)
 *
 * @see https://image.intervention.io/v2
 * @see https://www.hipot-studio.com/Codex/cimg-constantly-integrable-modifier-of-graphics/
 *
 * @author		(c) hipot studio
 * @version		4.0.0, 2023
 */
final class Img
{
	////////////////////
	// useful constants:
	////////////////////

	/* методы ресайза */
	public const M_CROP			    = 'CROP';
	public const M_CROP_TOP		    = 'CROP_TOP';
	public const M_FULL			    = 'FULL';
	public const M_FULL_S			= 'FULL_S';
	public const M_PROPORTIONAL	    = 'PROPORTIONAL';
	public const M_STRETCH			= 'STRETCH';

	////////////////////
	// class fields:
	////////////////////

	/**
	 * Тип пути изображения.
	 * Путь к изображению относительно корня сайта, либо относительно корня документов, либо битрикс ID
	 * @var string = bxid|abs|rel
	 */
	private string $path_type;

	/**
	 * Абсолютный путь к картинке на сервере
	 * @var string
	 */
	private string $src;

	/**
	 * Путь к картинке относительно корня сайта
	 * @var string
	 */
	private string $r_src;

	/**
	 * Объект с загруженным изображением для работы
	 * @var \Intervention\Image\Image|mixed
	 */
	private $iiImage;

	/**
	 * Путь для сохранения изображения
	 * @var string
	 */
	private string $path;

	/**
	 * Путь для сохранения изображения относительно корня сайта
	 * @var string
	 */
	private string $r_path;

	/**
	 * Заполняется при любой трансформации - как идентификатор для кеша.
	 * @var string
	 */
	private string $postfix = '';

	/**
	 * Использованный метод
	 * @var string
	 */
	private string $method;

	private int $defaultJpgQuality;

	/**
	 * Имя тега, <b>вызывать перед каждым вызовом трансформации</b>
	 * см SetTag(...)
	 * @var string
	 */
	public static string $tagName = '';

	/**
	 * Сохранять ли полупрозрачность при методах FULL и FULL_S?
	 * При этом меняется формат на png
	 * @var bool
	 */
	public static bool $saveAlpha = false;

	/**
	 * Можно переопределить в какой формат в итоге сохранить png|gif|jpeg|webp
	 * @var bool|string
	 */
	public static $decodeToFormat = false;

	public function __construct()
	{
		if (class_exists('Imagick') && extension_loaded('imagick')) {
			iiImage::configure(['driver' => 'imagick']);
		}
		if (!class_exists(CMainPage::class)) {
			require_once Loader::getDocumentRoot() . "/bitrix/modules/main/include/mainpage.php";
		}
		$this->defaultJpgQuality = (int)Option::get('main', 'image_resize_quality', '95');
	}

	///////////////////////////
	// service addons methods:
	///////////////////////////

	/**
	 * Загружает картинку
	 * @param int|array|string $img Путь к картинке относительно корня сайта,<br>
	 *        либо относительно корня диска, либо битрикс ID, либо массив из битрикс \CFile::GetByID()
	 * @throws \RuntimeException
	 * @throws \Bitrix\Main\IO\InvalidPathException
	 */
	private function load($img): void
	{
		// если передан массив из битрикс \CFile::GetByID()
		if (is_array($img) && isset($img["SRC"])) {
			$img = $img["SRC"];
		}
		if (is_string($img)) {
			$img = $this->normalizePath($img);
		}

		if (!is_numeric($img) && !is_string($img)) {
			throw new RuntimeException('wrong_input_img_type');
		}

		if (is_numeric($img)) {
			// если входит БитриксID картинки
			$this->path_type		= 'bxid';
			$this->r_src			= \CFile::GetPath($img);
			$this->src				= Loader::getDocumentRoot() . $this->r_src;
		} elseif (str_contains($img, Loader::getDocumentRoot())) {
			// если входит абсолютный путь к картинке на диске
			if (! is_file($img)) {
				throw new RuntimeException('wrong_input_img_type');
			}
			$this->path_type		= 'abs';
			$this->src				= $img;
			$this->r_src			= str_replace(Loader::getDocumentRoot(), '', $this->src);
		} elseif (is_file(Loader::getDocumentRoot() . $img)) {
			// если входит путь к картинке относительно корня сайта
			$this->path_type		= 'rel';
			$this->r_src			= $img;
			$this->src				= Loader::getDocumentRoot() . $this->r_src;
		} else {
			throw new RuntimeException('wrong_input_img_type');
		}
	}

	/**
	 * Формирует путь для сохранения изображения в дереве директорий Битрикс.
	 *
	 * @param bool $ssid = false ID сайта указывается при многосайтовости
	 * @throws \Bitrix\Main\IO\InvalidPathException
	 */
	private function makeSavePathForBx(bool $ssid = false): void
	{
		// учет многосайтовости
		if ($ssid) {
			$this->postfix = CMainPage::GetSiteByHost() . '/' . $this->postfix;
		}

		// 32000-2 folders can have unix folder (ext3)
		$imgPath = substr($this->postfix, 0, 3);
		if (trim(self::$tagName) != '') {
			$imgPath = self::$tagName . '/' . $imgPath;
			self::setTag('');
		}

		$this->r_path = '/upload/himg_cache/' . $imgPath . '/' . $this->postfix . '/' . basename($this->src);
		$this->r_path = $this->normalizePath($this->r_path);
		$this->decodeFormat();
		$this->path = Loader::getDocumentRoot() . $this->r_path;
	}

	private function decodeFormat(): void
	{
		if (self::$saveAlpha && in_array($this->method, [self::M_FULL, self::M_FULL_S])) {
			$this->r_path = preg_replace('#(jpe?g)|(gif)$#i', 'png', $this->r_path);
		}
		// gif has bug in this methods
		if (in_array($this->method, [self::M_FULL, self::M_FULL_S])) {
			$this->r_path = preg_replace('#gif$#i', 'png', $this->r_path);
		}
		if (self::$decodeToFormat) {
			$this->r_path = preg_replace('#(png)|(gif)|(jpe?g)$#i', self::$decodeToFormat, $this->r_path);
		}
		self::$decodeToFormat = false;
	}

	/**
	 * Проверяет закешированность картинки
	 *
	 * @return bool Если картинка закеширована, то возвращает true, иначе false
	 */
	private function wasCached(): bool
	{
		return file_exists($this->path);
	}

	/**
	 * Ресайзит изображение используя метод $method
	 *
	 * @param ?int    $w = null
	 * @param ?int    $h = null
	 * @param string $method = self::M_CROP Метод трансформации, см. self::M_
	 *
	 * @throws \RuntimeException
	 */
	private function doResize(?int $w = null, ?int $h = null, string $method = self::M_CROP): void
	{
		if (empty($method)) {
			throw new RuntimeException('no_resize_method_set');
		}

		$aspectRatio = function ($constraint) {
			$constraint->aspectRatio();
		};
		$aspectRatioNoUpside = function ($constraint) {
			$constraint->aspectRatio();
			$constraint->upsize();
		};

		$this->iiImage = iiImage::make($this->src);

		switch ($method) {
			case self::M_CROP: {
				$this->iiImage->fit($w, $h, $aspectRatio);
				break;
			}
			case self::M_CROP_TOP: {
				$this->iiImage->fit($w, $h, $aspectRatio, 'top');
				break;
			}
			case self::M_FULL: {
				$this->iiImage->resize($w, $h, $aspectRatio)
					->resizeCanvas($w, $h, 'center', false, 'rgba(255, 255, 255, 0.01)');
				break;
			}
			case self::M_FULL_S: {
				$this->iiImage->resize($w, $h, $aspectRatioNoUpside)
					->resizeCanvas($w, $h, 'center', false, 'rgba(255, 255, 255, 0.01)');
				break;
			}
			case self::M_PROPORTIONAL: {
				$this->iiImage->resize($w, $h, $aspectRatio);
				break;
			}
			case self::M_STRETCH: {
				$this->iiImage->resize($w, $h);
				break;
			}
		}

		// CUSTOMS FILTERS
		$this->iiImage->sharpen(6);
	}

	/**
	 * Сохраняет изображение
	 *
	 * @param int $jpgQuality = false Качество для jpeg (если false берет из настроек главного модуля)
	 */
	private function imageSave(int $jpgQuality = 0): void
	{
		CheckDirPath($this->path);

		if ($jpgQuality == 0) {
			$jpgQuality = $this->defaultJpgQuality;
		}

		// итог - сохраняем либо gif, либо png, либо jpeg
		$this->iiImage->save($this->path, $jpgQuality);
	}

	/**
	 * @param $path
	 * @return string
	 * @throws \Bitrix\Main\IO\InvalidPathException
	 */
	private function normalizePath(string $path): string
	{
		// on winnt all paths is windows-1251 encoding
		if (constant('BX_UTF') === true && strpos(ToLower(PHP_OS), 'win') !== false) {
			$path = mb_convert_encoding($path, 'UTF-8', 'WINDOWS-1251');
		}
		return IO\Path::normalize($path) ?? $path;
	}

	public static function insertOverlay(self $mi, string $lastWm, string $lastWmPos): void
	{
		$mi->iiImage->insert($lastWm, $lastWmPos);
	}

	//////////////////////////////////////////////////////////////////
	// useful methods:
	//////////////////////////////////////////////////////////////////

	/**
	 * Установить предварительные настройки для ОДНОГО последующего вызова Resize() или ResizeOverlay()
	 * <pre>
	 * [
	 *   'tag'             => 'detail-pics',
	 *   'decodeToFormat'  => 'jpg'              // принудительно выставить формат итогового изображения png|gif|jpeg
	 *   'saveAlpha'       => false,             // M_FULL и M_FULL_S превращать в png
	 * ]</pre>
	 * @param array{'tag':string, 'decodeToFormat':string, 'saveAlpha': bool} $params = []
	 */
	public static function oneResizeParams(array $params = []): void
	{
		foreach ($params as $name => $value) {
			$ln = ToLower($name);
			if ($ln == 'tag') {
				self::SetTag($value);
			}
			if ($ln == 'savealpha') {
				self::$saveAlpha = $value;
			}
			if ($ln == 'decodetoformat') {
				self::$decodeToFormat = $value;
			}
		}
	}

	/**
	 * Установка тега, ставить тег перед каждой трансформацией.
	 * Для структурирования /upload/hiimg_cache/<$tagName>/aaa/aaaaaaaaaaaaaaaaaaa/...
	 * При этом можно удобно удалять кеш через удаление папки /upload/himg_cache/<$tagName>
	 *
	 * @param string $tagName = '';
	 */
	public static function SetTag(string $tagName = ''): void
	{
		self::$tagName = trim($tagName);
	}

	/**
	 * Сделать ресайз изображения $f
	 *
	 * @param string|int|array      $f Картинка (abs|bxId|rel) при массиве нужно чтобы был ключ SRC (тип загрузки rel)
	 * @param ?int                  $w = null Ширина
	 * @param ?int                  $h = null Высота (можно передать null для подгонки, <b>один параметр ширину или высоту надо задать!</b>)
	 * @param string                $m = Img::M_CROP Метод трансформации (см: Img::M_*)
	 * @param bool                  $retAr = false Возвращать массив или строку. По умолчанию строку с путем к файлу
	 * @param ?callable             $callbackMi = null Метод, в который передается объект перед сохранением, сигнатура: $callbackMi(Img $mi)
	 *
	 * @return string|array{'SRC':string,'src':string, 'WIDTH':int,'width':int, 'HEIGHT':int,'height':int} путь к результирующей картинке или массив {путь, шир, выс}
	 * @throws \Exception
	 */
	public static function Resize($f, ?int $w = null, ?int $h = null, string $m = self::M_CROP, bool $retAr = false, ?callable $callbackMi = null): string|array
	{
		$mi = new self();
		$mi->load($f);

		$mi->method		= $m;
		$mi->postfix	= md5(serialize(func_get_args()));
		$mi->makeSavePathForBx();

		if (! $mi->wasCached()) {
			$mi->doResize($w, $h, $m);

			if (is_callable($callbackMi)) {
				$callbackMi($mi);
			}

			$mi->imageSave();
		}

		$r_path = $mi->normalizePath( $mi->r_path );

		if ($retAr) {
			$par = getimagesize($mi->path);
			$return = [
				'SRC'		=> $r_path,
				'WIDTH'		=> $par[0],
				'HEIGHT'	=> $par[1],
				// CFile::ResizeImageGet(...) compatibility
				'src'		=> $r_path,
				'width'		=> $par[0],
				'height'	=> $par[1]
			];
		} else {
			$return			= $r_path;
		}

		unset($mi);
		return $return;
	}

	/**
	 * Ресайзит изображение $f и накладывает водный знак $to
	 *
	 * @param int|string|array $f Картинка (bxid|rel|abs) на которую будет накладываться при массиве нужно чтобы был ключ SRC (тип загрузки rel)
	 * @param string     $to Картинка, которая будет накладываться (rel|abs)
	 * @param string     $pos = center (default)<br> top-left<br>
	 *                          top<br> top-right<br> left<br> right<br> bottom-left<br>
	 *                          bottom<br> bottom-right
	 * @param ?int     $w = null Ширина
	 * @param ?int     $h = null Высота (можно передать null для подгонки, <b>один параметр ширину или высоту надо задать!</b>)
	 * @param string   $m = self::M_PROPORTIONAL Метод трансформации (см: hiImg::M_*)
	 * @param bool     $retAr = false Возвращать массив или путь к результирующему файлу. По умолчанию путь
	 *
	 * @return string|array{'SRC':string,'src':string, 'WIDTH':int,'width':int, 'HEIGHT':int,'height':int} путь к результирующей картинке или массив {путь, шир, выс}
	 * @throws \Exception
	 */
	public static function ResizeOverlay($f, string $to, string $pos = 'center', ?int $w = null, ?int $h = null, string $m = self::M_PROPORTIONAL, bool $retAr = false): string|array
	{
		if (!str_contains($to, Loader::getDocumentRoot()) && is_file(Loader::getDocumentRoot() . $to)) {
			$to = Loader::getDocumentRoot() . $to;
		}
		if (! is_file($to)) {
			throw new RuntimeException('wrong_image_wm ' . $to);
		}
		return self::Resize($f, $w, $h, $m, $retAr, static function ($mi) use ($to, $pos) {
			self::insertOverlay($mi, $to, $pos);
		});
	}

	/**
	 * Провести ресайз тегов img в описании html
	 * @param string $htmlText
	 * @param callable $resizer функция выполняющая действие, пример:
	 * <pre>
	 * static function (string $src): array {
	 *      Img::oneResizeParams([
	 *          'tag'       => basename(__DIR__),
	 *          'decodeToFormat'  => 'webp',
	 *          'saveAlpha'       => false,
	 *      ]);
	 *      return Img::Resize($matches['src'], 1024, null, Img::M_PROPORTIONAL, true);     // return array from Resize-method
	 * };
	 * </pre>
	 * @param bool $wrapLink = false Обернуть ли уменьшенную копию изображения в ссылку на оригинал
	 * @return string
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public static function resizeImagesInHtml(string $htmlText, callable $resizer, bool $wrapLink = false): string
	{
		if (! is_callable($resizer)) {
			throw new ArgumentException('no resizer callback found');
		}

		return preg_replace_callback('#<img(?<params1>[^>]*)src=["\']?(?<src>[^"\']+)["\']?(?<params2>[^>]*)>#i', static function ($matches) use ($resizer, $wrapLink) {
			$arSmallSrc = [];
			try {
				$arSmallSrc = $resizer($matches['src']);

				foreach (['params1', 'params2'] as $p) {
					$matches[$p] = preg_replace('#width\s*=(["\'])?(\d+)(["\'])?#i', '', $matches[$p]);
					$matches[$p] = preg_replace('#height\s*=(["\'])?(\d+)(["\'])?#i', '', $matches[$p]);
				}
				$matches['params1'] .= sprintf(' width="%s" height="%s"', $arSmallSrc['width'], $arSmallSrc['height']);
			} catch (\Exception $e) {
				UUtils::logException($e);
				$arSmallSrc['src'] = $matches['src'];
			}

			$result = '';
			if ($wrapLink) {
				$result .= '<a href="' . $matches['src'] . '">';
			}
			$result .= '<img '.$matches['params1'].' src="' . $arSmallSrc['src'] . '" ' . $matches['params2'] . '>';
			if ($wrapLink) {
				$result .= '</a>';
			}
			return $result;
		}, $htmlText);
	}

} // end class


