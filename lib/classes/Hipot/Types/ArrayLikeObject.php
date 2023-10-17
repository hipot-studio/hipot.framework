<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 27.06.2022 19:29
 * @version pre 1.0
 */
namespace Hipot\Types;

/**
 * #[AllowDynamicProperties] in future versions php
 */
trait ArrayLikeObject
{
	private array $data = [];

	public function &__get($name)
	{
		return $this->data[$name];
	}

	public function __isset($name)
	{
		return isset($this->data[$name]);
	}

	public function __set($name, $value)
	{
		$this->data[$name] = $value;
	}

	public function __unset($name)
	{
		unset($this->data[$name]);
	}
}