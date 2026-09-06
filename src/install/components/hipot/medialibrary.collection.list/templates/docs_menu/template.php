<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
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

<?if (count($arResult['COLLECTIONS']) > 0) {?>
<div class="wr-block">
	<h4>Библиотека документов</h4>
	<div class="gal-block">
		<ul>
			<?
			$cd = '/cabinet/docs/';
			foreach ($arResult['COLLECTIONS'] as $col) {?>
				<?if ($col['ID'] == $arParams["SELECTED"]) {?>
					<li><span><?=$col['NAME']?></span></li>
				<?} else {?>
					<li><a href="<?=$cd?><?=$col['ID']?>/"><?=$col['NAME']?></a></li>
				<?}?>
			<?}?>
		</ul>
	</div><!--gal-block-->
</div><!--wr-block-->
<?}?>
