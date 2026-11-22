<?php

declare(strict_types=1);

namespace Hipot\BitrixUtils;

use Bitrix\Main\GroupTable;

/** Lookup of user groups by symbolic STRING_ID. */
final class UserGroup
{
	public const int CACHE_TTL = 3600;

	/** Returns the lowest matching ID, including inactive groups, or null. */
	public static function getIdByCode(string $code): ?int
	{
		return self::getInfoByCode($code)['ID'] ?? null;
	}

	/**
	 * Returns basic metadata by exact STRING_ID, or null when not found.
	 * Surrounding whitespace is removed. Duplicate codes resolve to the lowest ID.
	 * @return array<string, mixed>|null
	 */
	public static function getInfoByCode(string $code): ?array
	{
		$code = trim($code);
		if ($code === '') {
			return null;
		}

		$row = GroupTable::query()
			->setSelect(['ID', 'STRING_ID', 'NAME', 'DESCRIPTION', 'ACTIVE', 'C_SORT'])
			->where('STRING_ID', $code)
			->setOrder(['ID' => 'ASC'])
			->setLimit(1)
			->setCacheTtl(self::CACHE_TTL)
			->fetch();
		if ($row === false) {
			return null;
		}

		$row['ID'] = (int)$row['ID'];
		$row['C_SORT'] = (int)$row['C_SORT'];
		return $row;
	}
}
