<?php

namespace Hipot\Types;

/**
 * Class Registry
 * This class acts as a registry container to store and retrieve objects.
 */
class Registry
{
	use Container\Container;
	use Container\JsonSerialize;
	use Container\RegularAccess;
}
