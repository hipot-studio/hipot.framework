<?php
namespace Hipot\Utils;

use Intervention\Image\ImageManagerStatic as iiImage,
	Opis\Closure\SerializableClosure,
	Bitrix\Main\Loader,
	Bitrix\Main\Config\Option,
	CMainPage,
	Bitrix\Main\IO,
	RuntimeException,
	Bitrix\Main\ArgumentException,
	Bitrix\Main\Request,
	Hipot\Services\BitrixEngine;

/**
 * Обработка изображений
 * Использует библиотеку трансформации \Intervention\Image 2.X* (переписать под 3)
 *
 * Необходимо: php 8.1, Extensions: fileinfo, GD (лучше Imagick)
 *
 * @see https://image.intervention.io/v2
 * @see https://www.hipot-studio.com/Codex/cimg-constantly-integrable-modifier-of-graphics/
 *
 * @author		(c) hipot studio
 * @version		5.5.0, 2024
 *
 * @method static void setTag(string $tag) Установка тега, ставить тег перед каждой трансформацией. Для структурирования
 *     /upload/himg_cache/<$tagName>/aaa/aaaaaaaaaaaaaaaaaaa/... При этом можно удобно удалять кеш через удаление папки /upload/himg_cache/<$tagName>
 * @method static void oneResizeParams(array $params = []) Установить предварительные настройки для ОДНОГО последующего вызова Resize() или ResizeOverlay()
 *     ['tag' => 'detail-pics', 'decodeToFormat'  => 'jpg', 'saveAlpha' => false, 'jpgQuality' => 95]
 */
final class Img
{
	// useful constants:

	/* методы ресайза */
	public const M_CROP			    = 'CROP';
	public const M_CROP_TOP		    = 'CROP_TOP';
	public const M_FULL			    = 'FULL';
	public const M_FULL_S			= 'FULL_S';
	public const M_PROPORTIONAL	    = 'PROPORTIONAL';
	public const M_STRETCH			= 'STRETCH';

	/* filepath types */
	private const FILEPATH_BITRIX_ID = 'bxid';
	private const FILEPATH_ABS_PATH = 'abs';
	private const FILEPATH_REL_DOC_ROOT_PATH = 'rel';
	private const FILEPATH_URI_PATH = 'uri';
	private const OVERLAY_POSITION_TYPES = ['center', 'top-left', 'top', 'top-right', 'left', 'right', 'bottom-left', 'bottom', 'bottom-right'];
	private const IMAGE_DECODE_FORMATS = ['webp', 'png', 'jpeg', 'jpg', 'gif'];

	// region class fields:

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
	 * @var string
	 */
	private string $mimeType;

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

	// region deprecated oneResizeParams(), use $config in getInstance()

	/**
	 * Имя тега, см. setTag(...)
	 * @use self::oneResizeParams();
	 * @var string
	 */
	private static string $tag = '';

	/**
	 * Сохранять ли полупрозрачность при методах FULL и FULL_S? При этом меняется формат на png
	 * @use self::oneResizeParams();
	 * @var bool
	 */
	private static bool $saveAlpha = false;

	/**
	 * Можно переопределить в какой формат в итоге сохранить png|gif|jpeg|webp
	 * @use self::oneResizeParams();
	 * @var bool|string
	 * @internal
	 */
	public static $decodeToFormat = false;

	/**
	 * Качество jpeg сохранения изображения
	 * @use self::oneResizeParams();
	 * @var int
	 */
	private static int $jpgQuality = 0;

	// endregion

	// endregion
	private ?array $config = [];
	private bool $setSid = false;
	private ?Request $request = null;

	/**
	 * @param array|null $config
	 * @param bool       $setSid = false в путь установить ID сайта (удобно при многосайтовости)
	 */
	public function __construct(
		/**
		 * @var array{'tag':string, 'decodeToFormat':string, 'saveAlpha': bool, 'jpgQuality': int}
		 */
		?array $config = [],
		bool $setSid = false,
		?Request $request = null
	)
	{
		$this->request = $request;
		$this->setSid  = $setSid;
		$this->config  = $config;
		// region dependencies
		if (class_exists('Imagick') && extension_loaded('imagick')) {
			iiImage::configure(['driver' => 'imagick']);
		}
		if (!class_exists(CMainPage::class)) {
			require_once Loader::getDocumentRoot() . "/bitrix/modules/main/include/mainpage.php";
		}
		// endregion

		$this->defaultJpgQuality = (int)Option::get('main', 'image_resize_quality', '95');

		$this->setResizeParams($config);

		$this->request = BitrixEngine::getInstance()->request;
	}

