<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 10.01.2022 22:10
 * @version pre 1.0
 */
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var array $templateData */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;

/* @var $c HipotAjaxComponent */
$c = $this->getComponent();
$ids = array_flip($c::IBLOCK_IDS);

Loc::loadMessages(Loader::getDocumentRoot() . SITE_TEMPLATE_PATH . '/header.php');

if ($arParams['MODE'] === 'init') {
	?>
	<div data-like-block data-id="<?=(int)$arParams['ITEM']['ID']?>" data-type="<?=$ids[(int)$arParams['ITEM']['IBLOCK_ID']]?>" data-lang="<?=LANGUAGE_ID?>"></div>
	<?
} else if ($arParams['MODE'] === 'load') {
	$item         = $arParams['ITEM'];
	$item["NAME"] = htmlspecialcharsback($item["NAME"]);
	?>
	<div class="num-likes" data-id-num-likes="<?=$arParams['ITEM']['ID']?>"><?=Loc::getMessage('POST_LIKED_CNT')?>&nbsp;<b><?=(int)$arParams['ITEM']['PROPERTY_' . $c::IBLOCK_LIKE_PROP_CODE . '_VALUE']?></b></div>
	<div class="ico-like-pt" data-id="<?=$arParams['ITEM']['ID']?>" data-type="<?=$ids[(int)$arParams['ITEM']['IBLOCK_ID']]?>"  data-lang="<?=LANGUAGE_ID?>"></div>
	<?
}
?>


