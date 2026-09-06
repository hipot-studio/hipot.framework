<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 22:29
 * @version pre 1.0
 */
namespace Hipot\Model;

use Bitrix\Main\ORM\Data\DataManager;

class DataManagerReadModel
{
	private array $entityObject;

	public function __construct(array $entityObject)
	{
		$this->entityObject = $entityObject;
	}

	/**
	 * @return mixed
	 */
	public function getEntityObject(): array
	{
		return $this->entityObject;
	}

	/**
	 * @param class-string<DataManager>|DataManager $className
	 * @param int         $entityId
	 *
	 * @return self
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function buildById($className, int $entityId): self
	{
		$classModelName = HiBaseModel::getModelClass($className);
		$obj = $className::getByPrimary($entityId, ['filter' => self::getDefaultFilter($classModelName)])->fetch();
		if (class_exists($classModelName) && method_exists($classModelName, 'toReadModel')) {
			$obj = $classModelName::toReadModel($obj);
		}
		return new self($obj);
	}

	/**
	 * Default filter used in actions
	 *
	 * @param class-string<HiBaseModelInterface>|HiBaseModelInterface $classModelName
	 *
	 * @return array
	 */
	public static function getDefaultFilter($classModelName): array
	{
		$filter = [];
		if (class_exists($classModelName) && method_exists($classModelName, 'getDefaultFilter')) {
			return $classModelName::getDefaultFilter();
		}
		return $filter;
	}

}