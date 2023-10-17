<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>


<?// эти две дивки вместо тега form ?>
<div class="ajax_form popup" id="<?=$arParams['POST_NAME']?>_form">
<div class="ajax_form_wrapper">

	<div class="title-popup">Заказ звонка</div><!--title-popup-->

	<? if ($_SESSION['READY_request_'.$arParams['POST_NAME']] == 'Y'):?>
		
		<script type="text/javascript">
		$(function(){
			ShowWin($('#<?=$arParams['POST_NAME']?>_form'));
			window.setTimeout(function(){HideWin($('#<?=$arParams['POST_NAME']?>_form'));}, 4000);
		});
		</script>
		
		<p class="request_ready"><?=GetMessage('CALL_FRM_OK_SEND')?></p>
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
		
		<p>Оставьте Ваш номер телефона и мы перезвоним Вам в ближайшее время.</p>
		<div class="form-message">
			
				<div class="field">
					<label>Ваш номер телефона <span>*</span></label>
					<input type="text" maxlength="450"
						class="req_inpt phone"
						name="<?=$arParams['POST_NAME']?>[phone]"
						value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['name']) ? $arParams['_POST'][$arParams['POST_NAME']]['name'] : '';?>"
					/>
				</div><!--field-->

				<div class="exsample">Например: +7 (495) 989-1-797</div>

				<div class="button2 submit">
					Заказать звонок
				</div>
			
		</div><!--form-message-->
		
		
	<?endif;?>
	
		
	<span class="esc close_btn">Esc</span>
	<a class="close close_btn" href="javascript:void(0)"></a>

</div><!-- ajax_form_wrapper -->
</div><!-- ajax_form -->

