<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

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
