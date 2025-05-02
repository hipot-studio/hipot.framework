<?php
namespace Hipot\Components;

use Bitrix\Main\Loader,
	Hipot\Services\BitrixEngine,
	Hipot\BitrixUtils\PhpCacher;

/**
 * Example:
 * <code>
 * $APPLICATION->IncludeComponent('hipot:comments.blog', '', [
 * // region main params
 * 'BLOG_ID'        => BLOG_ID_COMMENTS_TO_TEACH_RU_BLOG_IB,
 * 'BLOG_POST_ID'   => $templateData['BLOG_POST_ID'],
 * 'IBLOCK_ELEMENT' => [
 * 'ID'        => $arResult['ID'],
 * 'IBLOCK_ID' => $arResult['IBLOCK_ID'],
 * 'NAME'      => $arResult['NAME'],
 * ],
 * 'LINK_IB_PROP_CODE' => 'BLOG_POST_ID',
 * // endregion
 * // region visual params
 * 'BLOG_POST_COMMENT_TEMPLATE' => '.default',
 * 'BLOG_POST_COMMENTS_COUNT'   => 20,
 * 'BLOG_POST_COMMENT_PARAMS'   => [
 * ],
 * // endregion
 * 'CACHE_TIME'     => 3600 * 24 * 7,
 * ], null, ['HIDE_ICONS' => 'Y']);
 * </code>
 */
class CommentsBlog extends \CBitrixComponent
{
	public const string DEFAULT_PROPERTY_LINK_CODE = 'BLOG_POST_ID';

	public function onPrepareComponentParams($arParams)
	{
		$arParams['BLOG_ID']      = (int)$arParams['BLOG_ID'];
		$arParams["BLOG_POST_ID"] = (int)$arParams["BLOG_POST_ID"];

		$arParams['IBLOCK_ELEMENT']['ID'] = (int)$arParams['IBLOCK_ELEMENT']['ID'];
		$arParams['IBLOCK_ELEMENT']['IBLOCK_ID'] = (int)$arParams['IBLOCK_ELEMENT']['IBLOCK_ID'];
		$arParams['IBLOCK_ELEMENT']['NAME'] ??= $arParams['IBLOCK_ELEMENT']['ID'];

		// код свойства в инфоблоке для привязок к постам в блоге
		if (! isset($arParams['LINK_IB_PROP_CODE'])) {
			$arParams['LINK_IB_PROP_CODE'] = self::DEFAULT_PROPERTY_LINK_CODE;
		}

		$arParams['CACHE_TIME'] = (int)$arParams['CACHE_TIME'];

		if (empty($arParams['BLOG_POST_COMMENT_TEMPLATE'])) {
			$arParams['BLOG_POST_COMMENT_TEMPLATE'] = '.default';
		}
		$arParams["BLOG_POST_COMMENTS_COUNT"] = (int)$arParams["BLOG_POST_COMMENTS_COUNT"] > 0 ? (int)$arParams["BLOG_POST_COMMENTS_COUNT"] : 20;
		$arParams['BLOG_POST_COMMENT_PARAMS'] = (array)$arParams['BLOG_POST_COMMENT_PARAMS'];

		return $arParams;
	}

	public function executeComponent()
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;

		if (!Loader::includeModule("iblock") || !Loader::includeModule("blog")) {
			$this->showError('Cant include required modules: iblock or blog, sorry (((');
			return;
		}
		if (empty($arParams["BLOG_ID"])) {
			$this->showError('Need blog_id to open blog, sorry (((');
			return;
		}

		// select blog
		$cacheId = 'hi_component_comments_blog' . PhpCacher::getCacheSubDirById($arParams["BLOG_ID"]);
		$arResult['arBlog'] = PhpCacher::cache($cacheId, $arParams['CACHE_TIME'], static function() use ($arParams) {
			return \CBlog::GetByID($arParams["BLOG_ID"]);
		});
		if (! $arResult['arBlog']) {
			$this->showError('Cant open blog, sorry (((');
			return;
		}

		// recheck if filled by this component to avoid dropping in the parent component cache
		if (empty($arParams['BLOG_POST_ID'])) {
			$linkProp = \CIBlockElement::GetProperty(
				$arParams['IBLOCK_ELEMENT']['IBLOCK_ID'], $arParams['IBLOCK_ELEMENT']['ID'],
				['SORT' => 'ASC'], ['CODE' => $arParams['LINK_IB_PROP_CODE']]
			)->Fetch();
			$arParams['BLOG_POST_ID'] = (int)$linkProp['VALUE'];
		}

		$this->getBlogPost();

		if (! $arResult["arPost"]['ID']) {
			if ($this->addBlogPost()) {
				$this->getBlogPost();
			} else {
				$this->showError('Cant add blog_post, sorry (((');
				return;
			}
		}

		if ($ex = BitrixEngine::getAppD0()->GetException()) {
			$arResult["error_msg"] .= $ex->GetString();
		}
		if (!empty($arResult["error_msg"])) {
			$this->showError($arResult["error_msg"]);
		}

