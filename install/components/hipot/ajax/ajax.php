<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 23:38
 * @version pre 1.0
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\ActionFilter;
use Hipot\BitrixUtils\HiBlock;
use Hipot\Model\DataManagerReadModel;
use Bitrix\Main\Web\Json;

/**
 * universal ajax controller component
 * @copyright <info@hipot-studio.com>
 */
class HipotAjaxController extends Controller
{
	public function configureActions(): array
	{
		// Предустановленные фильтры находятся в папке main/lib/engine/actionfilter
		return [
			'getEntity' => [
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

	/**
	 * @return Parameter[]
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				DataManagerReadModel::class,
				'entity',
				function ($className, $entityType, $entityId) {
					return DataManagerReadModel::buildById(
						HiBlock::getHightloadBlockTable(false, $entityType, true),
						$entityId
					);
				}
			)
		];
	}

	///// region ajax-actions

	public function getEntityAction(DataManagerReadModel $entity)
	{
		return Json::encode($entity->getEntityObject());
	}

	///// endregion
}

