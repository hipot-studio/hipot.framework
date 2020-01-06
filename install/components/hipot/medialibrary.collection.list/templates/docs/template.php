<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>

<?if (count($arResult['COLLECTIONS']) > 0) {?>
	<?foreach ($arResult['COLLECTIONS'] as $coll) {?>
		<h2><?=$coll['NAME']?></h2>
		<div class="instruction">
			<p><?=$coll['DESCRIPTION']?></p>
			
			<?if (count($coll['ITEMS']) > 0) {?>
			<ul>
				<?foreach ($coll['ITEMS'] as $file) {
					
					$className = 'none';
					if (preg_match('#docx?$#is', $file['PATH'])) {
						$className = 'doc';
					}
					if (preg_match('#pdf$#is', $file['PATH'])) {
						$className = 'pdf';
					}
					
					$ext = pathinfo($file['PATH'], PATHINFO_EXTENSION);
					?>
					<li class="<?=$className?>"><a href="<?=$file['PATH']?>"><?=$file['NAME']?>.</a>
						<span>(<?=ToUpper($ext)?>, <?=CFile::FormatSize($file['FILE_SIZE'], 1)?>)</span></li>
				<?}?>
			</ul>
			<?}?>
		</div><!--instruction-->
		<br /><br />
	<?}?>
<?}?>
