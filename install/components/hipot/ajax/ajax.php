<?php /** @noinspection AutoloadingIssuesInspection */
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 23:38
 * @version pre 1.0
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Web\Json;

use Hipot\BitrixUtils\HiBlock;
use Hipot\Model\DataManagerReadModel;
use Hipot\Model\HiBaseModel;

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
			'getEntityStat' => [
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
				static function ($className, $entityType, $entityId) {
					$entityClass = HiBlock::getHightloadBlockTable(0, $entityType, true);
					return DataManagerReadModel::buildById(
						$entityClass,
						$entityId
					);
				}
			)
		];
	}

	///// region ajax-actions

	public function getEntityStatAction(string $entityType, array $entityOrder, array $filter = []): string
	{
		$entityClass = HiBlock::getHightloadBlockTable(0, $entityType, true);
		$classModelName = HiBaseModel::getModelClass($entityClass);

		$filter = array_merge($filter, DataManagerReadModel::getDefaultFilter($classModelName));

		$orderBy = key($entityOrder);
		$orderOrder = current($entityOrder);
		$cacheTtl = 3600 * 24 * 7;

		[$resultStart, $resultEnd, $resultTotal] = HiBaseModel::getStatistic($orderBy, $orderOrder, $filter, $cacheTtl, $entityClass);

		return Json::encode([
			'START_ID'      => $resultStart['ID'],
			'END_ID'        => $resultEnd['ID'],
			'TOTAL_ROWS'    => $resultTotal['CNT']
		]);
	}

	public function getEntityAction(DataManagerReadModel $entity): string
	{
		return Json::encode($entity->getEntityObject());
	}

	///// endregion
}
