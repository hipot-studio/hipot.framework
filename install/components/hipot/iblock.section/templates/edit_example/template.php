<?
/**
 * шаблон исключительно для отладки, не использовать для вывода полезного контента
 *
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
// region var_templ
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $componentPath */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var array $templateData */
/** @var CBitrixComponent $component */
// endregion
$this->setFrameMode(true);

use Hipot\Utils\UUtils;
use Bitrix\Main\Localization\Loc;
?>

<?foreach ($arResult['SECTIONS'] as $item) {
	UUtils::setSectionEdit($this, $item, Loc::getMessage('CT_BNL_ELEMENT_DELETE_CONFIRM'));
	?>
	<div <?=UUtils::getEditAreaAttrId($this, $item)?>>
		<?=$item['NAME']?>
	</div>
<?}?>
