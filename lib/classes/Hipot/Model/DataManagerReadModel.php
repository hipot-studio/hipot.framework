<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 08.06.2023 22:29
 * @version pre 1.0
 */
namespace Hipot\Model;

// old DataManager used in hl-blocks
use Bitrix\Main\Entity\DataManager;

class DataManagerReadModel
{
	private $entityObject;

	public function __construct($entityObject)
	{
		$this->entityObject = $entityObject;
	}

	/**
	 * @return mixed
	 */
	public function getEntityObject()
	{
		return $this->entityObject;
	}

	public static function buildById($className, $entityId): self
	{
		/** @var DataManager $className */
		$obj = $className::getById($entityId)->fetch();
		return new self($obj);
	}
}