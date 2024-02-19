Решение PhpCacher для кэширования отдельных частей кода
------------------------------------------------------

Класс <code>\Hipot\BitrixUtils\PhpCacher</code> предназначен для быстрого кэширования выборок в коде 
для оптимизаций решений на битриксе прямо в текущем коде не создавая для этого отдельную сущность (напр. компонент).

Основная цель — для того что бы не писать повторяющиеся конструкции стандартного битрикс-кэширования (startDataCache - полезная нагрузка - endDataCache). По сути это обертка над стандартными действиями необходимыми при кэшировании. 

Т.е. данный класс предоставляет функционал как обертка над некой "тяжелой" логикой в виде анонимной функции, возвращающей нужные данные.

### Возможности
- Быстрая оптимизация кода. Обертка тяжелого участка кода и помещение его в кэш
- Возможность контролировать кэш на диске. Поскольку при написании требуется задавать осмысленный тег, где хранение в случае файлового кэша будет в виде <code>/bitrix/cache/php/тег1, 
/bitrix/cache/php/тег2</code>, и т.д. Тем самым можно быстро почистить кэш функционала "тег" просто удалением папки /bitrix/cache/php/тег
- Возможность кэшировать данные в разных хранилищах
- Дополнительная кластеризация кэша при необходимости
- Возможность помечать кэш тегами (тегированный кэш битиркса)


### Примеры использования
Допустим, нам необходимо сделать предварительную выборку брендов, которые мы не хотим показывать, а так же закэшишировать её.
А затем полученные бренды использовать как входные данные фильтра компонента.

```php
use Hipot\BitrixUtils\PhpCacher;
$arIds = PhpCacher::cache('index_courses_prefetch', 3600, static function () {
    \Bitrix\Main\Loader::includeModule('iblock');
    // Не отображаем эти бренды
    $res = \CIBlockElement::GetList([], ['IBLOCK_ID' => 9, 'PROPERTY_org' => [3887, 7181, 5340, 5355, 7154]], false, false, ['ID']);
    $arIds = [];
    while ($ar = $res->Fetch()) {
        $arIds = $ar["ID"];
    }
    return $arIds;
});
/**
 * Имя index_courses_prefetch мы выбрали сами, кэш будет сохранен в папке 
 * /bitrix/cache/php/index_courses_prefetch
 */
$APPLICATION->IncludeComponent('hipot:iblock.list', 'courses_today', array(
    'IBLOCK_ID' => 7,
    'PAGESIZE' => 15,
    'NAV_SHOW_ALL' => 'Y',
    'ORDER' => ['ACTIVE_FROM' => 'ASC'],
    'FILTER' => [
        'ACTIVE' => 'Y',
        '!PROPERTY_office' => false,
        '!PROPERTY_education_methods' => $arIds
    ],
    'GET_PROPERTY' => 'Y',
    'SELECT' => [],
    'CACHE_TIME' => 3600,
    'CACHE_TYPE' => 'A',
    'ALWAYS_INCLUDE_TEMPATE' => 'Y',
));
```

Это похоже на хранение а-ля «ключ-значение» (NoSQL), при этом значение возвращает анонимная функция. Если функция возвращает разные значения, то и ключ должен быть разным.
По-сути этот кэш - это для какой-то переменной (первый параметр) мы записываем значение (которое возвращает функция). 
Т.о. переменная ничего не знает о способах выбора данных, поэтому в нее нужно подмешивать зависимости, если функция выбора ими обладает:

```php
$obItem = \CIBlockElement::GetByID($id)->GetNextElement();
$arItem = $obItem->GetFields();
$arItem['PROPS'] = Hipot\BitrixUtils\PhpCacher::cache(
    'salon_list_prop_salon_id' . PhpCacher::getCacheSubDirById($arItem['ID']),
    3600, 
    static function () use ($obItem) {
        return $obItem->GetProperties();
    }
);
```

