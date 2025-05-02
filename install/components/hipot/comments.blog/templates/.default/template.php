<?php
defined('B_PROLOG_INCLUDED') || die();
// region var_templ
/**
 * @var array $arParams
 * @var array $arResult
 * @global CMain $APPLICATION
 * @global CUser $USER
 * @global CDatabase $DB
 * @var CBitrixComponentTemplate $this
 * @var string $componentPath
 * @var string $templateName
 * @var string $templateFile
 * @var string $templateFolder
 * @var array $templateData
 * @var CBitrixComponent $component
 */
// endregion
$this->setFrameMode(true);

$params = [
	"ID"                           => $arResult['arPost']['ID'],
	"BLOG_URL"                     => $arResult['arBlog']["URL"],
	"COMMENTS_COUNT"               => $arParams["BLOG_POST_COMMENTS_COUNT"],
	"DATE_TIME_FORMAT"             => "d.m.Y H:i:s",
	"SMILES_COUNT"                 => "4",
	"IMAGE_MAX_WIDTH"              => 600,
	"IMAGE_MAX_HEIGHT"             => 600,
	"EDITOR_RESIZABLE"             => "Y",
	"EDITOR_DEFAULT_HEIGHT"        => "200",
	"EDITOR_CODE_DEFAULT"          => "N",
	"PATH_TO_BLOG"                 => $arResult['IBLOCK_ELEMENT']['DETAIL_PAGE_URL'],
	"PATH_TO_USER"                 => $arResult['IBLOCK_ELEMENT']['DETAIL_PAGE_URL'],
	"CACHE_TYPE"                   => "A",
	"CACHE_TIME"                   => $arParams["CACHE_TIME"],
	"PATH_TO_SMILE"                => "",
	"SIMPLE_COMMENT"               => "Y",
	"USE_ASC_PAGING"               => "N",
	"SHOW_RATING"                  => "N",
	"ALLOW_VIDEO"                  => "N",
	"SHOW_SPAM"                    => "Y",
	"NO_URL_IN_COMMENTS"           => "L",
	"NO_URL_IN_COMMENTS_AUTHORITY" => "",
	"BLOG_VAR"                     => "",
	"POST_VAR"                     => "",
	"USER_VAR"                     => "",
	"PAGE_VAR"                     => "pagen",
	"COMMENT_ID_VAR"               => "",
	"SEO_USER"                     => "Y",
	"NOT_USE_COMMENT_TITLE"        => "Y",
	"AJAX_POST"                    => "Y"
];
foreach ($arParams['BLOG_POST_COMMENT_PARAMS'] as $key => $value) {
	$params[$key] = $value;
}

$APPLICATION->IncludeComponent("bitrix:blog.post.comment", $arParams['BLOG_POST_COMMENT_TEMPLATE'], $params, $component, ['HIDE_ICONS' => 'Y']);
