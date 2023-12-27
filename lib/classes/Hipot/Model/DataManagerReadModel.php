<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 22:29
 * @version pre 1.0
 */
namespace Hipot\Model;

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
	 * @param HiBaseModel $className
	 * @param int         $entityId
	 *
	 * @return self
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function buildById($className, int $entityId): self
	{
		/**
		 * @var HiBaseModel $className
		 * @noinspection VirtualTypeCheckInspection
		 */
		$obj = $className::getById($entityId)->fetch();
		if (method_exists($className, 'toReadModel')) {
			$obj = $className::toReadModel($obj);
		}
		return new self($obj);
	}

	/**
	 * @param HiBaseModel $className
	 * @return array
	 */
	public static function getDefaultFilter($className): array
	{
		$filter = [];
		if (method_exists($className, 'getDefaultFilter')) {
			return $className::getDefaultFilter();
		}
		return $filter;
	}
}