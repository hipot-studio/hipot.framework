<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 18.11.2019 0:39
 * @version pre 1.0
 */

use Bitrix\Main\Loader;
use Bitrix\Main\Data\MemcacheConnection;
use Bitrix\Main\Data\CacheEngineMemcache;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\File\Image\Imagick as BitrixImagick;

$defaultSettings = require __DIR__ . '/.settings.php';

return [
	// two stages sessions
	'session' => [
		'value' => [
			'lifetime' => 14400,
			'mode' => 'separated',
			'handlers' => [
				'kernel' => 'encrypted_cookies',
				'general' => [
					'type' => 'file',

					/*'type' => 'memcache',
					'port' => '0',
					'host' => 'unix:///home/bitrix/memcached.sock'*/
				],
			],
		]
		/*
		'value' => [
			'mode' => 'default',
			'handlers' => [
				'general' => [
					'type' => 'file',
				]
			],
		],
		'value' => [
			'mode' => 'default',
			'handlers' => [
				'general' => [
					'type' => 'redis',
					'port' => '6379',
					'host' => '127.0.0.1',
					'serializer' => \Redis::SERIALIZER_PHP
				],
			],
		],
		*/
	],
	'crypto' => [
		'value' => [
			'crypto_key' => '',     //советуем устанавливать 32-х символьную строку из a-z0-9
		],
		'readonly' => true
	],
	'cache' => [
		'value' => [
			'type' => [
				'class_name'    => CacheEngineMemcache::class,
				'extension'     => 'memcache'
			],
			'memcache' => [
				'host' => '127.0.0.1',      // 'unix:///home/bitrix/memcached.sock'
				'port' => '11211',          // '0'
			]
		],
		'sid' => Loader::getDocumentRoot() . "#01"
	],
	'cache_flags' => [
		'value' => [
			'config_options'        => 3600,
			'site_domain'           => 3600 * 24 * 7,
			// Кеш пишется в папку /bitrix/managed_cache/MYSQL/orm_%имя_таблицы_сущности_orm%/ с ключом md5(%sql-запрос%)
			"b_hlblock_entity_min_ttl" => 60,
			"b_hlblock_entity_max_ttl" => 86400,
			"b_iblock_element_min_ttl" => 60,
			"b_iblock_element_max_ttl" => 86400,
			"b_iblock_property_min_ttl" => 68400,
			"b_iblock_property_max_ttl" => 68400,
			"b_iblock_min_ttl" =>  68400,
			"b_iblock_max_ttl" =>  68400,
			"b_group_min_ttl" => 86400,
			"b_group_max_ttl" => 86400
		],
		'readonly' => false,
	],
	'composer' => [
		'value' => ['config_path' => 'local/composer.json']	// may relative path set
	],
	/* @see https://dev.1c-bitrix.ru/api_d7/bitrix/main/web/httpclient/legacy.php */
	'http_client_options' => [
		'value' => [
			'redirect'                  => true,
			'redirectMax'               => 3,
			'version'                   => HttpClient::HTTP_1_1,
			'socketTimeout'             => 20,
			'streamTimeout'             => 40,
			'disableSslVerification'    => true,
			'useCurl'                   => true,
			'curlLogFile'               => Loader::getDocumentRoot() . '/upload/curl.log' // opt
		],
		'readonly' => false,
	],
	'connections' => [
		'value' => [
			'default' => $defaultSettings['connections']['value']['default'],
			'memcache' => [
				'className' => MemcacheConnection::class,
				'port' => 0,
				'host' => 'unix:///home/bitrix/memcached.sock'
			],
		]
	],

	'smtp' => [
		'value'	=> [
			'enabled'	=> true,
			'debug'		=> true,	// opt
			'log_file'	=> Loader::getDocumentRoot() . '/upload/mailer.log' // opt
		]
	],

	'services' => [
		'value' => [
			'main.imageEngine' => array(
				'className' => BitrixImagick::class
			),
			'Orhanerday.OpenAI' => [
				'constructor' => static function () {
					// see page https://platform.openai.com/account/api-keys
					$open_ai_key = getenv('OPENAI_API_KEY');
					return new \Orhanerday\OpenAi\OpenAi($open_ai_key);
				},
			],
		],
		'readonly' => true
	]
];