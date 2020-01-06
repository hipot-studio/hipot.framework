<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

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


?>

<script src="<?=CUtil::GetAdditionalFileURL('/local/templates/order_v2/js/jquery.formstyler.min.js')?>"></script>
<link rel="stylesheet" href="<?=CUtil::GetAdditionalFileURL('/local/templates/order_v2/css/jquery.formstyler.css')?>">
<link rel="stylesheet" href="<?=CUtil::GetAdditionalFileURL('/local/templates/order_v2/css/jquery.formstyler.theme.css')?>">
<script src="<?=CUtil::GetAdditionalFileURL('/local/templates/order_v2/js/jquery.maskedinput-1.2.2.js')?>"></script>


<?if ($arParams['ORDER_ID']) {?>
	<div class="success_answer">Спасибо, Ваш заказ #<?=$arParams['ORDER_ID']?> принят! Наши менеджеры в ближайшее время свяжутся с Вами.</div>
<?} else {
	$APPLICATION->IncludeComponent(
		"wellmood:sale.basket.basket",
		"bootstrap_v5",
		array(
			"ACTION_VARIABLE" => "basketAction",
			"ADDITIONAL_PICT_PROP_18" => "-",
			"ADDITIONAL_PICT_PROP_7" => "-",
			"AUTO_CALCULATION" => "Y",
			"BASKET_IMAGES_SCALING" => "adaptive",
			"COLUMNS_LIST_EXT" => array(
				0 => "PREVIEW_PICTURE",
				1 => "DELETE",
				2 => "DELAY",
				3 => "SUM",
				4 => "PROPERTY_ART",
				5 => "PROPERTY_COLOR",
				6 => "PROPERTY_SIZE",
				7 => "PROPERTY_DISCOUNT_DIFF_PERCENT",
				//8 => "PROPERTY_PRICE",
			),
			"COMPATIBLE_MODE" => "Y",
			/*"COMPOSITE_FRAME_MODE" => "A",
			"COMPOSITE_FRAME_TYPE" => "AUTO",*/
			"CORRECT_RATIO" => "Y",
			"GIFTS_BLOCK_TITLE" => "Выберите один из подарков",
			"GIFTS_CONVERT_CURRENCY" => "N",
			"GIFTS_HIDE_BLOCK_TITLE" => "N",
			"GIFTS_HIDE_NOT_AVAILABLE" => "N",
			"GIFTS_MESS_BTN_BUY" => "Выбрать",
			"GIFTS_MESS_BTN_DETAIL" => "Подробнее",
			"GIFTS_PAGE_ELEMENT_COUNT" => "4",
			"GIFTS_PLACE" => "BOTTOM",
			"GIFTS_PRODUCT_PROPS_VARIABLE" => "prop",
			"GIFTS_PRODUCT_QUANTITY_VARIABLE" => "quantity",
			"GIFTS_SHOW_DISCOUNT_PERCENT" => "Y",
			"GIFTS_SHOW_OLD_PRICE" => "N",
			"GIFTS_TEXT_LABEL_GIFT" => "Подарок",
			"HIDE_COUPON" => "Y",
			"OFFERS_PROPS" => array(
			),
			"PATH_TO_ORDER" => "/personal/order/make/",
			"PRICE_VAT_SHOW_VALUE" => "N",
			"QUANTITY_FLOAT" => "N",
			"SET_TITLE" => "Y",
			"USE_GIFTS" => "N",
			"USE_PREPAYMENT" => "N",
			"COMPONENT_TEMPLATE" => "bootstrap_v4",
			"DEFERRED_REFRESH" => "N",
			"USE_DYNAMIC_SCROLL" => "Y",
			"SHOW_FILTER" => "Y",
			"SHOW_RESTORE" => "Y",
			"COLUMNS_LIST_MOBILE" => array(
				0 => "PREVIEW_PICTURE",
				//1 => "DELETE",
				2 => "DELAY",
				3 => "SUM",
				4 => "PROPERTY_ART",
				5 => "PROPERTY_COLOR",
				6 => "PROPERTY_DISCOUNT_DIFF_PERCENT",
			),
			"TEMPLATE_THEME" => "blue",
			"TOTAL_BLOCK_DISPLAY" => array(
				0 => "bottom",
			),
			"DISPLAY_MODE" => "extended",
			"PRICE_DISPLAY_MODE" => "Y",
			"SHOW_DISCOUNT_PERCENT" => "Y",
			"DISCOUNT_PERCENT_POSITION" => "bottom-right",
			"PRODUCT_BLOCKS_ORDER" => "props,sku,columns",
			"USE_PRICE_ANIMATION" => "Y",
			"LABEL_PROP" => array(
			),
			"EMPTY_BASKET_HINT_PATH" => "/",
			"USE_ENHANCED_ECOMMERCE" => "Y",
		),
		$component
	);?>

	<?if (count($arResult['BASKET']) > 0) {?>

	<div class="order-right" data-templatefolder="<?=$this->GetFolder()?>">
		<div class="tab order-head">
			<span class="tablinks active" data-open="fast_order"><a href="javascript:void(0)"><?=GetMessage('CUSTOM_ORDER_FAST_TITLE')?></a></span>
			<span class="tablinks" data-open="full_order"><a href="javascript:void(0)"><?=GetMessage('CUSTOM_ORDER_FULL_TITLE')?></a></span>
		</div>

		<div id="fast_order" class="tabcontent active">
			<div class="fast-form">
				<form name="fast_form" method="post">
					<span class="inpt-cont required"><input type="text" value="<?=htmlspecialcharsbx($USER->GetFullName())?>" placeholder="Ф.И.О."
															name="PROPS_fast[FIO]" /></span>
					<span class="inpt-cont required"><input type="text" value="<?=htmlspecialcharsbx($arResult['arUser']['PERSONAL_PHONE'])?>" placeholder="Контактный телефон"
															name="PROPS_fast[PHONE]" class="phone_mask" /></span>
					<span class="inpt-cont"><input type="text" value="<?=htmlspecialcharsbx($USER->GetEmail())?>" placeholder="E-mail"
															name="PROPS_fast[EMAIL]" /></span>
					<label><input type="checkbox" name="PROPS_fast[ORDER_STATUS_SMS]" checked value="Y" /> <?=GetMessage('CUSTOM_ORDER_AGREEMENT_SMS')?></label>
					<input data-form="fast_form" type="submit" value="Подтвердить заказ" />
					<input type="hidden" name="fast_form" value="Y">
				</form>
			</div>
		</div>

		<div id="full_order" class="tabcontent">
			<div class="full-form">
				<form name="full_form" method="post">
					<span class="inpt-cont required"><input type="text" value="<?=htmlspecialcharsbx($USER->GetFullName())?>" placeholder="Ф.И.О."
															name="PROPS[FIO]" /></span>
					<span class="inpt-cont required"><input type="text" value="<?=htmlspecialcharsbx($arResult['arUser']['PERSONAL_PHONE'])?>" placeholder="Контактный телефон"
															name="PROPS[PHONE]" class="phone_mask" /></span>
					<span class="inpt-cont required"><input type="text" value="<?=htmlspecialcharsbx($USER->GetEmail())?>" placeholder="E-mail"
															name="PROPS[EMAIL]" /></span>
					<label><input type="checkbox" name="PROPS[ORDER_STATUS_SMS]" checked value="Y" /> <?=GetMessage('CUSTOM_ORDER_AGREEMENT_SMS')?></label>
					<input type="hidden" name="full_form" value="Y">


					<input type="hidden" name="PROPS[CITY]" value="" />
					<label style="position:relative; z-index:40;"><span class="city"><?=GetMessage('CUSTOM_ORDER_CITY_TITLE')?>:</span>
					<?
					ob_start();
					$APPLICATION->IncludeComponent(
						"bitrix:sale.location.selector.search",
						"",
						Array(
							"COMPONENT_TEMPLATE" => ".default",
							"ID" => "84",
							"CODE" => "PROPS[DELIVERY]",
							"INPUT_NAME" => "PROPS[CITY]",
							"PROVIDE_LINK_BY" => "id",
							"JSCONTROL_GLOBAL_ID" => "",
							"JS_CALLBACK" => "refreshPostage()",
							"FILTER_BY_SITE" => "Y",
							"SHOW_DEFAULT_LOCATIONS" => "Y",
							"CACHE_TYPE" => "A",
							"CACHE_TIME" => "36000000",
							"FILTER_SITE_ID" => "s1",
							"INITIALIZE_BY_GLOBAL_EVENT" => "",
							"SUPPRESS_ERRORS" => "N"
						)
					);
					$delivery = ob_get_clean();
					echo $delivery;
					?>

					<p style="font-size: 14px; color: #000; padding-top:20px;">&nbsp; Доставка  </p>

					<?
					if (count($arResult['PROPS']['DELIVERY']['VARIANTS']) > 0) {
						$k = true;
						foreach ($arResult['PROPS']['DELIVERY']['VARIANTS'] as $ki => $delVar) {

							//$deliveryId = $arResult['PROPS']['DELIVERY']['IDS'][$ki];
							$deliveryId = $delVar['ID'];
							if (! $deliveryId) {
								continue;
							}
							?>
							<label class="radio"><input <?=$k ? ' checked ': '';?> type="radio" name="PROPS[DELIVERY]" value="<?=$deliveryId?>"><?=$delVar['NAME']?>
								<br><small style="color:#888"><?=$delVar['DESCRIPTION']?></small></label>
							<?
							$k = false;
						}
					}?>

					<span class="delivery-cost-frm"><a href="javascript:void(0);">Узнать о стоимости доставки</a></span>

					<input type="hidden" name="PROPS[COUNTRY_INPUT_NAME]" id="COUNTRY">
					<input type="hidden" name="PROPS[LOCATION_INPUT_NAME]" id="LOCATION">
					<input type="hidden" name="PROPS[COUNTRY_INPUT_NAME]" id="REGION">

					<div class="delivery_simple_calc">
						<?
						ob_start();
						/*$APPLICATION->IncludeComponent(
							"bitrix:sale.ajax.locations",
							".default",
							array(
								"AJAX_CALL" => "N",
								"COUNTRY_INPUT_NAME" => "COUNTRY",
								"CITY_INPUT_NAME" => "LOCATION",
								"REGION_INPUT_NAME" => "REGION",
								"CITY_OUT_LOCATION" => "Y",
								"ONCITYCHANGE" => "refreshPostage()",
								"LOCATION_VALUE" => $_SESSION['TF_LOCATION_SELECTED_CITY'],
							),
							$component
						);*/
						$APPLICATION->IncludeComponent(
							"bitrix:sale.location.selector.search",
							"",
							Array(
								"COMPONENT_TEMPLATE" => ".default",
								"ID" => "84",
								"CODE" => "PROPS[LOCATION_INPUT_NAME]",
								"INPUT_NAME" => "LOCATION",
								"PROVIDE_LINK_BY" => "id",
								"JSCONTROL_GLOBAL_ID" => "",
								"JS_CALLBACK" => "refreshPostage()",
								"FILTER_BY_SITE" => "Y",
								"SHOW_DEFAULT_LOCATIONS" => "Y",
								"CACHE_TYPE" => "A",
								"CACHE_TIME" => "36000000",
								"FILTER_SITE_ID" => "s1",
								"INITIALIZE_BY_GLOBAL_EVENT" => "",
								"SUPPRESS_ERRORS" => "N"
							)
						);
						$delivery = ob_get_clean();
						echo $delivery;
						?>
						<div class="postage">
							<img src="/local/components/wellmood/custom.order/templates/.default/images/ajax-loader.gif" alt=" Минутку... " title=" "/>
						</div>
						<a href="/services/delivery/" target="_blank">Подробнее о доставке</a>
					</div>

					<span class="inpt-cont required"><input type="text" placeholder="Адрес доставки" name="PROPS[ADDRESS]" class="adress" /></span>
					<span class="delivery-cost comment"><a href="javascript:void(0)">Добавить комментарий</a></span>
					<div class="inpt-cont text-comm"><textarea name="PROPS[USER_DESCRIPTION]" value="" placeholder="комментарий к заказу"></textarea></div>

					<span class="pay"><?=GetMessage('CUSTOM_ORDER_PAY_TITLE')?></span>
					<?if (is_array($arResult['PROPS']['PAY']['VARIANTS']) && !empty($arResult['PROPS']['PAY']['VARIANTS'])) {
						$k = true;
						foreach ($arResult['PROPS']['PAY']['VARIANTS'] as $payVar) {?>
							<label class="radio"><input <?=$k ? ' checked ': '';?> type="radio" name="PROPS[PAY]" value="<?=$payVar['ID']?>"><?=$payVar['NAME']?>
								<br><small style="color:#888"><?=$payVar['DESCRIPTION']?></small></label>
							<?
							$k = false;
						}
					}?>
					<input data-form="full_form" type="submit"  value="Подтвердить заказ" />
					<?//ShowError('В данный момент мы исправляем полную процедуру. Скоро все заработает ;)')?>
					<span class="description"><?=GetMessage('CUSTOM_ORDER_FULL_DESCRIPTION')?></span>
				</form><?//end form?>
			</div>
		</div>
	</div>
	<?}
}?>