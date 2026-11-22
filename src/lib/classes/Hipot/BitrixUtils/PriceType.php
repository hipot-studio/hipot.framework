<?php

declare(strict_types=1);

namespace Hipot\BitrixUtils;

use Bitrix\Catalog\GroupTable;
use Bitrix\Main\Loader;
use Bitrix\Main\LoaderException;

/** Lookup of catalog price types by symbolic NAME. */
final class PriceType
{
	public const int CACHE_TTL = 3600;

	/** Returns the lowest matching ID, or null. */
	public static function getIdByCode(string $code): ?int
	{
		return self::getInfoByCode($code)['ID'] ?? null;
	}

	/**
	 * Returns basic metadata by exact NAME (not the localized display name).
	 * Surrounding whitespace is removed. Duplicate codes resolve to the lowest ID.
	 * @return array<string, mixed>|null
	 */
	public static function getInfoByCode(string $code): ?array
	{
		$code = trim($code);
		if ($code === '') {
			return null;
		}
		if (!Loader::includeModule('catalog')) {
			throw new LoaderException('PriceType requires the catalog module.');
		}

		$row = GroupTable::query()
			->setSelect(['ID', 'NAME', 'BASE', 'SORT', 'XML_ID'])
			->where('NAME', $code)
			->setOrder(['ID' => 'ASC'])
			->setLimit(1)
			->setCacheTtl(self::CACHE_TTL)
			->fetch();
		if ($row === false) {
			return null;
		}

		$row['ID'] = (int)$row['ID'];
		$row['SORT'] = (int)$row['SORT'];
		return $row;
	}
}
