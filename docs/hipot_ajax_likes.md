# Компонент лайков
Компонент позволяет добавить возможность ставить "лайк" элементам инфоблока и отображение их кол-ва. Компонент не зависит от кеша родительского компонента. Для каждого посетителя всегда будут актуальные блоки лайков не зависимо от кеша самого списка.

## Пример подключения:

```php
// bitrix/catalog.detail/templates/.default/template.php

/** @var array $arResult */
/** @var CBitrixComponent $component */

<?$APPLICATION->IncludeComponent('hipot:ajax', 'iblock_like', [
    'ITEM'          => $arResult,
    'MODE'          => 'init'
], $component, ['HIDE_ICONS' => true]);
?>
```

--- 

После подключения компонента, необходимо добавить свойство в инфоблок ***`POST_LIKED_CNT`*** которое будет отвечать за кол-во лайков для текущего элемента. В момент иницализации компонента, рендерится `div` элемент, саму подгрузку инициирует `js-скрипт` в шаблоне `iblock_like`. Для загрузки интерфейса используется наш [ajax][1] компонент.


## Диграмма инициализации и взаимодействия с методами ajax компонента
```mermaid
    graph TB
        A[Компонент инициализации] --> B[Рендерит div-контейнер]
        B --> C[Подключает JS-скрипт <br/> iblock_like]
        
        C --> D[AJAX Компонент]
        D --> E[loadIblockLikeTemplatesAction </br> Загрука шаблона]
        D --> F[saveIblockLikeAction <br/> Сохранение лайка]
        
        E --> G[Свойство инфоблока <br/> POST_LIKED_CNT]
        F --> G
        F --> H[(Обновляет данные)]
        E --> I[Читает данные]
```

## Методы ajax компонента:
-  [`loadIblockLikeTemplatesAction`][2] - Чтобы нарисовать интерфейс с кнопкой лайка
-  [`saveIblockLikeAction`][3] - Чтобы сохранить клик в базе данных

## Пример работы компонента
![img](/docs/img/iblocklike.gif)

[1]:https://github.com/hipot-studio/hipot.framework/tree/master/install/components/hipot/ajax
[2]:https://github.com/hipot-studio/mgu-russian.com/blob/master/local/components/mgu/ajax/class.php#L131
[3]:https://github.com/hipot-studio/mgu-russian.com/blob/master/local/components/mgu/ajax/class.php#L168