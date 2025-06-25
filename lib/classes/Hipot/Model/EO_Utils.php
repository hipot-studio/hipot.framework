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
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Objectify\EntityObject;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\ORM\Fields\ScalarField;
use Bitrix\Main\ORM;
use Bitrix\Main\Result;
use Hipot\Utils\UUtils;
use Bitrix\Main\SystemException;
use stdClass;
use RuntimeException;

/**
 * Дополнительные утилиты для сущностей DataManager (для датамаппера)
 */
trait EO_Utils
{
	/**
	 * Get the value of a specific field with checks from a row when used fetch() instead of fetchObject()
	 *
	 * @param array  $row The row containing the fields.
	 * @param string $field The name of the field to retrieve.
	 *
	 * @return mixed The value of the field.
	 * @throws \RuntimeException if the specified field does not exist in the entity's table.
	 */
	public static function getRowField(array $row, string $field, ?Entity $entity = null)
	{
		if ($entity === null) {
			$entity = static::getEntity();
		}
		$entityFields = $entity->getFields();
		if (!isset($entityFields[$field])) {
			throw new \RuntimeException("Field '{$field}' does not exist in " . static::getTableName());
		}
		return $row[$field];
	}

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
	 * @param class-string<\Bitrix\Main\ORM\Data\DataManager> $dm
	 *
	 * @return array{'PREV':array{'ID':int}, 'NEXT':array{'ID':int}}
	 */
	public static function getNextPrevElementsById(int $id, array $order = [], array $select = ['ID'], array $filter = [], $dm = null): array
	{
		$dm = $dm ?? static::class;

		// find start pos
		$connection = Main\Application::getConnection();
		$tableName = $dm::getTableName();
		$whereSql = Query::buildFilterSql($dm::getEntity(), $filter);
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
								@rownum := @rownum + 1 AS `rank`
						FROM `{$tableName}` `{$tableName}`, (SELECT @rownum := 0) `r`
						WHERE {$whereSql}
						ORDER BY {$orderSql}
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
				'offset' => max($offset['rank'] - 2, 0),    // to select current and prev
			];
		}
		$list = $dm::getList($getListParams);

		$prev = $next = $iter = false;
		$bPrev = false;
		while ($row = $list->fetch()) {
			if ($bPrev) {
				$next = $row;
				break;
			}
			if ($row['ID'] == $id) {
				$prev = $iter;
				$bPrev = true;
			}
			$iter = $row;
		}
		return [
			'PREV' => $prev,
			'NEXT' => $next,
		];
	}

	/**
	 * Description: Retrieves statistic information based on the provided parameters.
	 *
	 * @param string $orderBy The field to order the results by.
	 * @param string $orderOrder The order of the results (ASC or DESC). Default is ASC.
	 * @param array  $filter An array of conditions to filter the results by.
	 * @param int    $cacheTtl The time-to-live (TTL) for caching the results. Default is 0 (no caching).
	 * @param class-string<\Bitrix\Main\ORM\Data\DataManager> $dm
	 *
	 * @return array An array containing the start result, end result, and total result.
	 */
	public static function getStatistic(string $orderBy, string $orderOrder = 'ASC', array $filter = [], int $cacheTtl = 0, $dm = null): array
	{
		$orderOrderRev = 'DESC';
		if (strtoupper($orderOrder) == 'DESC') {
			$orderOrderRev = 'ASC';
		}

		$dm = $dm ?? static::class;

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
		$resultTotal = $dm::getList([
			'select' => ['CNT'],
			'runtime' => [
				new ORM\Fields\ExpressionField('CNT', 'COUNT(*)')
			],
			'filter' => $filter
		])->fetch();
		return [$resultStart, $resultEnd, $resultTotal];
	}

	/**
	 * Collects and transforms the values from an EntityObject into a standard object.
	 * If the EntityObject contains nested EntityObjects, it recursively processes them.
	 *
	 * @param EntityObject $object The source entity object whose values are to be collected and transformed.
	 *
	 * @return object An object containing the collected values from the EntityObject.
	 * @throws RuntimeException If a system exception occurs during value collection.
	 */
	public static function collectValuesFromEntityObject(EntityObject $object): object
	{
		try {
			$item = new stdClass();
			foreach ($object->collectValues() as $key => $value) {
				if ($value instanceof EntityObject) {
					$value = self::collectValuesFromEntityObject($value);
				}
				$item->{$key} = $value;
			}
			return $item;
		} catch (SystemException $e) {
			throw new RuntimeException($e->getMessage(), $e->getCode(), $e);
		}
	}
}