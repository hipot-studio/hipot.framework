<?php

namespace Hipot\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Data\Cache;
use Bitrix\Main\Data\TaggedCache;
use Bitrix\Main\Engine\CurrentUser;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Request;
use Hipot\Types\Singleton;

final class BitrixEngine
{
	use Singleton;

	public function __construct(
		public ?Application $app = null,
		public ?Request     $request = null,
		public ?CurrentUser $user = null,
		public ?Cache       $cache = null,
		public ?TaggedCache $taggedCache = null,
		public ?Asset       $asset = null
	)
	{
	}

	public static function initInstance(): self
	{
		return new self(
			Application::getInstance(),
			Application::getInstance()?->getContext()?->getRequest(),
			CurrentUser::get(),
			Cache::createInstance(),
			Application::getInstance()->getTaggedCache(),
			Asset::getInstance()
		);
	}
}