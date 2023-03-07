<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 18.11.2019 0:39
 * @version pre 1.0
 */
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
	],
	/*'session' => [
		'value' => [
			'mode' => 'default',
			'handlers' => [
				'general' => [
					'type' => 'file',
				]
			],
		]
	],*/
	'crypto' => [
		'value' => [
			'crypto_key' => 'f73ed4636fa497cbf4e0b287b76bc2b2',     //советуем устанавливать 32-х символьную строку из a-z0-9
		],
		'readonly' => true
	],
	'cache' => [
		'value' => [
			'type' => [
				'class_name'    => \Bitrix\Main\Data\CacheEngineMemcache::class,
				'extension'     => 'memcache'
			],
			'memcache' => [
				'host' => '127.0.0.1',      // 'unix:///home/bitrix/memcached.sock'
				'port' => '11211',          // '0'
			]
		],
		'sid' => $_SERVER["DOCUMENT_ROOT"] . "#01"
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
	'http_client_options' => [
		'value' => [
			'redirect'                  => true,
			'redirectMax'               => 5,
			'version'                   => '1.1',
			'socketTimeout'             => 30,
			'streamTimeout'             => 50,
			'disableSslVerification'    => true
		],
		'readonly' => false,
	],
	'connections' => [
		'value' => [
			'default' => $defaultSettings['connections']['value']['default'],
			'memcache' => [
				'className' => \Bitrix\Main\Data\MemcacheConnection::class,
				'port' => 0,
				'host' => 'unix:///home/bitrix/memcached.sock'
			],
		]
	],

	'smtp' => [
		'value'	=> [
			'enabled'	=> true,
			'debug'		=> true,	// opt
			'log_file'	=> $_SERVER['DOCUMENT_ROOT'] . '/mailer.log' // opt
		]
	],

	'services' => [
		'value' => [
			'main.imageEngine' => [
				'className' => \Bitrix\Main\File\Image\Imagick::class
			]
		],
		'readonly' => true
	]
];