# Мини-фреймворк hipot.framework для создания проектов на bitrix
(с) hipot, 2017 - 2022\
mailto: info AT hipot-studio DOT com\
![hipot logo](docs/img/hipot_logo.jpg)

### Требования:
bitrix 20+, PHP 7.4+

# Доступные инструменты и возможности:
- библиотека классов /lib/classes для копирования в /local/php_interface/lib/classes
- автозагрузчик к классам lib/simple_loader.php для копирования в /local/php_interface/lib/simple_loader.php
- пример файла /local/php_interface/init.php с подключением деталей фреймворка к битриксу
можно найти в файле include.php
<ul style="margin-left:100px;"><li> объектная модель-обертка Hipot\IbAbstractLayer\IblockElemLinkedChains
<li>класс для работы с инфоблоками Hipot\BitrixUtils\IblockUtils
<li>api для трансформации изображений Hipot\Utils\Img</li>
<li>с кешированием Hipot\BitrixUtils\PhpCacher

```php
/** @global $USER \CUser */
use Hipot\BitrixUtils\PhpCacher;
$cachedUser = PhpCacher::cache('cached_users' . PhpCacher::getCacheSubDirById($USER->GetID()), 3600, static fn() => $USER);
/** @var $cachedUser \CUser */
\Bitrix\Main\Diag\Debug::dump($cachedUser->GetID());
```
</li>

<li>с магазином Hipot\BitrixUtils\SaleUtils
<li>различные утилиты Hipot\Utils\UnsortedUtils</ul>
  
- компоненты в папке install/components для копирования в /local/components
  
### Установка:
- Пока для ручного копирования деталей, 
в дальнейшем будет модуль hipot.framework:
- скопировать папку модуля в папку /local/modules/ (для новых проектов) или /bitrix/modules/ для рабочих
- установить в админке модуль, чтобы он зарегистрировал себя
- можно добавить нужные классы в автозагрузчик (PSR-0, см. hipot_code_style_34.pdf)

### ВАЖНО! После включения Abstract Iblock Elements Layer нужно проиндексировать файл /bitrix/modules/generated_iblock_sxem.php
Это делается в IDE для подсказок, по аналогии как в битриксе с аннотациями файл bitrix/modules/orm_annotations.php   

см docs/ABSTRACT_IBLOCK_ELEMENT_LAYER.MD

```php
use Bitrix\Main\Loader;
use \Hipot\IbAbstractLayer\IblockElemLinkedChains as hiIblockElemLinkedChains;

Loader::includeModule('hipot.framework');

$resultChains = hiIblockElemLinkedChains::getList(
	['sort' => 'asc'],
	['iblock_id' => 3, 'ID' => 232],
	false, false,
	['ID', 'NAME', 'PROPERTY_CML2_LINK']
);
// see iblock 3 properties to use in getList:
// (new __IblockElementItem_HIPOT3_LAN_3())->PROPERTIES->CML2_LINK

foreach ($resultChains as $ibItem) {
	/** @var $ibItem __IblockElementItem_HIPOT3_LAN_3 */
    echo $ibItem->PROPERTIES->CML2_LINK->CHAIN->PROPERTIES->MANUFACTURER->NAME;
}

var_dump($resultChains);

/* hiIblockElemLinkedChains помимо рекурсивных выборок и объектной модели умеет все, 
 что умеет класс Hipot\BitrixUtils\IblockUtils */
```
![layer example](docs/img/2020-10-15_19-16-26.png)

рис. смотрим какие классы есть после индексации модели инфоблоков в файл для IDE подсказок /bitrix/modules/generated_iblock_sxem.php

![layer example](docs/img/2020-10-15_19-16-57.png)

рис. смотрим какие свойства есть в 3м инфоблоке и какие у них коды для выборки

![layer example](docs/img/2020-10-15_19-17-28.png)

рис. имеем code completion для всех типов даже свойств связанных элементов инфоблоков

### версия 3.0
- собрано все по крупицам

### два ключевых аспекта, концепции фреймворка:

1. Это фасад. Т.е. фреймворк оберточного типа.
2. Это микрофрейморк. Т.е. по-минимуму нужных методов, ничего избыточного и лишнего.

### Правила по коду:

- Стиль написания кода и Структурирование файлов:
docs/hipot_code_style_33.pdf

### наследие wexpert
В файле docs/03.09.2014_SLIDES_10.pptx представлена моя планерка при выпуске версии 1.0
Решил ее тоже сохранить, возможно кому-то поможет.