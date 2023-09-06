<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 23:38
 * @version pre 1.0
 */
defined('B_PROLOG_INCLUDED') || die();

use Bitrix\Main\Application;
use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Engine\AutoWire\ExactParameter;
use Bitrix\Main\Engine\ActionFilter;
use Bitrix\Main\Entity\Query;
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
	 * @return list<\Bitrix\Main\Engine\AutoWire\Parameter>
	 */
	public function getAutoWiredParameters(): array
	{
		return [
			new ExactParameter(
				DataManagerReadModel::class,
				'entity',
				function ($className, $entityType, $entityId) {
					// here you may autoload your custom class
					return DataManagerReadModel::buildById(
						HiBlock::getHightloadBlockTable(0, $entityType, true),
						$entityId
					);
				}
			)
		];
	}

	///// region ajax-actions

	public function getEntityStatAction(string $entityType, array $entityOrder, array $filter = []): string
	{
		$dm = HiBlock::getHightloadBlockTable(0, $entityType, true);

		$orderBy = key($entityOrder);
		$orderOrder = current($entityOrder);
		$orderOrderRev = 'DESC';
		if (strtoupper($orderOrder) == 'DESC') {
			$orderOrderRev = 'ASC';
		}

		$cacheTtl = 3600 * 24 * 7;

		$resultStart = $dm::getList([
			'order' => [$orderBy => $orderOrder],
			'limit' => 1,
			'select' => ['ID'],
			'filter' => $filter,
			"cache" => ["ttl" => $cacheTtl, "cache_joins" => true]
		])->fetch();
		$resultEnd = $dm::getList([
			'order' => [$orderBy => $orderOrderRev],
			'limit' => 1,
			'select' => ['ID'],
			'filter' => $filter,
			'cache' => ['ttl' => $cacheTtl, "cache_joins" => true]
		])->fetch();
		$whereSql = Query::buildFilterSql($dm::getEntity(), $filter);
		$resultTotal = Application::getConnection()->query(
			'SELECT COUNT(`ID`) AS COUNT_ROWS FROM ' . $dm::getEntity()->getDBTableName() . ' ' . $whereSql )
			->fetch();

		return Json::encode([
			'START_ID'      => $resultStart['ID'],
			'END_ID'        => $resultEnd['ID'],
			'TOTAL_ROWS'    => $resultTotal['COUNT_ROWS']
		]);
	}

	public function getEntityAction(DataManagerReadModel $entity): string
	{
		return Json::encode($entity->getEntityObject());
	}

	///// endregion
}
