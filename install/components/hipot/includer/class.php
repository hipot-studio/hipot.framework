<?php
namespace Hipot\Components;

/**
 * Class Includer
 *
 * This class is an extension of the CBitrixComponent class
 * and is responsible for executing the component logic, including
 * caching and template inclusion.
 * <code>
 * $includer = $APPLICATION->IncludeComponent('hipot:includer', 'some.page', []);
 * echo $includer->arResult['SOME_TEMPLATE_PAGE_PARAMS'];
 * unset($includer);
 * // or
 * $APPLICATION->IncludeComponent('hipot:includer', 'some.widget', []);
 * </code>
 */
class Includer extends \CBitrixComponent
{
	public function executeComponent()
	{
		if ($this->startResultCache(false)) {
			$this->setResultCacheKeys([]);
			$this->includeComponentTemplate();
		}
		return $this;
	}
}
