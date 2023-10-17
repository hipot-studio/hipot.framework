<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 29.01.2022 21:29
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

// dops from main template.php:
/** @var string $price */
/** @var string $formUrl */

use Hipot\Utils\Img,
	Bitrix\Main\Loader;

$courseFullContent = ($arResult['CODE'] == 'rki-online');

$bOneBlockShow     = ($arParams['IS_ONE_BLOCK_SHOW'] == 'Y');
if ($bOneBlockShow) {
	// ajax need full blocks content
	$courseFullContent = true;
}

$getTextPropertyIfIsset = static function ($propertyValue) {
	if (is_array($propertyValue) && isset($propertyValue['TEXT'])) {
		return $propertyValue['TEXT'];
	}
	return $propertyValue;
};
?>

<section class="container page-courses">
	<h1><?=(trim($arResult['PROPERTIES']['ND2022_NAME']['VALUE']) != '') ? $arResult['PROPERTIES']['ND2022_NAME']['VALUE'] : $arResult['~NAME']?></h1>

	<div class="top-text-course">
		<?=$getTextPropertyIfIsset($arResult['PROPERTIES']['ND2022_TOP_DESCR']['~VALUE'])?>
	</div>

	<?
	if ($courseFullContent) {
		?>
		<div class="b-state-course">
			<?
			if ($bOneBlockShow) {
				$this->SetViewTarget('b-state-course');
			}
			?>
			<div class="item-state-course">
				<?
				$img = (trim($arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['VALUE_FULL']['SRC']) != '')
					? $arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['VALUE_FULL']['SRC']
					: SITE_TEMPLATE_PATH . '/img/img-state1.jpg';

				$w = $arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['VALUE_FULL']['WIDTH'];
				$h = $arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['VALUE_FULL']['HEIGHT'];
				if (! $w) {
					[$w, $h] = getimagesize(Loader::getDocumentRoot() . $img);
				}
				?>
				<div class="img-state-course"><img <?if ($w) {?> width="<?=$w?>" height="<?=$h?>" <?}?>
						loading="lazy" class="lazy" data-src="<?=$img?>" alt="<?=trim($arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['DESCRIPTION'])?>"
						title="<?=trim($arResult['PROPERTIES']['ND2022_HOW_PASSED_PIC']['DESCRIPTION'])?>"></div>
				<div class="desc-state-course">
					<div class="tit-state-course"><?=GetMessage('HOW_PASSED_H2')?></div>
					<?=$arResult['PROPERTIES']['ND2022_HOW_PASSED']['~VALUE']['TEXT']?>
				</div>
			</div>

			<div class="item-state-course">
				<?
				$img = (trim($arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['VALUE_FULL']['SRC']) != '')
					? $arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['VALUE_FULL']['SRC']
					: SITE_TEMPLATE_PATH . '/img/img-state2.jpg';

				$w = $arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['VALUE_FULL']['WIDTH'];
				$h = $arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['VALUE_FULL']['HEIGHT'];
				if (! $w) {
					[$w, $h] = getimagesize(Loader::getDocumentRoot() . $img);
				}
				?>
				<div class="img-state-course"><img <?if ($w) {?> width="<?=$w?>" height="<?=$h?>" <?}?>
						loading="lazy" class="lazy" data-src="<?=$img?>" alt="<?=trim($arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['DESCRIPTION'])?>"
						title="<?=trim($arResult['PROPERTIES']['ND2022_HOW_CONTROL_PIC']['DESCRIPTION'])?>"></div>
				<div class="desc-state-course">
					<div class="tit-state-course"><?=(trim($arResult['PROPERTIES']['ND2022_HOW_CONTROL']['DESCRIPTION']) != '')
							? trim($arResult['PROPERTIES']['ND2022_HOW_CONTROL']['DESCRIPTION']) : GetMessage('HOW_CONTROL_H2')?></div>
					<?=$arResult['PROPERTIES']['ND2022_HOW_CONTROL']['~VALUE']['TEXT']?>
				</div>
			</div>

			<?if (isset($arResult['PROPERTIES']['ND2022_HOW_LONG']['~VALUE']['TEXT'])) {?>
				<div class="item-state-course">
					<div class="desc-state-course desc-state-course-last">
						<div class="tit-state-course"><?=(trim($arResult['PROPERTIES']['ND2022_HOW_LONG']['DESCRIPTION']) != '')
								? trim($arResult['PROPERTIES']['ND2022_HOW_LONG']['DESCRIPTION']) : GetMessage('HOW_LONG_H2')?></div>
						<?=$arResult['PROPERTIES']['ND2022_HOW_LONG']['~VALUE']['TEXT']?>
					</div>
				</div>
			<?}?>
			<?
			if ($bOneBlockShow) {
				$this->EndViewTarget();
			}
			?>
		</div>
	<?} else {
		$APPLICATION->IncludeComponent('mgu:ajax', 'course_detail_teach_block', [
			"PARAMS" => [
				'COMPONENT'     => $arParams + ['IS_ONE_BLOCK_SHOW' => 'Y', 'IS_AJAX' => 'Y'],
				'subBlockName'  => 'b-state-course',
				'style'         => 'style="min-height:600px"'
			]
		], $component, ["HIDE_ICONS" => "Y"]);
	}
	?>

	<?
	if (is_array($arResult['PROPERTIES']['ND2022_CONS']['LINK_ITEMS']) && count($arResult['PROPERTIES']['ND2022_CONS']['LINK_ITEMS']) > 0) {
		if ($courseFullContent) {
			?>
			<div class="b-benef-course">
				<?
				if ($bOneBlockShow) {
					$this->SetViewTarget('b-benef-course');
				}
				?>
				<div class="tit-cr"><?=trim($arResult['PROPERTIES']['ND2022_CONS_H2']['~VALUE']) != '' ? $arResult['PROPERTIES']['ND2022_CONS_H2']['~VALUE'] : GetMessage('ADV_DEFAULT_H2')?></div>

				<div class="list-benef-course">
					<?foreach ($arResult['PROPERTIES']['ND2022_CONS']['LINK_ITEMS'] as $k => $item) {
						?>
						<div class="item-benef-course">
							<div class="ico-bn-course">
								<?php
								$filename = Bitrix\Main\Loader::getDocumentRoot() . SITE_TEMPLATE_PATH . '/img/' . $item['PROPERTIES']['ICON']['VALUE_XML_ID'];
								$width = $height = '';
								if (is_file($filename)) {
									$xmlget        = simplexml_load_string(file_get_contents($filename));
									$xmlattributes = $xmlget->attributes();
									$width         = (int)$xmlattributes->width;
									$height        = (int)$xmlattributes->height;
									unset($xmlget);
								}
								if (trim($item['PROPERTIES']['ICON']['VALUE_XML_ID']) != '') {?><img src="<?=SITE_TEMPLATE_PATH?>/img/<?=$item['PROPERTIES']['ICON']['VALUE_XML_ID']?>" width="<?=$width?>" height="<?=$height?>"
										alt="<?=htmlspecialcharsbx($item['NAME'])?>" /><?}?>
							</div>
							<span><?=$item['NAME']?></span>
							<p><?=$item['PREVIEW_TEXT']?></p>
						</div>
					<?}?>
				</div>
				<?
				if ($bOneBlockShow) {
					$this->EndViewTarget();
				}
				?>
			</div>
			<?
		} else {
			$APPLICATION->IncludeComponent('hipot:ajax', 'course_detail_teach_block', [
				"PARAMS" => [
					'COMPONENT'     => $arParams + ['IS_ONE_BLOCK_SHOW' => 'Y', 'IS_AJAX' => 'Y'],
					'subBlockName'  => 'b-benef-course',
					'style'         => 'style="min-height:500px"'
				]
			], $component, ["HIDE_ICONS" => "Y"]);
		}
	}
	?>
</section>
