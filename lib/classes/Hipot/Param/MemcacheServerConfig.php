<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 25.06.2022 19:22
 * @version pre 1.0
 */
namespace Hipot\Param;

use Bitrix\Main\Config\Configuration;

class MemcacheServerConfig
{
	/**
	 * Использовать ли сокет для подключения
	 * @var bool
	 */
	private bool $socket;

	/**
	 * Путь к мемкеш-сокету при ручной конфигурации по нашему регламенту
	 * @var string
	 */
	private const MANUAL_SOCKET_PATH = 'unix:///home/bitrix/memcached.sock';

	/**
	 * Для сохранения итогового подключенного сервера
	 * @var array
	 */
	private const DEFAULT_SERVER_ADDR = [
		'host'  => 'localhost',
		'port'  => 11211
	];

	public function __construct(bool $socket)
	{
		$this->socket = $socket;
	}

	public static function create(bool $socket = true): self
	{
		return new self($socket);
	}

	/**
	 * Возвращает адрес для подключения к сокету
	 *
	 * @param bool $socket = true
	 *
	 * @return array {'host' : string, 'port' : string}
	 */
	public function getServerAddr(): array
	{
		$v = self::DEFAULT_SERVER_ADDR;
		if ($this->socket) {
			// socket
			$v["host"] = self::MANUAL_SOCKET_PATH;
			$v["port"] = 0;
		} else {
			$cacheConfig = Configuration::getValue("cache");
			$vS = $cacheConfig["memcache"] ?? null;

			if ($vS != null && isset($vS["port"])) {
				$v["port"] = (int)$vS["port"];
			}
			if (trim($vS["host"]) != '') {
				$v["host"] = $vS["host"];
			}
		}
		return $v;
	}
}