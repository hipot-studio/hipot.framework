<?php

declare(strict_types=1);

namespace Bitrix\Catalog;

require_once __DIR__ . '/IblockLookupOrm.php';

final class GroupTable
{
	use \Bitrix\Iblock\LookupTableStub;
}

namespace Bitrix\Main;

if (!class_exists(LoaderException::class)) {
	class LoaderException extends \RuntimeException
	{
	}
}
