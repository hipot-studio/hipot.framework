# Abstract Iblock Elements Layer

## Подсказки на выборки CIBlockElement::GetList() на базе компонента hipot:iblock.list

### ЗАЧЕМ ЭТО НУЖНО?
* Удобное использование классов в шаблонах компонент, все поля подсказываются
* Удобная выборка связанных полей связанных элементов в качестве неограниченной вложенности,
  естественно, тоже с подсказками по связанным элементам

![](img/2020-10-15_19-16-26.png)

![](img/2020-10-15_19-17-28.png)

![](img/2020-10-15_19-16-57.png)

#### Ниже описано старое использование библиотеки. Сейчас все подключение происходит автоматически.

### ИСПОЛЬЗОВАНИЕ:
1/ Выяснить соль, либо переопределить ее в dbconn.php

```define('ABSTRACT_LAYER_SAULT', 'MY_SITE_RU');```

Для удобства использования в нескольких проектах следует указать соль, используемая в имени генерированных классов, напр. подключить так

  ```php
  // напр. для сайта www.mysite.ru следует указать (разрешены только символы по маске: [0-9a-zA-Z_])
  define('ABSTRACT_LAYER_SAULT', 'MY_SITE_RU');
  /*
  * Если не установлена, по-умолчанию константа ABSTRACT_LAYER_SAULT принимает значение 
  * трансформированного имени домена:
  * Напр. www.good-site.wexpert.ru --> GOOD_SITE_WEXPERT_RU
  */
  ```

2/ Имя класса пишется следующим образом: ```__hiIblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#```
```#ABSTRACT_LAYER_SAULT#``` - соль заданная либо по домену
```#IBLOCK_ID#``` - номер инфоблока, итерацию по которому мы в данный момент делаем, см. 4.1.

``` /** @var ____hiIblockElementItem_MY_SITE_RU_10 $oItem * / ```

3/ ВНИМАНИЕ!!! Естественно, чтобы вся эта система работала, у имен свойств нужно задавать корректные
символьные имена (шаблон [A-Za-z0-9_]+), т.к. символьные коды свойств превращаются в поля объектов

4.1/ В шаблоне компонента <code>hipot:iblock.list</code> можем писать:
```php 
 //template.php
 use Hipot\IbAbstractLayer\Types\IblockElementItem;
 
 foreach ($aResult['ITEMS'] as $arItem) {
    // имя класса пишется следующим образом: __WeIblockElementItem_#ABSTRACT_LAYER_SAULT#_#IBLOCK_ID#,
    // мы ведь знаем какого инфоблока список выводится, не так ли?
    // в примере заполняем абстрактный уровень 10го инфоблока
    // #ABSTRACT_LAYER_SAULT# - указанная нами соль, либо сгеренированная автоматически по имени домена

    // шаблон подсказки по типам можно смело закидывать в SNIPPETS (в шаблоны вашего IDE)

    /* @var $oItem __hiIblockElementItem_MY_SITE_RU_10 */
    /** @var __hiIblockElementItem_MY_SITE_RU_10 $oItem */

    $oItem = new IblockElementItem($arItem);

    echo $oItem->NAME; // подсказывает все стандартные поля инфоблока

    // предположим, что в 10м инфоблоке есть строковое свойство code1
    echo $oItem->PROPERTIES->code1->VALUE; // подсказывает все свойства инфоблока и их поля

    // code2 у нас множественное, давайте выведем первое значение:
    $arMultiple = $oItem->PROPERTIES->code2;
    echo $arMultiple[0]->VALUE;

    // предположим, что у нас есть свойство типа "HTML/Text" с кодом opis, выведем его значение
    echo $oItem->PROPERTIES->opis->VALUE['TEXT'];

    // вывод пути к файлу в свойстве
    echo $oItem->PROPERTIES->file->FILE_PARAMS->SRC;
 }
```

4.2/ Выборка цепочек с кешем

