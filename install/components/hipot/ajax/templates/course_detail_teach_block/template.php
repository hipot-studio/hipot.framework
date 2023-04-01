<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 21.02.2022 00:42
 * @version pre 1.0
 */
defined('B_PROLOG_INCLUDED') || die();
/** @var array{PARAMS:array, IS_AJAX: string} $arParams */
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
use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset,
	Bitrix\Main\Localization\Loc;
$this->setFrameMode(true);

// file with all client handlers
Asset::getInstance()->addJs($componentPath . '/js/block_loader.js');
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
		<script type="text/javascript">
			<?
			// need re-run script
			echo file_get_contents(Loader::getDocumentRoot() . $templateFolder . '/ajax.script.min.js');?>
		</script>
		<?
		//$arParams['PARAMS']['COMPONENT']['CACHE_TIME'] = 0;

		ob_start();
		$APPLICATION->IncludeComponent(
			"bitrix:news.detail",
			$arParams['PARAMS']['COMPONENT']['COMPONENT_TEMPLATE'],
			$arParams['PARAMS']['COMPONENT'],
			$component
		);
		$fullComponentHtml = ob_get_clean();

		echo $APPLICATION->GetViewContent($arParams['PARAMS']['subBlockName']);
		?>

	<?}?>

<?if ($arParams['IS_AJAX'] != 'Y') {?>
</div>
<!--/noindex-->
<?}?>
