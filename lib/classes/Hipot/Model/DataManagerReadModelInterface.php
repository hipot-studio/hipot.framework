<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 13.06.2023 21:24
 * @version pre 1.0
 */
namespace Hipot\Model;

// old DataManager used in hl-blocks
use Bitrix\Main\Entity\DataManager;

interface DataManagerReadModelInterface
{
	/**
	 * Transform row from DataManager::getById() to ReadModel
	 * @param array $row
	 * @return array
	 */

	public static function modifyRowAfterGet(array $row): array;
}