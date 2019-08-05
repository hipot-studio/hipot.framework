<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<?// эти две дивки вместо тега form ?>
<div class="ajax_form" id="<?=$arParams['POST_NAME']?>_form">
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
		
		<?=bitrix_sessid_post();?>
		
		<label for="name"><?=GetMessage('RT_name')?> <span class="req">*</span></label>
		<br />
		<input type="text" maxlength="450"
			class="req_inpt"
			name="<?=$arParams['POST_NAME']?>[name]"
			value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['name']) ? $arParams['_POST'][$arParams['POST_NAME']]['name'] : '';?>"
		/>
		
		<label for="mail"><?=GetMessage('RT_mail')?> <span class="req">*</span></label>
		<br />
		<input type="text" maxlength="450"
			class="req_inpt email_inpt"
			name="<?=$arParams['POST_NAME']?>[mail]"
			value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']) ? $arParams['_POST'][$arParams['POST_NAME']]['mail'] : '';?>"
		/>
		
	<?endif;?>
	
	

</div>
</div>

