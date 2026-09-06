<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */

$this->setFrameMode(true);
?>


<?// эти две дивки вместо тега form
if ($arParams['AJAX_CALL'] != 'Y') {
	?>
	<div class="ajax_form" id="<?=$arParams['POST_NAME']?>_form">
<?}?>
	<div class="ajax_form_wrapper">

	<? if ($_SESSION['READY_request_'.$arParams['POST_NAME']] == 'Y'):?>
		
		<div class="request_ready"><?=GetMessage('CALL_FRM_OK_SEND')?></div>
		<?unset($_SESSION['READY_request_'.$arParams['POST_NAME']]);?>
	
	<? else:?>
		
		<?
		// вывод ошибок
		$err = '';
		foreach ($arResult["error"] as $er) {
			if ($er == '') {
				continue;
			}
			$err .= $er . '<br />';
		}
		if (trim($err) != '') {
			echo '<div class="alert-errors">';
			echo GetMessage('CALL_REQUIRE_ERRORS');
			echo $err;
			echo '</div>';
		}
		?>

		<input type="hidden" name="<?=$arParams['POST_NAME']?>[token]" value="<?=randString(20);?>" class="token">
		<?=bitrix_sessid_post();?>

		<input type="hidden" name="<?=$arParams['POST_NAME']?>[good_id]" value="<?=$arParams['ELEMENT_ID']?>">

		<div class="form-message">
			<span>+7</span>
			<input type="text"
				class="req_inpt phone"
				name="<?=$arParams['POST_NAME']?>[phone]"
				value="<?= $arParams['_POST'][ $arParams['POST_NAME'] ]['name'] ?: '';?>"
				placeholder="Заказать в один клик"
			/>
			<div class="button2 submit" title="Заказать звонок в один клик">OK</div>
		</div>
	<?endif;?>
</div><!-- ajax_form_wrapper -->
<?
if ($arParams['AJAX_CALL'] != 'Y') {
	?>
	</div><!-- ajax_form -->
<?}?>

