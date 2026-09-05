# Компонент `hipot:comments.blog`

Компонент выводит комментарии `bitrix:blog.post.comment` на детальной странице элемента инфоблока. Для каждого элемента он использует отдельную тему блога и хранит её ID в числовом свойстве инфоблока.

## Требования

- установлен модуль `hipot.framework`, а компонент скопирован в `/local/components/hipot/comments.blog`;
- доступны модули `iblock` и `blog`;
- заранее создан блог, ID которого передаётся в `BLOG_ID`;
- соединение с базой данных должно позволять создать тему блога, свойство инфоблока и изменить элемент при первом обращении.

Первый вызов для элемента может изменить данные: если связанной темы ещё нет, компонент создаст её от имени владельца блога, при необходимости добавит в инфоблок числовое свойство `BLOG_POST_ID` и сохранит в нём ID темы.

## Как работает компонент

1. Загружает блог по `BLOG_ID`.
2. Использует переданный `BLOG_POST_ID` либо читает его из свойства `LINK_IB_PROP_CODE` у элемента.
3. Если тема не найдена, создаёт тему с названием элемента и сохраняет связь в свойстве инфоблока.
4. Подключает стандартный компонент `bitrix:blog.post.comment`. Параметры из `BLOG_POST_COMMENT_PARAMS` заменяют его настройки по умолчанию.

## Параметры

| Параметр | Тип | Обязательный | Значение по умолчанию | Описание |
|---|---:|:---:|---|---|
| `BLOG_ID` | `int` | да | `0` | ID существующего блога, в котором создаются темы для комментариев. |
| `BLOG_POST_ID` | `int` | нет | `0` | ID уже связанной темы. Если не передан, компонент прочитает его из свойства элемента. |
| `IBLOCK_ELEMENT` | `array` | да | — | Данные элемента: `ID`, `IBLOCK_ID` и `NAME`. |
| `LINK_IB_PROP_CODE` | `string` | нет | `BLOG_POST_ID` | Код числового свойства, в котором хранится ID темы блога. |
| `BLOG_POST_COMMENT_TEMPLATE` | `string` | нет | `.default` | Шаблон компонента `bitrix:blog.post.comment`. |
| `BLOG_POST_COMMENTS_COUNT` | `int` | нет | `20` | Количество комментариев на странице. Значения меньше единицы заменяются на `20`. |
| `BLOG_POST_COMMENT_PARAMS` | `array` | нет | `[]` | Дополнительные параметры `bitrix:blog.post.comment`. Имеют приоритет над встроенными настройками. |
| `CACHE_TIME` | `int` | нет | `0` | Время кеширования данных блога и темы в секундах. |

Состав `IBLOCK_ELEMENT`:

| Ключ | Тип | Описание |
|---|---:|---|
| `ID` | `int` | ID элемента инфоблока. |
| `IBLOCK_ID` | `int` | ID инфоблока элемента. |
| `NAME` | `string` | Заголовок и текст создаваемой темы. Если ключ не задан, используется ID элемента. |

Для корректных ссылок в комментариях передавайте `PATH_TO_POST` через `BLOG_POST_COMMENT_PARAMS`. Аналогично можно переопределить `PATH_TO_BLOG`, `PATH_TO_USER`, размеры изображений, режим редактора и остальные параметры стандартного компонента `bitrix:blog.post.comment`.

## Пример использования

```php
<?php
/**
 * @var CMain $APPLICATION
 * @var array $arResult Результат компонента с детальной страницей элемента
 */

$elementUrl = (string)$arResult['DETAIL_PAGE_URL'];
$blogPostId = (int)($arResult['PROPERTIES']['BLOG_POST_ID']['VALUE'] ?? 0);

$APPLICATION->IncludeComponent(
    'hipot:comments.blog',
    '',
    [
        'BLOG_ID' => 7,
        'BLOG_POST_ID' => $blogPostId,
        'IBLOCK_ELEMENT' => [
            'ID' => (int)$arResult['ID'],
            'IBLOCK_ID' => (int)$arResult['IBLOCK_ID'],
            'NAME' => (string)$arResult['NAME'],
        ],
        'LINK_IB_PROP_CODE' => 'BLOG_POST_ID',
        'BLOG_POST_COMMENT_TEMPLATE' => '.default',
        'BLOG_POST_COMMENTS_COUNT' => 20,
        'BLOG_POST_COMMENT_PARAMS' => [
            'PATH_TO_POST' => $elementUrl,
        ],
        'CACHE_TIME' => 604800,
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Замените `7` на ID блога с комментариями. Свойство `BLOG_POST_ID` можно создать заранее как числовое; иначе компонент создаст его при первом обращении.

## Использование с кешируемым `news.detail`

Комментарии лучше подключать после шаблона кешируемого родительского компонента. В шаблоне `news.detail/template.php` сохраните ID темы в `$templateData`:

```php
<?php
$templateData['COMMENTS_BLOG_POST_ID'] =
    (int)($arResult['PROPERTIES']['BLOG_POST_ID']['VALUE'] ?? 0);
```

Затем в `news.detail/component_epilog.php` вызовите `hipot:comments.blog`, используя `$templateData['COMMENTS_BLOG_POST_ID']` как `BLOG_POST_ID`. Сам вызов совпадает с примером выше. Такой вариант оставляет динамический блок комментариев вне HTML-кеша шаблона детальной страницы.

Свойство связи нужно добавить в `PROPERTY_CODE` родительского `news.detail`, чтобы его значение попало в `$arResult['PROPERTIES']`.

## Обслуживание связанных тем

Класс `Hipot\Components\CommentsBlog` содержит два статических метода:

```php
use Hipot\Components\CommentsBlog;

// Удалить из блога все темы, которые не указаны в BLOG_POST_ID элементов инфоблока.
CommentsBlog::clearNotLinkedBlogPosts($blogId, $iblockId, 'BLOG_POST_ID');

// Удалить из блога все темы.
CommentsBlog::clearBlogPosts($blogId);
```

Оба метода удаляют темы без возможности восстановления средствами компонента. `clearNotLinkedBlogPosts()` следует применять только к блогу, выделенному для комментариев одного инфоблока.

## Особенности

- Компонент не предоставляет `.parameters.php`; его параметры задаются в PHP-коде вызова.
- Права на чтение и добавление комментариев проверяет `bitrix:blog.post.comment` по настройкам блога и текущему пользователю.
- При одновременных первых запросах к одному элементу возможны две созданные темы: создание связи не защищено блокировкой. Лишнюю тему можно удалить методом `clearNotLinkedBlogPosts()`.
- После ручного изменения или удаления блога и темы учитывайте `CACHE_TIME`: данные могут оставаться во внутреннем кеше до окончания заданного срока.
