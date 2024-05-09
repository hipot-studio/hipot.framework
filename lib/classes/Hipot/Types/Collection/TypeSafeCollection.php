<?php

namespace Hipot\Types\Collection;

use Hipot\Types\ObjectArItem as Instance;

/**
 * If you want to use any type safe collection
 */
interface TypeSafeCollection
{
	public function add(Instance $entity): void;

	public function get(int $entityId): Instance;
}