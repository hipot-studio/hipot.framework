<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 23.10.2019 2:34
 * @version pre 1.0
 */

// see https://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata

namespace PHPSTORM_META
{
	registerArgumentsSet('module_include_const',
		\Bitrix\Main\Loader::MODULE_DEMO | \Bitrix\Main\Loader::MODULE_DEMO_EXPIRED | \Bitrix\Main\Loader::MODULE_INSTALLED | \Bitrix\Main\Loader::MODULE_NOT_FOUND
	);

	expectedReturnValues(
		\Bitrix\Main\Loader::includeSharewareModule(),
		argumentsSet('module_include_const')
	);

	registerArgumentsSet('bitrix_components',
	'hipot:ajax', 'hipot:includer', 'hipot:iblock.list', 'hipot:iblock.section', 'hipot:iblock.menu_ext', 'hipot:iblock.menu_ext'
	);

	expectedArguments(
		\CMain::IncludeComponent(),
		0,
		argumentsSet('bitrix_components')
	);
}