```php 
 // component.php
 // QUERY 1 MAIN
 $rsItems = \CIBlockElement::GetList($arOrder, $arFilter, false, $arNavParams, $arSelect);
 
 // создаем объект, должен создаваться до цикла по элементам, т.к. в него складываются
 // уже выбранные цепочки в качестве кеша
 $obChainBuilder = new Hipot\IbAbstractLayer\IblockElemLinkedChains();
 
 while ($arItem = $rsItems->GetNext()) {
    // QUERY 2
    $db_props = \CIBlockElement::GetProperty($arParams["IBLOCK_ID"], $arItem['ID'],
                                        array("sort" => "asc"), array("EMPTY" => "N"));
    while ($ar_props = $db_props->GetNext()) {

        // довыборка цепочек глубиной 3
        if ($ar_props['PROPERTY_TYPE'] == 'E') {
            // инициализация должна происходить перед каждым вызовом getChains_r
            // с указанием выбираемой вложенности
            $obChainBuilder->init(3);
            $ar_props['CHAIN'] = $obChainBuilder->getChains_r( $ar_props['VALUE'] );
        }
        if ($ar_props['PROPERTY_TYPE'] == 'F') {
            $ar_props['FILE_PARAMS'] = \CFile::GetFileArray($ar_props['VALUE']);
        }

        if ($ar_props['MULTIPLE'] == "Y") {
                $arItem['PROPERTIES'][ $ar_props['CODE'] ][] = $ar_props;
            } else {
                $arItem['PROPERTIES'][ $ar_props['CODE'] ] = $ar_props;
            }
        }

        $arResult["ITEMS"][] = $arItem;
    }
 }
 
 // освобождаем память от цепочек
 unset($obChainBuilder);
 
 
 // template.php
 use Hipot\IbAbstractLayer\Types\IblockElementItem;
 
 foreach ($aResult['ITEMS'] as $arItem) {
     // имя класса пишется следующим образом: __hiIblockElementItem#IBLOCK_ID#,
     // мы ведь знаем какого инфоблока список выводится, не так ли?
     // в примере заполняем абстрактный уровень 10го инфоблока
     // (возникло предложение сделать по символьному коду инфоблока, думаю резонное, т.е. __hiIblockElementItem#IBLOCK_CODE#)
     $oItem = new IblockElementItem($arItem);
  
     // допуспим link2 у нас привязка к инфоблоку, у которого есть еще одна привязка link3
     // выводим имя привязанного элемента в привязанном
     // те. у нас связь: текущий инфоблок связан по link2 с еще одним инфоблоком, тот в свою очередь еще с одним.
     // задача вывести имя связанного элемента через один
     // ПО ЭТОМУ ВСЕМУ ЕСТЬ АВТОКОМПЛИТ!! )))
     echo $oItem->PROPERTIES->link2->CHAIN->PROPERTIES->link3->CHAIN->NAME;
  
     // покажем имя первого автора книги
     $arWriters = $oItem->PROPERTIES->writers;
     // выводим имя первого (в CHAIN все поля связанного элемента)
     echo $arWriters[0]->CHAIN->NAME;
 }
```

5/ За заполненностью полей, естественно, должен следить сам разработчик, т.к. схема предоставляет
просто удобный способ написания кода с автокомплитом. Т.е. класс - это абстрактный уровень
для результатов работы компонента iblock.list

6/ Для обновления файла с классами нужно просто удалить файл
```$_SERVER['DOCUMENT_ROOT'] . '/bitrix/cahce/generated_sxem.php'```
открыть сайт, чтобы файл сгенерировался и открыть его в eclipse, чтобы проиндексировались классы
При изменении структуры инфоблоков схема также обновляется.

7/ В случае свойств привязок к инфоблокам, обязательно должно быть указано, к какому инфоблоку
привязано свойство элемента, иначе как построить схему привязок?

8/ Более "прокачанный" пример использования:
```php
use Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValue;
use Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValueLinkElem;
use Hipot\IbAbstractLayer\Types\IblockElementItemPropertyValueFile;
use \Hipot\IbAbstractLayer\IblockElemLinkedChains as hiIblockElemLinkedChains;

d(\CFile::FormatSize(memory_get_usage(true)));

$resultChains = hiIblockElemLinkedChains::getList(
    ['ID' => 'ASC'],
    ['IBLOCK_ID' => 3, '>=ID' => 232],
    false, ['nTopCount' => 1],
    ['ID', 'NAME', 'TIMESTAMP_X', 'ACTIVE', 'PROPERTY_CML2_LINK', 'PROPERTY_CML2_LINK.PROPERTY_NEWPRODUCT']
);

// see iblock 3 properties to use in getList:
foreach ($resultChains as $ibItem) {
    /* @var $ibItem \__IblockElementItem_BX_LAST_ClothesOffers_3 */

    // link element params
    d($ibItem->PROPERTIES->CML2_LINK->CHAIN->PROPERTIES->MANUFACTURER->NAME);
    // link element params via getList
    d($ibItem->PROPERTY_CML2_LINK_PROPERTY_NEWPRODUCT_ENUM_ID);
    // file params
    $f = $ibItem->PROPERTIES->MORE_PHOTO[0]->FILE_PARAMS->SRC;
    // has functional of Container
    d($ibItem->PROPERTIES->count(), $ibItem->PROPERTIES->CML2_LINK->CHAIN->toArray());

    // standard element fields
    d($ibItem->ID . '::' . $ibItem->NAME . '::');

    // iterator to main good props
    foreach ($ibItem->PROPERTIES->CML2_LINK->CHAIN->PROPERTIES as $propCode => $prop) {

        $getPropValue = static function ($prop) {
            if ($prop->isFile()) {
                /** @var IblockElementItemPropertyValueFile $prop */
                $value = $prop->FILE_PARAMS->SRC;
            } else if ($prop->isLinkElem()) {
                /** @var IblockElementItemPropertyValueLinkElem $prop */
                $value = $prop->CHAIN->NAME;
            } else {
                $value = $prop->VALUE;
            }
            return $value;
        };

        /** @var IblockElementItemPropertyValue | IblockElementItemPropertyValue[] $prop */

        // multiple props - is array of objects
        if (is_array($prop)) {
            $values = [];
            foreach ($prop as $propValue) {
                /** @var IblockElementItemPropertyValue $propValue */
                $values[] = $getPropValue($propValue);
            }
            $value = implode(', ', $values);
        } else {
            $value = $getPropValue($prop);
        }
        d($propCode . ' --> ' . $value);
    }
}

dd(\CFile::FormatSize(memory_get_usage(true)));
```

[CHANGELOG](ABSTRACT_IBLOCK_ELEMENT_LAYER_CHANGELOG.MD)