Обратите внимание на то, что тег задан не <code>'salon_list_prop_salon_id'.$arItem['ID']</code>, т.к. если речь идет о файловой системе, то нам нужно 
<b>кластеризовать хранение элементов кэша</b>, т.к. их может быть огромное кол-во, равномерно складывая в поддиректории <code>/bitrix/cache/php/salon_list_prop_salon_id/*</code>

### Тегированный кэш
По умолчанию класс использует тегированный кэш и помечает кэш связью с указанными тегами. Для выборок из инфоблоков тут не явное поведение, т.к. сами выборки помечают кэш внутри своих GetList, но как есть.  
Внутри функции-замыкания можно помечать итоговый кэш:
```php
$statusesCnt = PhpCacher::cache('some_cache_with_tags', 3600 * 24 * 30 * 12 /* one year */, static function () {
    $result = [];
    
    // ... select result
    BitrixEngine::getInstance()->taggedCache->registerTag("tag_1");
    BitrixEngine::getInstance()->taggedCache->registerTag("tag_2")

    return $result;
});

// clear cache 'some_cache_with_tags' in some event place:
BitrixEngine::getInstance()->taggedCache->clearByTag("tag_2");
```

А если нужно отключить тегированный кэш, т.е. четко зафиксировать время кэша на переданное, можно использовать класс-синглтон <code>\Hipot\Services\BitrixEngine</code>, пример выборки статистики:
```php
$statusesCnt = PhpCacher::cache('total_statistic', 3600 * 24 * 30, static function () {
	// use hard cache this block
	BitrixEngine::getInstance()->taggedCache->abortTagCache();

	// CIBlockElement::GetList used taggedCache inside yourself
	$rs = CIBlockElement::GetList(['CNT' => 'DESC'], ['IBLOCK_ID' => Settings::BIDS_PROTOKOLS], ['CODE']);
	$statusesCnt = [];
	while ($ar = $rs->Fetch()) {
		$ar['CODE_NAME'] = Statistic::getStatusText($ar['CODE']);
		$ar['STATUS_NAME'] = trim($ar['CODE']) == '' ? 'не заполнено' :  $ar['CODE_NAME'];
		$statusesCnt[ $ar['STATUS_NAME'] ] = $ar['CNT'];
	}
	return $statusesCnt;
});
```

### Возможность кэшировать данные в разных местах

Теперь этот класс умеет кэшировать не только в кэше по умолчанию, а еще и в любом наследнике от <code>Bitrix\Main\Data\CacheEngine</code>.
Сами наследники конфигурируются через <code>\Bitrix\Main\DI\ServiceLocator</code>, т.е. в конфигурации [<code>.settings_extra.php</code>](../install/.settings_extra.php) задаем службу:
```php
'services' => [
    'value' => [
        'cache.apc' => [
            'constructor' => static function () {
                // important: set 'sid' in 'cache-value' section of config
                $options = [
                    'type' => 'apcu'
                ];
                return new CacheEngineApc($options);
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
                        ]
                    ];
                    // to avoid using of internal constructor static variables
                    return new class ($options) extends CacheEngineMemcache {
                        /** @noinspection MagicMethodsValidityInspection */
                        public function __construct(array $options = [])
                        {
                            $config = $this->configure($options);
                            $this->connect($config);
                            if (self::$isConnected === false) {
                                throw new ConnectionException('Cant connect to memcache');
                            }
                        }
                    };
                }
           ],
    ],
    'readonly' => true
]
```

И теперь можем отдельные выбороки складывать в кэш apcu, настроенный нами ранее:

```php
$cachedUser = PhpCacher::cache(
    'cached_users' . PhpCacher::getCacheSubDirById($USER->GetID()),  // tag with cluster by collision of id's
    3600, 
    static function () use ($USER) {
	    return \CUser::GetByID( $USER->GetID() )->Fetch();
    },
    'cache.memcache'     /* будет подтянут анонимный класс-наследник Bitrix\Main\Data\CacheEngineMemcache */
);
```

Обратите, внимание, что нужно обязательно указать 'sid' в основной секции настроек <code>'cache'=>'value'</code>, поскольку текущее
ядро битрикса берет эту настройку только из этого места.

На нашем dev-сервере самый быстрый способ чтения из кэша оказался из memcache:
```php
array:4 [▼
  "cache.apc" => array:2 [▼
    "time" => 0.0015130043029785
  ]
  "cache.memcache" => array:2 [▼
    "time" => 0.00025811073303223
  ]
  "cache.files" => array:2 [▼
    "time" => 0.00031805038452148
  ]
]
```