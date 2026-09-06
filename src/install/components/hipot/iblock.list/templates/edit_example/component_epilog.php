<?php
defined('B_PROLOG_INCLUDED') || die();
// region var_comp
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponent $this */
/** @var CBitrixComponent $component */
// endregion

use Hipot\Utils\UUtils;

UUtils::setComponentEdit($this, [
	'IBLOCK_ID'     => $arParams['IBLOCK_ID'],
]);