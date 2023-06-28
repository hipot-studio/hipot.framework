<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 03.08.2019 23:30
 * @version pre 1.0
 */
namespace Hipot\Model;

use Bitrix\Main;
use Bitrix\Main\Data;
use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\DB;
use Bitrix\Main\Entity\Query;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\Result;
use Hipot\Utils\UUtils;

/**
 * Дополнительные утилиты для сущностей
 * @package Hipot\BitrixUtils
 */
trait EO_UtilsTrait
{
	/**
	 * @param ScalarField $field
	 *
	 * @return DB\Result;
	 * @throws \Bitrix\Main\DB\SqlQueryException
	 */
	private static function addFieldDataTable(ScalarField $field): DB\Result
	{
		/** @var Data\Connection|DB\Connection $connection */
		$connection = self::getEntity()->getConnection();

		$query = 'ALTER   TABLE `' . self::getTableName() . '` ADD ' . $field->getName() . ' ';
		$query .= $connection->getSqlHelper()->getColumnTypeByField($field);
		$query .= $field->isRequired() ? ' NOT NULL' : '';
		return $connection->query($query);
	}

	/**
	 * Обновить поля сушьности после добавления его в getMap()
	 * удаленные из getMap удаляются из базы, а добавленные в него - добавляются
	 */
	public static function updateDataTable(): void
	{
		$entity = self::getEntity();
		/** @var Data\Connection|DB\Connection $connection */
		$connection = $entity->getConnection();

		$dbHasField = [];
		foreach ($connection->getTableFields(self::getTableName()) as $field) {
			if (!$entity->hasField($field->getName())) {
				$connection->dropColumn(
					self::getTableName(),
					$field->getName()
				);
			} else {
				$dbHasField[] = $field->getName();
			}
		}
		foreach ($entity->getFields() as $field) {
			/** @noinspection TypeUnsafeArraySearchInspection */
			if (!in_array($field->getName(), $dbHasField)) {
				self::addFieldDataTable($field);
			}
		}
	}

	/**
	 * Wraps provided callback with transaction
	 * @param callable $callback
	 * @param array $arguments
	 * @return mixed
	 * @throws SqlQueryException
	 * @throws \Throwable
	 */
	public static function wrapTransaction(callable $callback, ...$arguments): bool
	{
		$connection = Application::getConnection();

		try {
			$connection->startTransaction();

			$result = $callback(...$arguments);

			$isSuccess = true;

			if ($result instanceof Result) {
				$isSuccess = $result->isSuccess();
			} elseif (is_bool($result)) {
				$isSuccess = $result;
			}

			if ($isSuccess) {
				$connection->commitTransaction();
			} else {
				$connection->rollbackTransaction();
			}

			return $result;
		} catch (\Throwable $e) {
			$connection->rollbackTransaction();
			throw $e;
		}
	}

	/**
	 * Deletes rows by filter.
	 * @param array $filter Filter does not look like filter in getList. It depends by current implementation.
	 * @return void
	 */
	public static function deleteBatch(array $filter)
	{
		$whereSql = Query::buildFilterSql(static::getEntity(), $filter);

		if ($whereSql != '') {
			$tableName = static::getTableName();
			$connection = Main\Application::getConnection();
			$connection->queryExecute("DELETE FROM {$tableName} WHERE {$whereSql}");
		}
	}

	/**
	 * Получить список колонок SQL-запросом, либо если уже был получен, то просто вернуть
	 * @param string $tableName имя таблицы
	 * @return array
	 */
	public static function getTableFields(string $tableName): array
	{
		$a = [];
		if (trim($tableName) != '' && Application::getConnection()->isTableExists($tableName)) {
			$fields = Application::getConnection()->getTableFields($tableName);
			foreach ($fields as $field) {
				$a[] = $field->getName();
			}
		}
		return $a;
	}

	/**
	 * @param int   $id
	 * @param array $order
	 * @param array $select
	 * @param array $filter
	 *
	 * @return array{'PREV':array{'ID':int}, 'NEXT':array{'ID':int}}
	 */
	public static function getNextPrevElementsById(int $id, array $order = [], array $select = ['ID'], array $filter = []): array
	{
		// find start pos
		$connection = Main\Application::getConnection();
		$tableName = static::getTableName();
		$whereSql = Query::buildFilterSql(static::getEntity(), $filter);
		if (empty($whereSql)) {
			$whereSql = ' 1 ';
		}
		$orderSql = '';
		foreach ($order as $orderF => $orderO) {
			$orderSql .= " `{$tableName}`.`{$orderF}` {$orderO}, ";
		}
		$orderSql = rtrim($orderSql, ', ');

		try {
			/** @noinspection SqlNoDataSourceInspection */
			$offset = $connection->query(
				"SELECT `ID`, `rank` FROM
						(SELECT 
							`{$tableName}`.`ID` AS `ID`,
								@rownum := @rownum + 1 AS rank
						FROM `{$tableName}` `{$tableName}`, (SELECT @rownum := 0) r 
						ORDER BY {$orderSql}
						WHERE {$whereSql}
						) AS `list`
					WHERE `ID` = {$id}")->fetch();
		} catch (\Throwable $ignore) {
			UUtils::logException($ignore);
			$offset = [
				'rank' => 0
			];
		}

		$getListParams = [
			'order' => $order,
			'select' => $select,
			'filter' => $filter,
			"cache" => ["ttl" => 3600 * 24 * 7, "cache_joins" => true]
		];
		if ((int)$offset['rank'] > 0) {
			$getListParams += [
				'limit' => 4,
				'offset' => $offset['rank'],
			];
		}
		$list = static::getList($getListParams);

		$prev = $next = [];
		$iter = false;
		$bPrev = false;
		while ($row = $list->fetch()) {
			if ($bPrev) {
				$prev = $row;
				break;
			}
			if ($row['ID'] == $id) {
				$next = $iter;
				$bPrev = true;
			}
			$iter = $row;
		}
		return [
			'PREV' => $prev,
			'NEXT' => $next,
		];
	}
}