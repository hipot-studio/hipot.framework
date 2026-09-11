<?php

declare(strict_types=1);

namespace Hipot\BitrixUtils;

/** Utilities for Bitrix menu arrays. */
final class Menu
{
	/**
	 * Builds a nested tree from a flat menu ordered by depth.
	 *
	 * Original item keys are preserved at every level. If a depth level is
	 * skipped, the item is attached to the nearest preceding ancestor.
	 *
	 * @param array<int|string, array<string, mixed>> $items
	 * @return array<int|string, array<string, mixed>>
	 */
	public static function buildTree(
		array $items,
		string $depthKey = 'DEPTH_LEVEL',
		string $childrenKey = 'ITEMS',
	): array {
		$tree = [];
		$ancestors = [];

		foreach ($items as $key => $item) {
			$depth = max(1, (int)($item[$depthKey] ?? 1));
			$item[$childrenKey] = [];

			while ($ancestors !== [] && array_key_last($ancestors) >= $depth) {
				array_pop($ancestors);
			}

			if ($ancestors === []) {
				$tree[$key] = $item;
				$ancestors[$depth] = &$tree[$key];
			} else {
				$parentDepth = array_key_last($ancestors);
				$ancestors[$parentDepth][$childrenKey][$key] = $item;
				$ancestors[$depth] = &$ancestors[$parentDepth][$childrenKey][$key];
			}
			unset($item);
		}

		return $tree;
	}
}
