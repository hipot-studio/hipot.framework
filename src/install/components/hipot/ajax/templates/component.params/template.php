<?php
defined('B_PROLOG_INCLUDED') || die();
// region var_templ
/**
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var array $templateData
 * @var CBitrixComponent $component
 */
// endregion
$this->setFrameMode(true);
?>

<?if ($arParams['IS_AJAX'] != 'Y') {?>
<!--noindex-->
<div class="<?=$arParams['PARAMS']['subBlockName']?>"
     data-decomposed-block="Y"
     data-block="<?=$templateName?>"
     data-block-params="<?=$this->getComponent()->getSignedParameters()?>"
	<?=$arParams['~PARAMS']['style']?>
>
<?}?>
	<?if ($arParams['IS_AJAX'] == 'Y') {?>
		<?
		$APPLICATION->IncludeComponent(
			$arParams['PARAMS']['COMPONENT_NAME'],
			$arParams['PARAMS']['COMPONENT_TEMPLATE'],
			$arParams['PARAMS']['COMPONENT_PARAMS'],
			$component
		);
		?>
	<?}?>
<?if ($arParams['IS_AJAX'] != 'Y') {?>
</div>
<!--/noindex-->
<?}?>

<?
/*
Example:

$arParamsComments = [
	// region main params
	'BLOG_ID'        => BLOG_ID_COMMENTS_TO_TEACH_RU_BLOG_IB,
	'BLOG_POST_ID'   => $templateData['BLOG_POST_ID'],
	'IBLOCK_ELEMENT' => [
		'ID'              => $arResult['ID'],
		'IBLOCK_ID'       => $arResult['IBLOCK_ID'],
		'NAME'            => $arResult['NAME'],
		'DETAIL_PAGE_URL' => $arResult['SEO_DETAIL_PAGE_URL'],
	],
	'LINK_IB_PROP_CODE' => 'BLOG_POST_ID',
	// endregion
	// region visual params
	'BLOG_POST_COMMENT_TEMPLATE' => '.default',
	'BLOG_POST_COMMENTS_COUNT'   => 20,
	'BLOG_POST_COMMENT_PARAMS'   => [
	],
	// endregion
	'CACHE_TIME'     => 3600 * 24 * 7,
];
$APPLICATION->IncludeComponent('hipot:ajax', 'component.params', [
	"PARAMS" => [
		'COMPONENT_NAME'     => 'hipot:comments.blog',
		'COMPONENT_TEMPLATE' => '',
		'COMPONENT_PARAMS'   => $arParamsComments + ['IS_AJAX' => 'Y'],
		'subBlockName'       => 'comments-parent',
		'style'              => ''
	]
], $component, ["HIDE_ICONS" => "Y"]);

*/
?>