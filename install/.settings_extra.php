<?php
/**
 * hipot studio source file
 * User: <hipot AT ya DOT ru>
 * Date: 18.11.2019 0:39
 * @version pre 1.0
 */

use Bitrix\Main\Data\CacheEngineApc;
use Bitrix\Main\Data\CacheEngineFiles;
use Bitrix\Main\Data\CacheEngineMemcache;
use Bitrix\Main\Data\MemcacheConnection;
use Bitrix\Main\DB\ConnectionException;
use Bitrix\Main\Loader;
use Bitrix\Main\Web\HttpClient;
use Bitrix\Main\File\Image\Imagick as BitrixImagick;
use Bitrix\Main\Application;

$defaultSettings = require __DIR__ . '/.settings.php';
$sid             = Loader::getDocumentRoot() . "#01";

return [
	/* @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2795#cache */
	'cache' => [
		'value' => [
			'type' => [
				'class_name'    => CacheEngineFiles::class,
			],
			'sid' => $sid,
			'use_lock' => true
		],
		/*
		'value' => [
			'type' => [
				'class_name'    => CacheEngineMemcache::class,
				'extension'     => 'memcache'
			],
			'memcache' => [
				'host' => '127.0.0.1',      // 'unix:///home/bitrix/memcached.sock'
				'port' => '11211',          // '0'
			],
			'sid' => $sid
		]
		*/
	],
	'cache_flags' => [
		'value' => [
			'config_options'            => 3600,
			'site_domain'               => 3600 * 24 * 7,
			'geoip_manager'             => 604800 * 2, // a two week
			// Кеш пишется в папку /bitrix/managed_cache/MYSQL/orm_%имя_таблицы_сущности_orm%/ с ключом md5(%sql-запрос%)
			"b_hlblock_entity_min_ttl"  => 60,
			"b_hlblock_entity_max_ttl"  => 86400,
			"b_iblock_element_min_ttl"  => 60,
			"b_iblock_element_max_ttl"  => 86400,
			"b_iblock_property_min_ttl" => 68400,
			"b_iblock_property_max_ttl" => 68400,
			"b_iblock_min_ttl"          => 68400,
			"b_iblock_max_ttl"          => 68400,
			"b_group_min_ttl"           => 86400,
			"b_group_max_ttl"           => 86400,
			"b_lang_min_ttl"            => 86400,
			"b_lang_max_ttl"            => 86400,
		],
		'readonly' => false,
	],

	'crypto' => [
		'value' => [
			'crypto_key' => '',     //советуем устанавливать 32-х символьную строку из a-z0-9
		],
		'readonly' => true
	],

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

	'connections' => [
		'value' => [
			'default' => $defaultSettings['connections']['value']['default'],
			'memcache' => [
				'className' => MemcacheConnection::class,
				'port'      => 0,
				'host'      => 'unix:///home/bitrix/memcached.sock',
				'sid'       => $sid
			]
		]
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

	'smtp' => [
		'value'	=> [
			'enabled'	=> true,
			'debug'		=> true,	// opt
			'log_file'	=> Loader::getDocumentRoot() . '/upload/mailer.log' // opt
		]
	],

	/* @see https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=105&LESSON_ID=14032&LESSON_PATH=9209.5062.14032  */
	'services' => [
		'value' => [
			'main.imageEngine' => [
				'className' => BitrixImagick::class,
				/* @see https://github.com/bitrix24/dev?tab=readme-ov-file#phpimagickimageengine */
				'constructorParams' => [
					null,
					[
						'allowAnimatedImages' => true,
						'maxSize' => [
							7000,
							7000
						],
						'jpegLoadSize' => [
							2000,
							2000
						],
						// 'substImage' => $_SERVER['DOCUMENT_ROOT'].'/bitrix/images/image.png',
					]
				],
			],
			'Orhanerday.OpenAI' => [
				'constructor' => static function () {
					// see page https://platform.openai.com/account/api-keys
					$open_ai_key = getenv('OPENAI_API_KEY');
					return new \Orhanerday\OpenAi\OpenAi($open_ai_key);
				},
			],
			'cache.files' => [
				'constructor' => static function () {
					$options = [];
					return new CacheEngineFiles($options);
				}
			],
			'cache.apc' => [
				'constructor' => static function () {
					// important: set 'sid' in 'cache-value' section of config
					$options = [
						'type' => 'apcu',
						'use_lock' => true
					];
					return new class ($options) extends CacheEngineApc {
						/** @noinspection MagicMethodsValidityInspection */
						public function __construct(array $options = [])
						{
							$config = $this->configure($options);
							$this->connect($config);
						}
					};
				}
			],
			'cache.memcache' => [
				'constructor' => static function () {
					// important: set 'sid' in 'cache-value' section of config
					$options = [
						'type'    => 'memcache',
						'servers' => [
							[
								'host' => 'unix:///home/bitrix/memcached.sock',
								'port' => 0
							]
						],
						'use_lock' => true
					];
					return new class ($options) extends CacheEngineMemcache {
						/** @noinspection MagicMethodsValidityInspection */
						public function __construct(array $options = [])
						{
							/*$config = $this->configure($options);
							$this->connect($config);
							if (self::$isConnected === false) {
								throw new ConnectionException('Cant connect to memcache');
							}*/

							if (self::$engine === null) {
								/** @var MemcacheConnection $connection */
								$connection = Application::getConnection('memcache');
								self::$engine = $connection->getResource();
							}
						}
					};
				}
			],
		],
		'readonly' => true
	],

	'analytics_counter' => [
		'value' => [
			'enabled' => false
		],
	],
];