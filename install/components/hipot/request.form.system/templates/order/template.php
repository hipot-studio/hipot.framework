<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<div class="applicat static_form" id="<?=$arParams['POST_NAME']?>_form">

	<div class="title-aplicat">Заявка</div><!--title-aplicat-->
	
	<form method="post" enctype="multipart/form-data" class="form_pads">

	<? if ($_SESSION['READY_request_'.$arParams['POST_NAME']] == 'Y'):?>
		
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
		
		<div class="field">
			<label>Ф.И.О.<span>*</span></label>
			<input type="text" maxlength="450" class="req_inpt"
				name="<?=$arParams['POST_NAME']?>[fio]"
				value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['fio']) ? $arParams['_POST'][$arParams['POST_NAME']]['fio'] : '';?>"
			/>
		</div><!--field-->
		
		<div class="field">
			<label>Компания <span>*</span></label>
			<input type="text" maxlength="450" class="req_inpt"
				name="<?=$arParams['POST_NAME']?>[company]"
				value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['company']) ? $arParams['_POST'][$arParams['POST_NAME']]['company'] : '';?>"
			/>
		</div><!--field-->
		
		<div class="field">
			<label>Должность</label>
			<input type="text" maxlength="450"
				name="<?=$arParams['POST_NAME']?>[rank]"
				value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['rank']) ? $arParams['_POST'][$arParams['POST_NAME']]['rank'] : '';?>"
			/>
		</div><!--field-->
		
		<div class="field">
			<label>Телефон <span>*</span></label>
			<input type="text" maxlength="450"
				class="req_inpt phone"
				name="<?=$arParams['POST_NAME']?>[phone]"
				value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['phone']) ? $arParams['_POST'][$arParams['POST_NAME']]['phone'] : '';?>"
			/>
		</div><!--field-->
		
		<div class="field">
			<label>Электронная почта</label>
			<input type="text" maxlength="450"
				class="email_inpt"
				name="<?=$arParams['POST_NAME']?>[mail]"
				value="<?=($arParams['_POST'][ $arParams['POST_NAME'] ]['mail']) ? $arParams['_POST'][$arParams['POST_NAME']]['mail'] : '';?>"
			/>
		</div><!--field-->
		
		<div class="field">
			<label>Текст сообщения <span>*</span></label>
			<textarea class="req_inpt"
				name="<?=$arParams['POST_NAME']?>[message]"
				><?=($arParams['_POST'][ $arParams['POST_NAME'] ]['message']) ? $arParams['_POST'][$arParams['POST_NAME']]['message'] : '';?></textarea>
		</div><!--field-->
			
				
		<div class="field-obyaz"><span>*</span> — заполните обязательно</div><!--field-obyaz-->
		
		<div class="file">
			<div class="type_file">
				<input type="file" size="17" class="inputFile" name="file" />
				<div class="fonTypeFile"></div>
				<input type="text" class="inputFileVal" name="filename" readonly="readonly" />
			</div>
		</div><!--file-->
		
		<div class="button submit">Отправить заявку</div><!--button-->
		
	<?endif;?>
	</form>
			

</div><!-- applicat -->