	/**
	 * @param ?array{'tag':string, 'decodeToFormat':string, 'saveAlpha': bool, 'jpgQuality': int} $config = []
	 */
	public static function getInstance(?array $config = []): self
	{
		return new self($config);
	}

	/**
	 * Work with deprecated code (compatibility)
	 * @param string     $name
	 * @param array|null $arguments
	 * @return mixed
	 */
	public static function __callStatic(string $name, ?array $arguments = null)
	{
		if (strtolower($name) == 'oneresizeparams') {
			$name = 'setResizeParams';
		} else {
			$name .= 'Internal';
		}
		return self::getInstance()->$name(...$arguments);
	}

	/**
	 * Получить значение параметра трансофмации (для совместимости со старыми вызовами если не определены в контрейнере $config, то берет из статичных полей)
	 *
	 * @param string $name The name of the parameter
	 * @return mixed The value of the parameter if it exists, otherwise the default value.
	 */
	public function getParam(string $name)
	{
		return $this->config[$name] ?? self::${$name};
	}

	// region service addons methods:

	/**
	 * Загружает картинку
	 * @param int|array|string $img Путь к картинке относительно корня сайта,<br>
	 *        либо относительно корня диска, либо битрикс ID, либо массив из битрикс \CFile::GetByID(), либо урл до картики
	 * @throws \RuntimeException
	 * @throws \Bitrix\Main\IO\InvalidPathException
	 */
	private function load($img): void
	{
		// если передан массив из битрикс \CFile::GetByID()
		if (is_array($img) && isset($img["SRC"])) {
			$img = $img["SRC"];
		}
		// если передан урл сервера на себя
		$img = str_replace('http' . ($this->request->isHttps() ? 's://' : '://') . $this->request->getServer()->getServerName(), '', $img);
		$isUri = filter_var($img, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) !== false;

		if (is_string($img) && !$isUri) {
			$img = $this->normalizePath($img);
		}

		if (!is_numeric($img) && !is_string($img)) {
			throw new RuntimeException('wrong_input_img_type');
		}

		if (is_numeric($img)) {
			// если входит БитриксID картинки
			$this->path_type		= self::FILEPATH_BITRIX_ID;
			$this->r_src			= \CFile::GetPath($img);
			$this->src				= Loader::getDocumentRoot() . $this->r_src;
		} elseif (strpos($img, Loader::getDocumentRoot()) !== false) {
			// если входит абсолютный путь к картинке на диске
			if (! is_file($img)) {
				throw new RuntimeException('wrong_input_img_type');
			}
			$this->path_type		= self::FILEPATH_ABS_PATH;
			$this->r_src			= str_replace(Loader::getDocumentRoot(), '', $this->src);
			$this->src				= $img;
		} elseif (is_file(Loader::getDocumentRoot() . $img)) {
			// если входит путь к картинке относительно корня сайта
			$this->path_type		= self::FILEPATH_REL_DOC_ROOT_PATH;
			$this->r_src			= $img;
			$this->src				= Loader::getDocumentRoot() . $this->r_src;
		} elseif ($isUri) {
			// если url
			$this->path_type		= self::FILEPATH_URI_PATH;
			$downloadedFile         = \CFile::MakeFileArray($img);
			if ((int)$downloadedFile['size'] <= 0) {
				throw new RuntimeException('wrong_input_img_type_cant_download');
			}
			$this->r_src			= $downloadedFile['name'];
			$this->src				= $downloadedFile['tmp_name'];
			$this->mimeType         = $downloadedFile['type'];
		} else {
			throw new RuntimeException('wrong_input_img_type_cant_process');
		}
	}

	/**
	 * Формирует путь для сохранения изображения в дереве директорий Битрикс.
	 * @throws \Bitrix\Main\IO\InvalidPathException
	 */
	private function makeSavePathForBx(): void
	{
		// 32000-2 folders can have unix folder (ext3)
		$imgPath = substr($this->postfix, 0, 3);
		if (trim($this->getParam('tag')) != '') {
			$imgPath = $this->getParam('tag') . '/' . $imgPath;
		}

		$this->r_path = '/upload/himg_cache/' . $imgPath . '/' . $this->postfix . '/' . basename($this->src);
		$this->r_path = $this->normalizePath($this->r_path);
		$this->decodeFormat();
		$this->path = Loader::getDocumentRoot() . $this->r_path;
	}

