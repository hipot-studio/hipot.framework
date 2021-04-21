<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 03.08.2019 23:30
 * @version pre 1.0
 */

namespace Hipot\BitrixUtils;

use Bitrix\Main\Application;
use Bitrix\Main\DB\SqlQueryException;
use Bitrix\Main\Result;

/**
 * Дополнительные утилиты для сущностей
 * @package Hipot\BitrixUtils
 */
trait EO_Utils
{
	/**
	 * @param \Bitrix\Main\ORM\Fields\ScalarField $field
	 */
	protected function addFieldDataTable(\Bitrix\Main\ORM\Fields\ScalarField $field)
	{
		$connection = self::getEntity()->getConnection();

		$query = 'ALTER   TABLE ';
		$query .= self::getTableName() . ' ADD ' . $field->getName() . ' ';
		$query .= $connection->getSqlHelper()->getColumnTypeByField($field);
		$query .= $field->isRequired() ? ' NOT NULL' : '';
		$connection->query($query);
	}

	/**
	 * Обновить поля сушьности после добавления его в getMap()
	 * удаленные из getMap удаляются из базы, а добавленные в него - добавляются
	 */
	public static function updateDataTable()
	{
		$entity = self::getEntity();
		$connection = $entity->getConnection();

		$dbHasField = array();
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
	 * @throws Throwable
	 */
	public static function wrapTransaction(callable $callback, ...$arguments)
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
		} catch (Throwable $e) {
			$connection->rollbackTransaction();
			throw $e;
		}
	}
}