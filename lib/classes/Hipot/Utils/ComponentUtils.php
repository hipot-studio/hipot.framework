<?php
namespace Hipot\Utils;

use Bitrix\Main\Loader;
use Hipot\BitrixUtils\IblockUtils;
use CIBlock;
use CBitrixComponentTemplate;
use CBitrixComponent;

trait ComponentUtils
{

	/**
	 * Добавляет иконки включаемых областей для компонента Битрикс
	 * можно использовать в component_epilog.php
	 *
	 * @param CBitrixComponent $component Компонент Битрикс
	 * @param array{'IBLOCK_ID':int, 'ELEMENT_ID':int, 'SECTION_ID':int} $arParams [] Массив параметров для компонента
	 * @param array{
	 *     'SECTION_BUTTONS':bool,
	 *     'SESSID':bool,
	 *     'SHOW_CATALOG_BUTTONS':bool,
	 *     'USE_CATALOG_BUTTONS':array,
	 *     'LABELS':array
	 * } $arOptions []
	 *
	 * @see \CIBlock::GetPanelButtons()
	 * @see \CIBlock::GetComponentMenu()
	 */
	public static function setComponentEdit(CBitrixComponent $component, array $arParams = [], array $arOptions = []): void
	{
		global $USER, $APPLICATION; // todo
		if ($USER->IsAuthorized() && $APPLICATION->GetShowIncludeAreas() && Loader::includeModule("iblock")) {
			$arButtons = CIBlock::GetPanelButtons(
				(int)$arParams["IBLOCK_ID"],
				(int)$arParams["ELEMENT_ID"],
				(int)$arParams["SECTION_ID"],
				$arOptions
			);
			$component->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
		}
	}

	/**
	 * Sets the element edit actions for a CBitrixComponentTemplate instance.
	 *
	 * This method adds the edit and delete actions for an element to the given template.
	 *
	 * @param CBitrixComponentTemplate $template The template instance to set the actions on.
	 * @param array &                  $arElement The element array to set the actions for.
	 * @param string                   $confirmDelete The confirm delete message.
	 *
	 * @return void
	 *
	 * @see \CBitrixComponentTemplate::AddEditAction()
	 * @see \CBitrixComponentTemplate::AddDeleteAction()
	 */
	public static function setElementEdit(CBitrixComponentTemplate $template, &$arElement, string $confirmDelete): void
	{
		// edit buttons block
		IblockUtils::setElementPanelButtons($arElement);
		$template->AddEditAction($arElement['ID'], $arElement['EDIT_LINK'], CIBlock::GetArrayByID($arElement["IBLOCK_ID"], "ELEMENT_EDIT"));
		$template->AddDeleteAction($arElement['ID'], $arElement['DELETE_LINK'], CIBlock::GetArrayByID($arElement["IBLOCK_ID"], "ELEMENT_DELETE"), ["CONFIRM" => $confirmDelete]);
	}

