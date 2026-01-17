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
	
	registerArgumentsSet('hipot_components',
		'hipot:ajax', 'hipot:includer', 'hipot:iblock.list', 'hipot:hiblock.list', 'hipot:iblock.section', 'hipot:iblock.menu_ext', 'hipot:iblock.menu_ext'
	);
	
	expectedArguments(
		\CMain::IncludeComponent(),
		0,
		argumentsSet('hipot_components')
	);
	expectedArguments(
		\CBitrixComponent::includeComponentClass(),
		0,
		argumentsSet('hipot_components')
	);
	
	registerArgumentsSet('bitrix_page_properties',
		'title', 'description', 'h1', 'robots', 'canonical', 'keywords', 'og:title', 'og:description', 'og:type', 'og:url', 'og:image', 'og:image:alt'
	);
	
	expectedArguments(
		\CMain::SetPageProperty(),
		0,
		argumentsSet('bitrix_page_properties')
	);
	
	expectedArguments(
		\CMain::GetPageProperty(),
		0,
		argumentsSet('bitrix_page_properties')
	);
}