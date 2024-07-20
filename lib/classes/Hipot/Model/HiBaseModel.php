<?php

namespace Hipot\Model;

use Bitrix\Main\ORM\Data\DataManager;

/**
 * Base type to extends in concrete Hiload-DataManagers-classes (ex. HiBlock<s>Table</s>Model extends HiBaseModel)
 */
abstract class HiBaseModel implements HiBaseModelInterface
{
	use EO_Utils;

	/**
	 * @inheritDoc
	 */
	public static function toReadModel(array $row): array
	{
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public static function getDefaultFilter(): array
	{
		return [];
	}

	/**
	 * @param class-string<DataManager> $className
	 *
	 * @return class-string<\Hipot\Model\HiBaseModelInterface>
	 */
	public static function getModelClass($className): string
	{
		return str_replace('Table', 'Model', $className);
	}

	/**
	 * @param class-string<\Hipot\Model\HiBaseModel> $className
	 * @return class-string<DataManager>
	 */
	public static function getDataManagerClass($className): string
	{
		return str_replace('Model', 'Table', $className);
	}
}