	/**
	 * Sets the edit buttons for a section in a Bitrix component template.
	 *
	 * This method adds edit and delete actions to the template for a section, using the CBitrixComponentTemplate
	 * class. It also sets the confirm delete message for the delete action.
	 *
	 * @param CBitrixComponentTemplate $template The Bitrix component template to set the edit buttons for.
	 * @param array &                  $arSection The array representing the section to set the edit buttons for.
	 * @param string                   $confirmDelete The confirm delete message for the delete action.
	 *
	 * @return void
	 *
	 * @see IblockUtils::setSectionPanelButtons()
	 * @see CBitrixComponentTemplate::AddEditAction()
	 * @see CBitrixComponentTemplate::AddDeleteAction()
	 */
	public static function setSectionEdit(CBitrixComponentTemplate $template, &$arSection, string $confirmDelete): void
	{
		// edit buttons block
		IblockUtils::setSectionPanelButtons($arSection);
		$template->AddEditAction($arSection['ID'], $arSection['EDIT_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "SECTION_EDIT"));
		$template->AddDeleteAction($arSection['ID'], $arSection['DELETE_LINK'], CIBlock::GetArrayByID($arSection["IBLOCK_ID"], "SECTION_DELETE"), ["CONFIRM" => $confirmDelete]);
	}

	/**
	 * Returns the value of id attribute for the edit area
	 *
	 * @param CBitrixComponentTemplate $template The template object
	 * @param array                    $entity The entity array containing ID attribute
	 *
	 * @return string The value of id attribute
	 * @see CBitrixComponentTemplate::GetEditAreaId()
	 */
	public static function getEditAreaAttrId(CBitrixComponentTemplate $template, array $entity): string
	{
		return 'id="'. $template->GetEditAreaId($entity['ID']) . '"';
	}

	/**
	 * Возвращает код, сгенерированный компонентом Битрикс
	 * @param string $name Имя компонента
	 * @param string $template Шаблон компонента
	 * @param array $params Параметры компонента
	 * @param mixed $componentResult Данные, возвращаемые компонентом
	 * @return string
	 * @see \CMain::IncludeComponent()
	 */
	public static function getComponent($name, $template = '', $params = [], &$componentResult = null): string
	{
		/** @var $GLOBALS array{'DB':\CDatabase, 'APPLICATION':\CMain, 'USER':\CUser, 'USER_FIELD_MANAGER':\CUserTypeManager, 'CACHE_MANAGER':\CCacheManager, 'stackCacheManager':\CStackCacheManager} */
		ob_start();
		$componentResult = $GLOBALS['APPLICATION']->IncludeComponent($name, $template, $params, null, [], true);
		return ob_get_clean();
	}

	/**
	 * Возвращает код, сгенерированный включаемой областью Битрикс
	 * @param string $path Путь до включаемой области
	 * @param array $params Массив параметров для подключаемого файла
	 * @param array $functionParams Массив настроек данного метода
	 * @return string
	 * @see \CMain::IncludeFile()
	 */
	public static function getIncludeArea($path, $params = [], $functionParams = []): string
	{
		/** @var $GLOBALS array{'DB':\CDatabase, 'APPLICATION':\CMain, 'USER':\CUser, 'USER_FIELD_MANAGER':\CUserTypeManager, 'CACHE_MANAGER':\CCacheManager, 'stackCacheManager':\CStackCacheManager} */
		ob_start();
		$GLOBALS['APPLICATION']->IncludeFile($path, $params, $functionParams);
		return ob_get_clean();
	}

	/**
	 * Подключает видео-проигрыватель битрикса
	 *
	 * @param string $videoFile
	 * @param int $width
	 * @param int $height
	 * @param \CBitrixComponent|null $component
	 * @param bool $adaptiveFixs = true установка ширины плеера согласно текущей ширины блока, но с соотношением переданной ширины/высоты
	 *
	 * @return void
	 */
	public static function insertVideoBxPlayer(string $videoFile, int $width = 800, int $height = 450, $component = null, bool $adaptiveFixs = true): void
	{
		global $APPLICATION;

		$videoId = 'video_' . md5($videoFile . randString());
		if ($adaptiveFixs) {
			?>
			<script>
				BX.ready(() => {
					<?php
					/*
					BX.addCustomEvent('PlayerManager.Player:onAfterInit', (player) => {
						if (typeof $(player.getElement()).data('resize_koef') === 'undefined') {
							$(player.getElement()).data('resize_koef', $(player.getElement()).height() / $(player.getElement()).width()).css({
								'width': '100%',
							});
						}
						$(window).resize(() => {
							$(player.getElement()).css({
								'width' : '100%',
								'height': $(player.getElement()).width() * $(player.getElement()).data('resize_koef')
							});
						}).resize();
					});
					*/?>
					BX.addCustomEvent('PlayerManager.Player:onBeforeInit', (player) => {
						// https://docs.videojs.com/player#fluid
						player.params['fluid'] = true;
					});
				});
			</script>
			<?
		}

		$APPLICATION->IncludeComponent(
			"bitrix:player",
			"",
			[
				//"PREVIEW" => "",
				"ADVANCED_MODE_SETTINGS" => "N",
				"AUTOSTART" => "N",
				"AUTOSTART_ON_SCROLL" => "N",

				"WIDTH" => $width,
				"HEIGHT" => $height,
				'PLAYER_ID' => $videoId,
				"PATH" => trim($videoFile),

				"MUTE" => "N",
				"PLAYBACK_RATE" => "1",
				"PLAYER_TYPE" => "auto",
				"PRELOAD" => "N",
				"BUFFER_LENGTH" => "15",
				"REPEAT" => "none",
				"SHOW_CONTROLS" => "Y",
				"SIZE_TYPE" => "absolute",
				"SKIN" => "",
				"SKIN_PATH" => "/bitrix/js/fileman/player/videojs/skins",
				"START_TIME" => "0",
				"VOLUME" => 60,

			], $component, ["HIDE_ICONS" => "Y"]
		);
	}
}