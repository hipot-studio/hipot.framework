<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 26.01.2022 17:31
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
use Bitrix\Main\Page\Asset;

// file with all client handlers
Asset::getInstance()->addJs($componentPath . '/js/block_loader.js');
?>
<!--noindex-->
<?if ($arParams['IS_AJAX'] != 'Y') {?>
<div class="cookie js-cookie mobile_off" style="display:none"
     data-decomposed-block="Y"
     data-block="<?=$templateName?>"
	>
<?}?>

	<?if ($arParams['IS_AJAX'] == 'Y') {?>
		<script type="text/javascript">
			BX.loadScript('<?=CUtil::GetAdditionalFileURL($templateFolder . '/ajax.script.min.js')?>');
		</script>
		<div class="container">
		<div class="row">

			<div class="cookie-close js-cookie-close" data-lang="<?=LANGUAGE_ID?>">OK</div>
			<div class="cookie-text">
				<?=GetMessage('COOKIE_WARN_TEXT', [
					'#SITE_NAME#'          => $_SERVER['HTTP_HOST']
				])?>
			</div>

		</div>
		</div>
	<?}?>

<?if ($arParams['IS_AJAX'] != 'Y') {?>
</div>
<?}?>
<!--/noindex-->
