<?php
namespace Hipot\BitrixUtils\Agent;

use Bitrix\Main\ArgumentTypeException;

/**
 * Agent helpers.
 */
final class AgentHelper
{
	/**
	 * Creates and returns agent name by class name and parameters.
	 * Use to return this name from the executed method of agent.
	 *
	 * @param string $className Agent class name.
	 * @param array  $args Arguments for `__constructor` of agent class.
	 * @param array  $callChain
	 *
	 * @return string
	 * @throws ArgumentTypeException
	 */
	public static function createName($className, array $args = [], array $callChain = []): string
	{
		$chain = '';

		if (!empty($callChain)) {
			foreach ($callChain as $method => $methodArgs) {
				if (!is_array($methodArgs)) {
					throw new ArgumentTypeException('callChain', 'array');
				}

				$chain .= '->' . $method . '(' . static::convertArgsToString($methodArgs) . ')';
			}
		}

		return '\\' . $className . '::agent(' . static::convertArgsToString($args) . ')' . $chain . ';';
	}

	protected static function convertArgsToString(array $args): string
	{
		$args = json_encode($args, JSON_UNESCAPED_SLASHES);
		$args = str_replace(',', ', ', $args);
		$args = substr($args, 1);
		$args = substr($args, 0, -1);

		return $args;
	}
}