<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 11.04.2021 19:49
 * @version pre 1.0
 */
/**
 * @var string $str
 * @var array $arResult
 * @var int $id
 * @var string $message
 * @var string $preview
 * @var string $text
 * @var string $page
 * @var string $parameter
 * @var string $value
 */
?>
<!-- Js escape demo -->
<script>
	var mess = '<?=GetMessageJS("MESSAGE_ID")?>';
	var str = '<?=CUtil::JSEscape($str)?>';
	var arResult = <?=CUtil::PhpToJSObject($arResult)?>;
</script>

<!-- Html attributes and values  -->
<label for="<?=htmlspecialcharsbx($id)?>"
	onclick="<?=htmlspecialcharsbx("alert(\"" . CUtil::JSEscape($message) . "\")")?>"
	>
	<!-- Html between tags -->
	<?=htmlspecialcharsEx($preview)?>
</label>

<!-- html inside textarea -->
<textarea id="<?=htmlspecialcharsbx($id)?>"><?=htmlspecialcharsbx($text)?></textarea>

<!-- html "a" tag href attribute -->
<a href="<?
	echo htmlspecialcharsbx(
		CHTTP::urnEncode($page) . "?" . urlencode($parameter) . '=' . urlencode($value)
	)
?>"></a>
