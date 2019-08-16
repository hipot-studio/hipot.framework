<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 05.06.2018 22:18
 * @version pre 1.0
 */

use \Intervention\Image\ImageManagerStatic as iiImage;

if (extension_loaded('imagick') && class_exists('Imagick')) {
	iiImage::configure(['driver' => 'imagick']);
}

/**
 * Обработка изображений aka CImg 2.0
 *
 * За основу взят класс CImg и сохранена некоторая совместимость c ним
 *
 * Использует библиотеку трансформации \Intervention\Image 2.X
 *
 * Необходимо:
 * php 7.1, Fileinfo Extension, GD (лучше Imagick)
 *
 * @see http://image.intervention.io/
 * @see https://hipot.socialmatrix.net/Codex/cimg-constantly-integrable-modifier-of-graphics/
 *
 * @throws 		Intervention\Image\Exception\*
 *
 * @author		(c) hipot
 * @version		3.0, 2019
 */
class Img
{
	////////////////////
	// useful constants:
	////////////////////

	/* методы ресайза */

	const M_CROP			= 'CROP';

	const M_CROP_TOP		= 'CROP_TOP';

	const M_FULL			= 'FULL';

	const M_FULL_S			= 'FULL_S';

	const M_PROPORTIONAL	= 'PROPORTIONAL';

	const M_STRETCH			= 'STRETCH';

	////////////////////
	// class fields:
	////////////////////

	/**
	 * Тип пути изображения. <br/>
	 * Путь к изображению относительно корня сайта, либо относительно корня документов, либо битрикс ID
	 * @var string = bxid|abs|rel
	 */
	public $path_type;

	/**
	 * Абсолютный путь к картинке на сервере
	 * @var string
	 */
	public $src;

	/**
	 * Путь к картинке относительно корня сайта
	 * @var string
	 */
	public $r_src;

	/**
	 * Объект с загруженным изображением для работы
	 * @var \Intervention\Image\Image
	 */
	public $iiImage;

	/**
	 * Путь для сохранения изображения
	 * @var string
	 */
	public $path;

	/**
	 * Путь для сохранения изображения относительно корня сайта
	 * @var string
	 */
	public $r_path;

	/**
	 * Заполняется при любой трансформации - как идентификатор для кеша.
	 * @var string
	 */
	public $postfix = '';

	/**
	 * Имя тега, <b>вызывать перед каждым вызовом трансформации</b>
	 * см SetTag(...)
	 * @var string
	 */
	public static $tagName = '';

	/**
	 * Использованный метод
	 * @var string
	 */
	public $method;

	/**
	 * Сохранять ли полупрозрачность при методах FULL и FULL_S?
	 * При этом меняется формат на png
	 * @var bool
	 */
	public static $saveAlpha = false;

	///////////////////////////
	// service addons methods:
	///////////////////////////

	/**
	 * Загружает картинку
	 *
	 * @param $img Путь к картинке относительно корня сайта,<br>
	 *        либо относительно корня диска, либо битрикс ID
	 *
	 * @throws \RuntimeException
	 */
	protected function load($img)
	{
		if ($_SERVER['SERVER_SOFTWARE'] == 'Microsoft-IIS/666.0' && constant('BX_UTF') === true) {
			$img = mb_convert_encoding($img, 'WINDOWS-1251', 'UTF-8');
		}

		if (is_numeric($img)) { 												// если входит БитриксID картинки
			$this->path_type		= 'bxid';
			$this->r_src			= \CFile::GetPath($img);
			$this->src				= $_SERVER['DOCUMENT_ROOT'] . $this->r_src;
		} elseif (strpos($img, $_SERVER['DOCUMENT_ROOT']) !== false) { 			// если входит абсолютный путь к картинке на диске
			if (! is_file($img)) {
				throw new \RuntimeException('wrong_input_img_type');
			}
			$this->path_type		= 'abs';
			$this->src				= $img;
			$this->r_src			= str_replace($_SERVER['DOCUMENT_ROOT'], '', $this->src);
		} elseif (is_file($_SERVER['DOCUMENT_ROOT'] . $img)) {					// если входит путь к картинке относительно корня сайта
			$this->path_type		= 'rel';
			$this->r_src			= $img;
			$this->src				= $_SERVER['DOCUMENT_ROOT'] . $this->r_src;
		} else {
			throw new \RuntimeException('wrong_input_img_type');
		}
	}

	/**
	 * Формирует путь для сохранения изображения в дереве директорий Битрикс.
	 *
	 * @param bool $ssid = false ID сайта указывается при многосайтовости
	 */
	protected function makeSavePathForBx($ssid = false)
	{
		// учет многосайтовости
		if ($ssid) {
			require_once $_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/mainpage.php";
			$this->postfix = \CMainPage::GetSiteByHost() . '/' . $this->postfix;
		}

		// 32000-2 folders can have unix folder (ext3)
		$cimgPath = substr($this->postfix, 0, 3);
		if (trim(self::$tagName) != '') {
			$cimgPath = self::$tagName . '/' . $cimgPath;
			self::SetTag('');
		}

		$this->r_path = '/upload/weimg_cache/' . $cimgPath . '/' . $this->postfix . '/' . basename($this->src);

		if (self::$saveAlpha && in_array($this->method, array(self::M_FULL, self::M_FULL_S))) {
			$this->r_path = preg_replace('#(jpe?g)|(gif)$#i', 'png', $this->r_path);
		}
		// gif has bug in this methods
		if (in_array($this->method, array(self::M_FULL, self::M_FULL_S))) {
			$this->r_path = preg_replace('#gif$#i', 'png', $this->r_path);
		}

		$this->path = $_SERVER['DOCUMENT_ROOT'] . $this->r_path;
	}