		$this->setResultCacheKeys([]);
		$this->includeComponentTemplate();
	}

	////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	private function showError(string $error): void
	{
		ShowError($error);
	}

	private function getBlogPost(): void
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;
		// пытаемся выбрать пост в блоге, к которому пишутся комментарии
		if ($arParams['BLOG_POST_ID']) {
			$cacheId = 'hi_component_comments_blog_post' . PhpCacher::getCacheSubDirById($arParams["BLOG_POST_ID"]);
			$arResult["arPost"] = PhpCacher::cache($cacheId, $arParams['CACHE_TIME'], static function() use ($arParams) {
				$dbPosts = \CBlogPost::GetList([], [
					"ID"       => $arParams['BLOG_POST_ID'],
					"BLOG_ID"  => $arParams["BLOG_ID"]
				], false, false, ['ID', 'NUM_COMMENTS', 'BLOG_ID']);
				return $dbPosts->GetNext();
			});
		}
	}

	private function addBlogPost(): bool
	{
		$arParams =& $this->arParams;
		$arResult =& $this->arResult;
		// Добавление темы в блог
		$arFieldsNewBlogPost = [
			"TITLE"              => $arParams['IBLOCK_ELEMENT']["NAME"],
			"DETAIL_TEXT"        => $arParams['IBLOCK_ELEMENT']["NAME"],
			"BLOG_ID"            => $arResult['arBlog']['ID'],
			"AUTHOR_ID"          => $arResult['arBlog']['OWNER_ID'],
			"=DATE_CREATE"       => BitrixEngine::getInstance()->getConnection()?->getSqlHelper()->getCurrentDateTimeFunction(),
			"DATE_PUBLISH"       => ConvertTimeStamp(false, "FULL"),
			"ENABLE_TRACKBACK"   => 'N',
			"ENABLE_COMMENTS"    => 'Y',
		];
		$arParams['BLOG_POST_ID'] = \CBlogPost::Add($arFieldsNewBlogPost);
		if ((int)$arParams['BLOG_POST_ID'] > 0) {
			[$ibp, $propId] = $this->checkIfPropertyExists($arParams);
			if ($propId > 0) {
				\CIBlockElement::SetPropertyValuesEx($arParams['IBLOCK_ELEMENT']["ID"], $arParams['IBLOCK_ELEMENT']['IBLOCK_ID'], [
					$arParams['LINK_IB_PROP_CODE'] => $arParams['BLOG_POST_ID']
				]);
			} else {
				$this->showError('Cant add property for iblock_element, sorry ((( ' . $ibp->LAST_ERROR);
				return false;
			}
			return true;
		}
		return false;
	}

	private function checkIfPropertyExists(array $arParams): array
	{
		// check if property exists
		$ibp    = new \CIBlockProperty();
		$ibProp = $ibp::GetList(['ID' => 'ASC'], [
			'IBLOCK_ID' => $arParams['IBLOCK_ELEMENT']['IBLOCK_ID'],
			'CODE'      => $arParams['LINK_IB_PROP_CODE']
		])->Fetch();
		$propId = (int)$ibProp['ID'];
		if ($propId <= 0) {
			$propId = $ibp->Add([
				'CODE'          => $arParams['LINK_IB_PROP_CODE'],
				'IBLOCK_ID'     => $arParams['IBLOCK_ELEMENT']['IBLOCK_ID'],
				'SORT'          => 2000,
				'NAME'          => 'Linking to the blog topic with comments',
				'HINT'          => '(filled in automatically)',
				'ACTIVE'        => 'Y',
				'PROPERTY_TYPE' => \Bitrix\Iblock\PropertyTable::TYPE_NUMBER,
				'FILTRABLE'     => 'Y'
			]);
		}
		return [$ibp, $propId];
	}

	public static function clearBlogPosts(int $blogId): void
	{
		Loader::includeModule("blog");
		$dbPosts = \CBlogPost::GetList([], [
			"BLOG_ID"  => $blogId
		], false, false, ['ID']);
		while ($arPost = $dbPosts->GetNext()) {
			\CBlogPost::Delete($arPost['ID']);
		}
	}

	public static function clearNotLinkedBlogPosts(int $blogId, int $iblockId, string $propertyCode = self::DEFAULT_PROPERTY_LINK_CODE): void
	{
		Loader::includeModule("blog");
		Loader::includeModule("iblock");

		$arFilledPosts = [];
		$rs = \CIblockElement::GetList(['PROPERTY_' . $propertyCode => 'ASC'], ['IBLOCK_ID' => $iblockId], ['PROPERTY_' . $propertyCode]);
		while ($el = $rs->GetNext()) {
			$arFilledPosts[] = (int)$el['PROPERTY_' . strtoupper($propertyCode) . '_VALUE'];
		}

		$dbPosts = \CBlogPost::GetList(['ID' => 'ASC'], [
			"BLOG_ID"  => $blogId,
			'!ID' => $arFilledPosts
		], false, false, ['ID']);
		while ($arPost = $dbPosts->GetNext()) {
			\CBlogPost::Delete($arPost['ID']);
		}
	}

}