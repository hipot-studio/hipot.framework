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

	public static function buildById($className, int $entityId): self
	{
		/**
		 * @var DataManagerReadModelInterface $className
		 * @noinspection VirtualTypeCheckInspection
		 */
		$obj = $className::getById($entityId)->fetch();
		if (method_exists($className, 'modifyRowAfterGet')) {
			$obj = $className::modifyRowAfterGet($obj);
		}
		return new self($obj);
	}
}