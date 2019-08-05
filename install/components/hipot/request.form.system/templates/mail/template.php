<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();?>
<?
/**
 * пример динамичного почтового шаблона
 */
?>
<table>
	<tr>
		<td>Имя</td>
		<td><?=$arResult['MAIL_VARS']['name']?></td>
	</tr>
	<tr>
		<td>Телефон</td>
		<td><?=$arResult['MAIL_VARS']['phone']?></td>
	</tr>
	<tr>
		<td>E-mail</td>
		<td><?=$arResult['MAIL_VARS']['mail']?></td>
	</tr>
	<tr>
		<td>Комментарий</td>
		<td><?=$arResult['MAIL_VARS']['comment']?></td>
	</tr>
</table>