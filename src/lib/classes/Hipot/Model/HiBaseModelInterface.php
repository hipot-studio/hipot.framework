<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 16.06.2023 22:41
 * @version pre 1.0
 */
namespace Hipot\Model;

/**
 * Base type to extends in concrete Hiload-DataManagers-classes (ex. HiBlock<s>Table</s>Model implements HiBaseModel)
 */
interface HiBaseModelInterface
{
	/**
	 * Transform row from DataManager::getById() to ReadModel
	 * @param array $row
	 * @return array
	 */
	public static function toReadModel(array $row): array;

	/**
	 * Return default filter to select read model's
	 * @return array
	 */
	public static function getDefaultFilter(): array;
}