	/**
	 * Проверяет закешированность картинки
	 *
	 * @return bool Если картинка закеширована, то возвращает true, иначе false
	 */
	protected function wasCached()
	{
		//return false;
		return file_exists($this->path);
	}

	/**
	 * Ресайзит изображение используя метод $method
	 *
	 * @param int    $w = null
	 * @param int    $h = null
	 * @param string $method = self::M_CROP Метод трансформации, см. self::M_
	 *
	 * @throws \RuntimeException
	 */
	protected function do_resize($w = null, $h = null, $method = self::M_CROP)
	{
		if ($method === false) {
			throw new \RuntimeException('no_resize_method_set');
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
	 * @param bool|int $jpgQuality = false Качество для jpeg (если false берет из настроек главного модуля)
	 */
	protected function imagesave($jpgQuality = false)
	{
		CheckDirPath($this->path);

		if ($jpgQuality === false) {
			$jpgQuality = (int)\COption::GetOptionString('main', 'image_resize_quality', '95');
		}

		// итог - сохраняем либо gif, либо png, либо jpeg
		$this->iiImage->save($this->path, $jpgQuality);
	}

	// used in ResizeOverlay
	public static $lastWm;
	public static $lastWmPos;

	public static function insertOverlay($mi)
	{
		$mi->iiImage->insert(self::$lastWm, self::$lastWmPos);
	}

	///////////////////
	// useful methods:
	//////////////////

	/**
	 * Установка тега, ставить тег перед каждой трансформацией.
	 * Для структурирования /upload/weimg_cache/<$tagName>/aaa/aaaaaaaaaaaaaaaaaaa/...
	 * При этом можно удобно удалять кеш через удаление папки /upload/weimg_cache/<$tagName>
	 *
	 * @param string $tagName = '';
	 */
	public static function SetTag($tagName = '')
	{
		self::$tagName = $tagName;
	}

	/**
	 * Ресайзит изображение $f
	 *
	 * @param string|int   $f Картинка (rel_p|abs_p|bitrix_id)
	 * @param int          $w Ширина = null
	 * @param int          $h = null Высота (можно передать null для подгонки, <b>один параметр ширину или высоту надо задать!</b>)
	 * @param string       $m = weImg::M_CROP Метод трансформации (см: weImg::M_*)
	 * @param bool         $retAr = false Возвращать массив или строку. По умолчанию строку с путем к файлу
	 * @param array|string $callbackMi = null Метод, в который передается объект перед сохранением
	 *                (использовать анонимные функции пока нельзя)
	 *
	 * @return array путь к результирующей картинке или массив (шир, выс, путь)
	 * @throws \Exception
	 */
	public static function Resize($f, $w = null, $h = null, $m = self::M_CROP, $retAr = false, $callbackMi = null)
	{
		$mi = new self();
		$mi->load($f);

		$mi->method		= $m;
		$mi->postfix	= md5(serialize(func_get_args()));
		$mi->makeSavePathForBx();

		if (! $mi->wasCached()) {
			$mi->do_resize($w, $h, $m);

			if (is_callable($callbackMi)) {
				$callbackMi($mi);
			}

			$mi->imagesave();
		}

		if ($_SERVER['SERVER_SOFTWARE'] == 'Microsoft-IIS/666.0' && constant('BX_UTF') === true) {
			$r_path = mb_convert_encoding($mi->r_path, 'UTF-8', 'WINDOWS-1251');
		}

		if ($retAr) {
			$par = getimagesize($mi->path);
			$return = array(
				'SRC'		=> $r_path,
				'WIDTH'		=> $par[0],
				'HEIGHT'	=> $par[1]
			);
		} else {
			$return			= $r_path;
		}

		unset($mi);
		return $return;
	}

	/**
	 * Ресайзит изображение $f и накладывает водный знак $to
	 *
	 * @param int|string $f Картинка (rel_p|abs_p|bitrix_id) на которую будет накладываться
	 * @param string     $to Картинка которая будет накладываться (rel_p|abs_p)
	 * @param string     $pos = center (default)<br> top-left<br>
	 *                top<br> top-right<br> left<br> right<br> bottom-left<br>
	 *                bottom<br> bottom-right
	 * @param string     $w = null Ширина
	 * @param string     $h = null Высота (можно передать null для подгонки, <b>один параметр ширину или высоту надо задать!</b>)
	 * @param string     $m Метод трансформации (см: weImg::M_*)
	 * @param bool       $retAr = false Возвращать массив или путь к результирующему файлу. По умолчанию путь
	 *
	 * @return array|string Путь к картинке или массив (шир, выс, путь)
	 * @throws \Exception
	 */
	public static function ResizeOverlay($f, $to, $pos = 'center', $w, $h, $m, $retAr = false)
	{
		if (strpos($to, $_SERVER['DOCUMENT_ROOT']) === false && is_file($_SERVER['DOCUMENT_ROOT'] . $to)) {
			$to = $_SERVER['DOCUMENT_ROOT'] . $to;
		}
		if (! is_file($to)) {
			throw new \RuntimeException('wrong_image_wm ' . $to);
			return false;
		}

		// trap used in insertOverlay
		self::$lastWm		= $to;
		self::$lastWmPos	= $pos;

		return self::Resize($f, $w, $h, $m, $retAr, [self, 'insertOverlay']);
	}


} // end class
