<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 10.01.2022 21:13
 * @version pre 1.0
 */
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Errorable;
use Bitrix\Main\Error;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Web\Json;
use Hipot\BitrixUtils\IblockUtils;
use Bitrix\Main\IO\Directory;

Loc::loadMessages(__FILE__);

/**
 * ajax controller for mgu site
 * @copyright <info@hipot-studio.com>
 */
class HipotAjaxControllerComponent extends \CBitrixComponent implements Controllerable, Errorable
{
	protected ErrorCollection $errorCollection;

	public function configureActions()
	{
		// Предустановленные фильтры находятся в папке main/lib/engine/actionfilter
		return [
			'loadIblockLikeTemplates' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					new ActionFilter\Csrf(),
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
			'setCookieWarningRead' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_GET]
					),
				],
				'postfilters' => []
			],
			'checkLibraryPassword' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					new ActionFilter\Csrf(),
				],
				'postfilters' => []
			],
			'getVideoHtmlCode' => [
				'prefilters' => [
					new ActionFilter\HttpMethod(
						[ActionFilter\HttpMethod::METHOD_POST]
					),
					new ActionFilter\Csrf(),
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
			global $APPLICATION;
			$APPLICATION->set_cookie("COOKIE_WARNING_READ", $value, time() + 60*60*24*350, '/', $_SERVER['HTTP_HOST'], true);
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
		$directory = new Directory(Loader::getDocumentRoot() . '/local/templates/.default/components/hipot/ajax');
		if ($directory->isExists()) {
			foreach ($directory->getChildren() as $child) {
				$existsTemplates[] = $child->getName();
			}
		}

		if (! in_array($blockName, $existsTemplates)) {
			$this->errorCollection[] = new Error("Block is unknown to load");
			return null;
		}
		global $APPLICATION;

		$response = [];
		ob_start();
		$APPLICATION->IncludeComponent('hipot:ajax', $blockName, [
			'IS_AJAX' => 'Y',
			'PARAMS' => $this->arParams['PARAMS']       // Pass here params like curDir
		], null, ['HIDE_ICONS' => 'Y']);
		$response['HTML'] = ob_get_clean();

		return $response;
	}

	///// endregion

	public function executeComponent()
	{
		// only to show html-templates
		$this->includeComponentTemplate();
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