# Мини-фреймворк hipot.framework для работы
(с) hipot, 2017 - 2020<br>
mailto: me AT hipot DOT life

![](https://scontent.fkiv6-1.fna.fbcdn.net/v/t1.0-9/18447157_223812644786808_1001941195412342679_n.png?_nc_cat=106&_nc_ohc=oTCrecS2eIgAQkTHS31xaKgzdXHD2oS0lA1bhpOza8ito7Lda6hvVcYJg&_nc_ht=scontent.fkiv6-1.fna&oh=07c7dcff104f9d0d6c592700d26ef3a3&oe=5E97E72D)

### Требования:
bitrix 20+, PHP 7.2+

# Доступные инструменты:
- библиотека классов /lib/classes для копирования в /local/php_interface/lib/classes
- автозагрузчик к классам lib/simple_loader.php для копирования в /local/php_interface/lib/simple_loader.php
- пример файла /local/php_interface/init.php с подключением деталей фреймворка к битриксу
можно найти в файле include.php
- компоненты в папке install/components для копирования в /local/components
  
### Установка:
- Пока для ручного копирования деталей, 
в дальнешем будет модуль hipot.framework:
- скопировать папку модуля в папку /local/modules/ (для новых проектов) или /bitrix/modules/ для рабочих
- установить в админке модуль, чтобы он зарегистрировал себя
- можно добавить нужные классы в автозагрузчик (PSR-0, см. hipot_code_style_34.pdf)

### ВАЖНО! После включения Abstract Iblock Elements Layer нужно проиндексировать файл /bitrix/cache/generated_iblock_sxem.php
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