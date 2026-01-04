<?php
namespace Hipot\Services;

use COM;

/**
 * work only on Windows capture IE-screenshot service
 */
final class PageScreenshotCapture
{
	const string COM_OBJECT_NAME = 'InternetExplorer.Application';
	
	/**
	 * @var ?object{ HWND: int, Visible: bool, Fullscreen: bool, Width: int, Height: int, Navigate: callable, Busy: callable, Quit: callable }
	 * @see https://learn.microsoft.com/en-us/previous-versions/windows/internet-explorer/ie-developer/platform-apis/aa752084(v=vs.85)
	 */
	private ?object $browser;
	private int $handle;
	
	public function __construct()
	{
	}
	
	public function __destruct()
	{
		$this->browser?->Quit();
		unset($this->handle);
	}
	
	public function __invoke(string $url, string $imgPathName = ''): void
	{
		if (trim($imgPathName) === '') {
			$imgPathName = basename($url, '.swf') . '.jpg';
		}
		
		$this->initBrowser();
		$this->browser->Navigate($url);
		
		/* Still working? */
		$b = 0;
		while ($this->browser->Busy) {
			com_message_pump(4000);
			$b++;
			if ($b > 500) {
				break;
			}
		}
		$im = imagegrabwindow($this->handle, true);
		
		if ($im !== false) {
			$dest = imagecreatetruecolor(imagesx($im), imagesy($im));
			imagecopy($dest, $im, 0, 0, 0, 0, imagesx($im), imagesy($im));
			imagejpeg($dest, $imgPathName, 75);
		}
	}
	
	private function createBrowser(): void
	{
		$this->browser = new COM(self::COM_OBJECT_NAME);
		$this->handle  = $this->browser->HWND;
	}
	
	private function initBrowser(): void
	{
		static $isInitialized = false;
		if ($isInitialized) {
			return;
		}
		
		$this->createBrowser();
		$this->browser->Visible = true;
		$this->browser->Fullscreen = true;
		$this->browser->Width = 2560;
		$this->browser->Height = 1440;
		
		$isInitialized = true;
	}
}