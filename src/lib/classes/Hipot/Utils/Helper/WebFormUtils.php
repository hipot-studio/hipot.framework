<?php

namespace Hipot\Utils\Helper;

use Bitrix\Main\Loader;
use Hipot\Types\UpdateResult;

trait WebFormUtils
{
	/**
	 * Получить форму вместе с ее полями и вариантами ответов.
	 *
	 * @param int  $webFormId Идентификатор веб-формы
	 * @param bool $includeAdditionalFields Включить дополнительные поля результата
	 * @param bool $includeInactive Включить неактивные поля и ответы
	 * @return array{
	 *     FORM: array<string, mixed>,
	 *     FIELDS: array<string, array<string, mixed>>
	 * }|null
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function getFormWithFields(
		int $webFormId,
		bool $includeAdditionalFields = false,
		bool $includeInactive = false,
	): ?array {
		if ($webFormId <= 0) {
			return null;
		}

		Loader::requireModule('form');

		$form = [];
		$questions = [];
		$answers = [];
		$dropdown = [];
		$multiselect = [];
		$result = \CForm::GetDataByID(
			$webFormId,
			$form,
			$questions,
			$answers,
			$dropdown,
			$multiselect,
			$includeAdditionalFields ? 'ALL' : 'N',
			$includeInactive ? 'Y' : 'N',
		);

		if ($result === false) {
			return null;
		}

		$fields = [];
		foreach ($questions as $sid => $question) {
			$fields[$sid] = $question + [
				'ANSWERS' => $answers[$sid] ?? [],
				'DROPDOWN' => $dropdown[$sid] ?? null,
				'MULTISELECT' => $multiselect[$sid] ?? null,
			];
		}

		return [
			'FORM' => $form,
			'FIELDS' => $fields,
		];
	}

	/**
	 * Добавить в модуль веб-формы в форму данные
	 *
	 * @param int   $WEB_FORM_ID id формы, для которой пришел ответ
	 * @param array $arrVALUES = <pre>array (
	 * [WEB_FORM_ID] => 3
	 * [web_form_submit] => Отправить
	 *
	 * [form_text_18] => aafafsfasdf
	 * [form_text_19] => q1241431342
	 * [form_text_21] => afsafasdfdsaf
	 * [form_textarea_20] =>
	 * [form_text_22] => fasfdfasdf
	 * [form_text_23] => 31243123412впывапвыапывпыв аывпывпыв
	 *
	 * 18, 19, 21 - ID ответов у вопросов https://yadi.sk/i/_9fwfZMvO2kblA
	 * )</pre>
	 *
	 * @return UpdateResult|false
	 * @throws \Bitrix\Main\LoaderException
	 */
	public static function formResultAddSimple(int $WEB_FORM_ID, array $arrVALUES = []): UpdateResult|false
	{
		Loader::requireModule('form');

		// add result like bitrix:form.result.new
		$arrVALUES['WEB_FORM_ID'] = $WEB_FORM_ID;
		if ($arrVALUES['WEB_FORM_ID'] <= 0) {
			return false;
		}
		$arrVALUES['web_form_submit'] = 'Отправить';

		$resultId = \CFormResult::Add($WEB_FORM_ID, $arrVALUES);
		if (!$resultId) {
			return false;
		}

		// send email notifications
		\CFormCRM::onResultAdded($WEB_FORM_ID, $resultId);
		\CFormResult::SetEvent($resultId);
		\CFormResult::Mail($resultId);

		return new UpdateResult([
			'RESULT' => $resultId,
			'STATUS' => UpdateResult::STATUS_OK,
		]);
	}
}
