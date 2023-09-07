k #Abstract Iblock Elements Layer

 ## Подсказки на выборки CIBlockElement::GetList() на базе компонента hipot:iblock.list

 ### ЗАЧЕМ ЭТО НУЖНО?
 * Удобное использование классов в шаблонах компонент, все поля подсказываются
 * Удобная выборка связанных полей связанных элементов в качестве неограниченной вложенности,
 естественно, тоже с подсказками по связанным элементам

![](https://scontent.fkiv6-1.fna.fbcdn.net/v/t1.0-9/78401415_741880406313360_5240495133509550080_n.jpg?_nc_cat=111&_nc_ohc=C7eLlmuC1HAAQksv5B_lvd7BGoR4TOf_whDOa7d5FIIpIbQ7lfY4OBXgA&_nc_ht=scontent.fkiv6-1.fna&oh=2c09b4f4e4689d0d7038bcab20f6cce0&oe=5EA0044D)

![](https://scontent.fkiv6-1.fna.fbcdn.net/v/t1.0-9/73504662_741880556313345_8864736740015865856_n.jpg?_nc_cat=104&_nc_ohc=oaW6mnwI16YAQmbm4DTZVSeHgN1IfUzt0qBPeIch35fSpfJwuTXlfsDmA&_nc_ht=scontent.fkiv6-1.fna&oh=ccbea221ea20908435cdba284f2e9fd1&oe=5E9B8626)

![](https://scontent.fkiv6-1.fna.fbcdn.net/v/t1.0-9/77107997_741880622980005_4024262980576215040_n.jpg?_nc_cat=109&_nc_ohc=yyz8JMQDkrYAQmtHiyP05Lg4HCx8NK_wzXycBewU177vFH1pHU-m3QddA&_nc_ht=scontent.fkiv6-1.fna&oh=c1d774bcb02762abf32a6879b007d9b2&oe=5E681A07)

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
  
 ``` /** @var __WeIblockElementItem_MY_SITE_RU_10 $oItem * / ```

 3/ ВНИМАНИЕ!!! Естественно, чтобы вся эта система работала, у имен свойств нужно задавать корректные
     символьные имена (шаблон [A-Za-z0-9_]+), т.к. символьные коды свойств превращаются в поля объектов 

 4.1/ В шаблоне компонента hipot:iblock.list можем писать:
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

[CHANGELOG](ABSTRACT_IBLOCK_ELEMENT_LAYER_CHANGELOG.MD)