	private function decodeFormat(): void
	{
		// uri with no-extension
		$imgExtRegX = '#(png)|(gif)|(jpe?g)|(webp)$#i';
		if (!empty($this->mimeType) && !preg_match($imgExtRegX, $this->r_path)) {
			$ext = pathinfo($this->r_src, PATHINFO_EXTENSION);
			if (! preg_match($imgExtRegX, $ext)) {
				$this->r_path .= '.' . explode('/', $this->mimeType)[1];
			} else {
				$this->r_path = str_replace(basename($this->r_path), $this->r_src, $this->r_path);
			}
		}
		if ($this->getParam('saveAlpha') && in_array($this->method, [self::M_FULL, self::M_FULL_S])) {
			$this->r_path = preg_replace('#(jpe?g)|(gif)$#i', 'png', $this->r_path);
		}
		// gif has bug in this methods
		if (in_array($this->method, [self::M_FULL, self::M_FULL_S])) {
			$this->r_path = preg_replace('#gif$#i', 'png', $this->r_path);
		}
		if ($this->getParam('decodeToFormat')) {
			$this->r_path = preg_replace($imgExtRegX, $this->getParam('decodeToFormat'), $this->r_path);
		}
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
	private function doResizeMethod(?int $w = null, ?int $h = null, string $method = self::M_CROP): void
	{
		if (empty($method)) {
			throw new RuntimeException('no_resize_method_set');
		}

		$aspectRatio = static function ($constraint) {
			$constraint->aspectRatio();
		};
		$aspectRatioNoUpside = static function ($constraint) {
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

		// progressive JPEG and animated GIFs
		$this->iiImage->interlace(true);

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
		if (constant('BX_UTF') === true && strpos(strtolower(PHP_OS), 'win') !== false) {
			$path = mb_convert_encoding($path, 'UTF-8', 'WINDOWS-1251');
		}
		return IO\Path::normalize($path) ?? $path;
	}

	/**
	 * returner array must be serialized
	 * @param array $resizeArgs
	 * @return array
	 */
	private function getConfigPathPostfix(array $resizeArgs = []): array
	{
		foreach ($resizeArgs as $arg => &$argVal) {
			if (is_a($argVal, \Closure::class)) {
				if (class_exists(SerializableClosure::class)) {
					$argVal = new SerializableClosure($argVal);
				} else {
					$debug = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS)[1];
					$argVal = 'Closure_' . $debug['file'] . ':' . $debug['line'];
				}
			}
		}
		unset($argVal);
		return $resizeArgs + array_merge([
				'tag'            => self::$tag,
				'saveAlpha'      => self::$saveAlpha,
				'decodeToFormat' => self::$decodeToFormat,
				'jpgQuality'     => self::$jpgQuality
			], (array)$this->config);
	}

	public static function insertOverlay(self $mi, string $lastWm, string $lastWmPos): void
	{
		$mi->iiImage->insert($lastWm, $lastWmPos);
	}

	// endregion

	// region config setters and getters

	/**
	 * Установить предварительные настройки для ОДНОГО последующего вызова Resize() или ResizeOverlay()
	 * <pre>
	 * [
	 *   'tag'             => 'detail-pics',
	 *   'decodeToFormat'  => 'jpg'              // принудительно выставить формат итогового изображения png|gif|jpeg
	 *   'saveAlpha'       => false,             // M_FULL и M_FULL_S превращать в png
	 *   'jpgQuality'      => 95                 // Качество сохранения jpeg
	 * ]</pre>
	 * @param array{'tag':string, 'decodeToFormat':string, 'saveAlpha': bool, 'jpgQuality': int} $params = []
	 */
	public function setResizeParams(array $params = []): void
	{
		foreach ($params as $name => $value) {
			$loweName = strtolower($name);
			if ($loweName == 'tag') {
				$this->setTagInternal($value);
			}
			if ($loweName == 'savealpha') {
				$this->setSaveAlpha($value);
			}
			if ($loweName == 'decodetoformat') {
				$this->setDecodeToFormat($value);
			}
			if ($loweName == 'jpgquality') {
				$this->setJpgQuality($value);
			}
		}
	}

	/**
	 * Восстанавливает параметры ресайза по умолчанию
	 *
	 * @param bool $confToo = false сбросить внутренние настройки инстанса, либо только статичные поля
	 * @return void
	 * @see self::setResizeParams()
	 */
	public function restoreResizeParams(bool $confToo = true): void
	{
		self::$tag = '';
		self::$saveAlpha = false;
		self::$decodeToFormat = false;
		self::$jpgQuality = 0;
		if ($confToo) {
			$this->config = [];
		}
	}

	/**
	 * Установка тега для структурирования копий по схеме /upload/hiimg_cache/<$tagName>/aaa/aaaaaaaaaaaaaaaaaaa/...
	 * При этом можно удобно удалять кеш через удаление папки /upload/himg_cache/<$tagName>
	 * @param string $tagName = '';
	 */
	public function setTagInternal(string $tagName = ''): void
	{
		self::$tag           = trim($tagName);
		$this->config['tag'] = self::$tag;
	}

	public function setSaveAlpha(bool $saveAlpha): void
	{
		self::$saveAlpha = $saveAlpha;
		$this->config['saveAlpha'] = self::$saveAlpha;
	}

	public function setDecodeToFormat(
		$decodeToFormat = false
	): void
	{
		self::$decodeToFormat = $decodeToFormat;
		$this->config['decodeToFormat'] = self::$decodeToFormat;
	}

	public function setJpgQuality(int $jpgQuality = 0): void
	{
		self::$jpgQuality = $jpgQuality;
		$this->config['jpgQuality'] = self::$jpgQuality;
	}

	/**
	 * @return \Intervention\Image\Image|mixed
	 */
	public function getProcessEngine()
	{
		return $this->iiImage;
	}

	// endregion

	// region useful methods:

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
	 * @return array{'SRC':string,'src':string, 'WIDTH':int,'width':int, 'HEIGHT':int,'height':int}|string массив {путь, шир, выс} или путь к результирующей
	 *     картинке
	 * @throws \Exception
	 */
	public function doResize($f, ?int $w = null, ?int $h = null, string $m = self::M_CROP, bool $retAr = false, ?callable $callbackMi = null)
	{
		$this->load($f);

		$this->method	= $m;
		$this->postfix	= md5(serialize($this->getConfigPathPostfix(func_get_args())));
		if ($this->setSid) {
			$this->postfix = CMainPage::GetSiteByHost() . '/' . $this->postfix;
		}
		$this->makeSavePathForBx();

		if (! $this->wasCached()) {
			$this->doResizeMethod($w, $h, $m);

			if (is_callable($callbackMi)) {
				$callbackMi($this);
			}

			$this->imageSave( $this->getParam('jpgQuality') );
		}

		$r_path = $this->normalizePath($this->r_path);

		if ($retAr) {
			$par = getimagesize($this->path);
			$return = [
				'SRC'		=> $r_path,
				'WIDTH'		=> $par[0],
				'HEIGHT'	=> $par[1],
				// CFile::ResizeImageGet(...) compatibility
				'src'		=> $r_path,
				'width'		=> $par[0],
				'height'	=> $par[1]
			];
			if (is_numeric($f) && $this->path_type == self::FILEPATH_BITRIX_ID) {
				$return = ['ID' => $f] + $return;
			}
		} else {
			$return			= $r_path;
		}
		return $return;
	}

	/**
	 * @see self::doResize()
	 */
	public static function resize($f, ?int $w = null, ?int $h = null, string $m = self::M_CROP, bool $retAr = false, ?callable $callbackMi = null)
	{
		$mi = new self();
		$return = $mi->doResize(...func_get_args());
		$mi->restoreResizeParams();
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
	 * @throws \RuntimeException|\Exception
	 */
	public function doResizeOverlay($f, string $to,
	                                string $pos = 'center',
	                                ?int $w = null, ?int $h = null, string $m = self::M_PROPORTIONAL, bool $retAr = false)
	{
		if (strpos($to, Loader::getDocumentRoot()) === false && is_file(Loader::getDocumentRoot() . $to)) {
			$to = Loader::getDocumentRoot() . $to;
		}
		if (! is_file($to)) {
			throw new RuntimeException('wrong_image_wm ' . $to);
		}
		return $this->doResize($f, $w, $h, $m, $retAr, function (self $mi) use ($to, $pos) {
			self::insertOverlay($this, $to, $pos);
		});
	}

	/**
	 * @see self::doResizeOverlay()
	 */
	public static function resizeOverlay($f, string $to, string $pos = 'center', ?int $w = null, ?int $h = null, string $m = self::M_PROPORTIONAL, bool $retAr = false)
	{
		return (new self())->doResizeOverlay(...func_get_args());
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

	// endregion

} // end class


