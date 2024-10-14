<?php /** @noinspection GlobalVariableUsageInspection */
defined('B_PROLOG_INCLUDED') || die();
/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

// region need variables
/** @var $curDir string */
/** @var $curPage string */
/** @var $curPageIndex string */
/** @var $isMainPage bool */
// endregion

?>
<!-- PAGE_END -->

</body>
</html>