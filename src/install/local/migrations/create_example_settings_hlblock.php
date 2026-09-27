<?php

declare(strict_types=1);

use Hipot\BitrixUtils\HiBlockApps;
use Hipot\Types\Enums\UserFieldTypes;

defined('B_PROLOG_INCLUDED') || die();

return HiBlockApps::installHiBlock(
	'ExampleSettings',
	'hi_example_settings',
	[
		[
			'CODE' => 'NAME',
			'TYPE' => UserFieldTypes::STRING,
			'SORT' => 100,
			'REQUIRED' => 'Y',
			'NAME' => 'Название',
			'SETTINGS' => ['SIZE' => 60, 'ROWS' => 1],
		],
		[
			'CODE' => 'VALUE',
			'TYPE' => UserFieldTypes::STRING,
			'SORT' => 200,
			'NAME' => 'Значение',
			'SETTINGS' => ['SIZE' => 60, 'ROWS' => 3],
		],
		[
			'CODE' => 'SORT',
			'TYPE' => UserFieldTypes::INTEGER,
			'SORT' => 300,
			'NAME' => 'Сортировка',
			'SETTINGS' => ['DEFAULT_VALUE' => 500],
		],
	],
);
