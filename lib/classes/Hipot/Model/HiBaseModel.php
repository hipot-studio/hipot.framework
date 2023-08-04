<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 16.06.2023 22:41
 * @version pre 1.0
 */
namespace Hipot\Model;

use Bitrix\Highloadblock\DataManager;
use Bitrix\Highloadblock\HighloadBlockTable;
use Hipot\BitrixUtils\HiBlock;

/**
 * Base class to extends in concrete Hiload-DataManagers-classes (ex. HiBlockTable extends  HiBaseModel)
 */
abstract class HiBaseModel extends DataManager
{
	use EO_Utils;

	// region init block
	public static function getTableName(): string
	{
		$hlblock = self::getHiBlock();

		return $hlblock['TABLE_NAME'];
	}

	public static function getMap(): array
	{
		return [
			'ID' => [
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true
			]
		];
	}

	public static function getHighloadBlock(): array
	{
		$hlblock = self::getHiBlock();
		return HighloadBlockTable::resolveHighloadblock($hlblock);
	}
	// endregion

	private static function getHiBlock(): array
	{
		$class   = str_replace('Table', '', static::class); // HiBlockTable suffix drop
		return HiBlock::getHightloadBlockTable(0, $class);
	}

	/**
	 * Transform row from DataManager::getById() to ReadModel
	 * @param array $row
	 * @return array
	 */
	abstract public static function toReadModel(array $row): array;
}