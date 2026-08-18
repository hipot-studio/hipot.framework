<?php

namespace Hipot\Services;

use Bitrix\Main\Application;
use Bitrix\Main\Loader;

/**
 * Represents a utility for managing database table clones and generating facet sections
 * for specific sections in a Bitrix Information Block.
 * <pre>
 * // With combination of invoking (ex. local/php_interface/cron/crop_facet_table.php)
 * // with \Bitrix\Iblock\PropertyIndex\Facet::query() patch:
 * $facetTableName = $this->storage->getTableName();
 * $facetTableNameMin = ($this->iblockId == \Site\Settings::BIG_FACET_CATALOG_ID && $this->sectionId > 0)
 * ? $facetTableName . "_" . $this->sectionId
 * : $facetTableName;
 * if ($facetTableName !== $facetTableNameMin
 * && is_array($connection->query('SHOW TABLES FROM `' . $connection->getDatabase() . '` LIKE "' . $facetTableNameMin . '"')->fetch())
 * ) {
 * $facetTableName = $facetTableNameMin;
 * }
 * unset($facetTableNameMin);
 * </pre>
 */
final class FacetFragments
{
	public function __construct(
		private $connection = null,
		private bool $noSqlExecute = false,
	)
	{
		$this->connection = Application::getConnection();
		Loader::includeModule('iblock');
	}
	
	private function query($query)
	{
		if ($this->noSqlExecute) {
			echo $query . PHP_EOL;
			return new \CDbResult();
		}
		return $this->connection->query($query);
	}
	
	public function dropCloneTable(string $cloneTable): void
	{
		$res = $this->query('SHOW TABLES FROM `' . $this->connection->getDatabase() . '` LIKE "' . $cloneTable . '"');
		if ($row = $res->Fetch()) {
			$resDel = $this->query('DROP TABLE `' . $cloneTable . '`');
		}
	}
	public function clearCloneTable(string $cloneTable): void
	{
		$res = $this->query('SHOW TABLES FROM `' . $this->connection->getDatabase() . '` LIKE "' . $cloneTable . '"');
		if ($row = $res->Fetch()) {
			$resDel = $this->query('TRUNCATE TABLE `' . $cloneTable . '`');
		}
	}
	public function createSectionFacet(int $sectionID, string $fullTable, string $cloneTable): void
	{
		$resCreate = $this->query('CREATE TABLE IF NOT EXISTS ' . $cloneTable . ' LIKE ' . $fullTable . '');
		$resInsert = $this->query('INSERT INTO `' . $cloneTable . '` SELECT * FROM `' . $fullTable . '` WHERE SECTION_ID="' . $sectionID . '"');
	}
	
	/**
	 * Handles the creation and management of faceted indexes for sections within an information block.
	 *
	 * Iterates through sections based on the provided filter, processes their element count, and updates
	 * custom user fields accordingly. If the element count for a section is 0, it removes the related clone table.
	 * Otherwise, it clears the clone table, creates a faceted index for the section, and updates the related user field.
	 * Optionally, outputs debug information if enabled.
	 *
	 * @param int   $iblockId The ID of the information block to process sections for.
	 * @param array $filter An array defining the filter criteria for selecting sections.
	 * @param bool  $showDebug Whether to display debug information while processing.
	 *
	 * @return void
	 */
	public function run(int $iblockId, array $filter, bool $showDebug = false): void
	{
		$db_list = \CIBlockSection::GetList(['ID' => 'ASC'], $filter, true, ["IBLOCK_ID", "ID", 'ELEMENT_CNT', "NAME", "UF_CREATED_FACET"]);
		while ($arSection = $db_list->GetNext()) {
			$fullTable  = "b_iblock_" . $iblockId . "_index";
			$cloneTable = "b_iblock_" . $iblockId . "_index_" . $arSection["ID"];
			
			if ($arSection['ELEMENT_CNT'] <= 0) {
				$this->dropCloneTable($cloneTable);
				BitrixEngine::getUserFieldManager()->Update('IBLOCK_' . $iblockId . '_SECTION', $arSection['ID'], [
					'UF_CREATED_FACET' => false
				]);
				continue;
			}
			
			$this->clearCloneTable($cloneTable);
			$this->createSectionFacet((int)$arSection["ID"], $fullTable, $cloneTable);
			BitrixEngine::getUserFieldManager()->Update('IBLOCK_' . $iblockId . '_SECTION', $arSection['ID'], [
				'UF_CREATED_FACET' => true
			]);
			
			if ($showDebug) {
				echo $arSection["ID"] . '.' . PHP_EOL;
			}
		}
	}
}

