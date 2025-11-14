<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 10.01.2022 21:13
 * @version pre 1.0
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\Web\Json;
use Bitrix\Main\IO\Directory;
use Hipot\BitrixUtils\Iblock as IblockUtils;
use Bitrix\Main\ORM\Data\DataManager;
use Hipot\Services\BitrixEngine;

Loc::loadMessages(__FILE__);

/**
 * universal ajax controller component (with html-templates)
 * @copyright <info@hipot-studio.com>
 */
class HipotAjaxComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	protected ErrorCollection $errorCollection;

	// region fields to ajax-actions
	public const IBLOCK_IDS = [
		// @todo need add parameters
		'blog_ru' => 11
	];
	public const IBLOCK_LIKE_PROP_CODE = 'POST_LIKED_CNT';
	// endregion

	public function configureActions(): array
	{
		// Предустановленные фильтры находятся в папке main/lib/engine/actionfilter
		return [
			'setCookieWarningRead' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_GET]
					),
				],
				'postfilters' => []
			],
			'loadDynamicBlock' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					new ActionFilter\Csrf(),
				],
				'postfilters' => []
			],
			'loadIblockLikeTemplates' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					//new ActionFilter\Csrf(),
				],
				'postfilters' => []
			],
			'saveIblockLike' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					new ActionFilter\Csrf(),
				],
				'postfilters' => []
			],
		];
	}

	protected function listKeysSignedParameters()
	{
		return [
			'PARAMS'
		];
	}

	public function onPrepareComponentParams($arParams)
	{
		$this->errorCollection = new ErrorCollection();
		Loader::includeModule('iblock');
		return $arParams;
	}

	///// region ajax-actions

	public function setCookieWarningReadAction($value = 'Y', $lang = LANGUAGE_ID): ?string
	{
		if ($value == 'Y') {
			BitrixEngine::getAppD0()->set_cookie("COOKIE_WARNING_READ", $value, time() + 60*60*24*350, '/', $_SERVER['HTTP_HOST'], true);
		} else {
			$this->errorCollection[] = new Error("Wrong value to set");
			return null;
		}
		return 'OK';
	}

	/**
	 * Load any html-block when its visible
	 *
	 * @param string  $blockName template name of this component
	 * @param ?string $lang
	 *
	 * @return array|null
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\IO\FileNotFoundException
	 */
	public function loadDynamicBlockAction(string $blockName, ?string $lang = LANGUAGE_ID): ?array
	{
		$existsTemplates = [];
		[$componentNamespace, $componentName] = explode(':', $this->getName());
		$checkDynamicBlockPaths = [
			'/local/templates/.default/components/' . $componentNamespace . '/' . $componentName,
			'/bitrix/templates/.default/components/' . $componentNamespace . '/' . $componentName,
			SITE_TEMPLATE_PATH . '/components/' . $componentNamespace . '/' . $componentName,
		];
		foreach ($checkDynamicBlockPaths as $checkPath) {
			$directory = new Directory(Loader::getDocumentRoot() . $checkPath);
			if ($directory->isExists()) {
				foreach ($directory->getChildren() as $child) {
					$existsTemplates[] = $child->getName();
				}
			}
		}
		if (! in_array($blockName, $existsTemplates)) {
			$this->errorCollection[] = new Error("Block is unknown to load");
			return null;
		}
		global $APPLICATION;

		$response = [];
		ob_start();
		$APPLICATION->IncludeComponent($this->getName(), $blockName, [
			'IS_AJAX' => 'Y',
			'PARAMS' => $this->arParams['PARAMS']       // Pass here params like curDir
		], null, ['HIDE_ICONS' => 'Y']);
		$response['HTML'] = ob_get_clean();

		return $response;
	}

	// region iblock_like template

	public function loadIblockLikeTemplatesAction($ids, $type, $lang = LANGUAGE_ID): ?array
	{
		$ids = array_filter($ids);
		if (count($ids) <= 0) {
			$this->errorCollection[] = new Error("No items passed");
			return null;
		}
		if (!isset(self::IBLOCK_IDS[$type])) {
			$this->errorCollection[] = new Error("Wrong type");
			return null;
		}
		$r = [];

		Loader::includeModule('iblock');
		$rs = CIBlockElement::GetList(['ID' => $ids], ['ID' => $ids, 'IBLOCK_ID' => self::IBLOCK_IDS[$type]], false, false, [
			'ID', 'IBLOCK_ID', 'NAME', 'DETAIL_PAGE_URL', 'PROPERTY_' . self::IBLOCK_LIKE_PROP_CODE
		]);
		while ($item = $rs->GetNext()) {
			$item['DETAIL_PAGE_URL'] = 'https://' . BitrixEngine::getInstance()->request->getServer()->getServerName() . $item['DETAIL_PAGE_URL'];
			$item["DETAIL_PAGE_URL"] = htmlspecialcharsbx($item["DETAIL_PAGE_URL"]);
			$item["NAME"]            = $item["~NAME"];

			ob_start();
			BitrixEngine::getAppD0()->IncludeComponent($this->getName(), 'iblock_like', [
				'ITEM'  => $item,
				'MODE'  => 'load'
			], false, ["HIDE_ICONS" => "Y"]);
			$html = ob_get_clean();

			$r[ $item['ID'] ] = [
				'CNT_P'   => (int)$item['PROPERTY_' . self::IBLOCK_LIKE_PROP_CODE . '_VALUE'],
				'HTML'    => $html
			];
		}
		return $r;
	}

	/** @noinspection GlobalVariableUsageInspection */
	public function saveIblockLikeAction($id, $type, $value = '+', $lang = LANGUAGE_ID): ?array
	{
		$id = (int)$id;
		if ($id <= 0) {
			$this->errorCollection[] = new Error("No item passed");
			return null;
		}
		if (!isset(self::IBLOCK_IDS[$type])) {
			$this->errorCollection[] = new Error("Wrong type");
			return null;
		}

		$prop = CIBlockElement::GetProperty(self::IBLOCK_IDS[$type], $id, 'sort', 'asc', ['CODE'  => self::IBLOCK_LIKE_PROP_CODE])->Fetch();
		$v = (int)$prop['VALUE'];

		if ($value === '+') {
			$v++;
		} else if ($value === '-') {
			$v--;
		}

		CIBlockElement::SetPropertyValuesEx($id, self::IBLOCK_IDS[$type], [
			self::IBLOCK_LIKE_PROP_CODE => $v
		]);

		$_SESSION['saveIblockLikeAction'][$id] = $v;

		return [
			'CNT_P' => (int)$_SESSION['saveIblockLikeAction'][$id]
		];
	}

	// endregion

	///// endregion

	public function executeComponent()
	{
		if ($this->arParams['ADD_BLOCK_LOADER_JS'] == 'Y') {
			$this->addLoaderBlockJs();
		}

		// only to show html-templates
		$this->includeComponentTemplate();
	}

	/**
	 * <pre>
	 * // block loader
	 * CBitrixComponent::includeComponentClass("hipot:ajax");
	 * if (class_exists(HipotAjaxComponent::class)) {
	 *      (new HipotAjaxComponent)->addLoaderBlockJs();
	 * }
	 * </pre>
	 * @return void
	 */
	public function addLoaderBlockJs(): void
	{
		// file with all client handlers
		static $blockJsLoaderAdded = false;
		if (!$blockJsLoaderAdded) {
			\CJSCore::Init(['ajax']);
			if ($this->getParent()) {
				$this->getParent()->addChildJS($this->getPath() . '/js/block_loader.js');
			}
			Asset::getInstance()->addJs($this->getPath() . '/js/block_loader.js');
			$blockJsLoaderAdded = true;
		}
	}

	/**
	 * Getting array of errors.
	 * @return Error[]
	 */
	public function getErrors()
	{
		return $this->errorCollection->toArray();
	}

	/**
	 * Getting once error with the necessary code.
	 *
	 * @param string $code Code of error.
	 *
	 * @return Error
	 */
	public function getErrorByCode($code)
	{
		return $this->errorCollection->getErrorByCode($code);
	}
}