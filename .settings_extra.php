<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 18.11.2019 0:39
 * @version pre 1.0
 */
return [
	'session' => [
		'value' => [
			'mode' => 'default',
			'handlers' => [
				'general' => [
					'type' => 'file',
					/*'type' => 'memcache',
					'port' => '0',
					'host' => 'unix:///home/bitrix/memcached.sock'*/
				]
			],
		]
	],
	// two stages sessions
	'crypto' => [
		'value' => [
			'crypto_key' => '',
			'readonly' => true
		]
	],
	/*'session' => [
		'value' => [
			'lifetime' => 14400, // +
			'mode' => 'separated',  // +
			'handlers' => [
				'kernel' => 'encrypted_cookies',  // +
				'general' => [
					'type' => 'file',

					//'type' => 'memcache',
					//'port' => '11211',
					//'host' => '127.0.0.1',
				],
			],
		]
	],*/
	'cache' => [
		'value' => [
			'type' => [
				'class_name'    => '\\Bitrix\\Main\\Data\\CacheEngineMemcache',
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
			'site_domain'           => 3600,
			// Кеш пишется в папку /bitrix/managed_cache/MYSQL/orm_%имя_таблицы_сущности_orm%/ с ключом md5(%sql-запрос%)
			"b_hlblock_entity_min_ttl" => 60,
			"b_hlblock_entity_max_ttl" => 86400,
			/*"b_iblock_element_min_ttl" => 60,
			"b_iblock_element_max_ttl" => 86400,*/
		],
		'readonly' => false,
	],
	'composer' => [
		'value' => ['config_path' => 'local/composer.json']	// may relative path set
	],
];