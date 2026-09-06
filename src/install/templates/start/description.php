<?
use Bitrix\Main\Loader;

$arTemplate = [
	'NAME'          => basename(__DIR__),
	'DESCRIPTION'   => '',
	'SORT'          => '',
	'TYPE'          => '',
	'EDITOR_STYLES' => [
		str_replace(Loader::getDocumentRoot(), '', __DIR__) . '/stylesheets/editor_styles.css'
	]
];
?>