# Описание всех компонентов пространства имен `hipot:` с примерами использования

## hipot:includer
Простой компонент для создания повторно используемых виджетов или блоков на сайте:

```php
<?
\Hipot\Services\BitrixEngine::getAppD0()->IncludeComponent("hipot:includer", "widget.input_xls_file", [
    'IS_REQUIRED' => 'Y'
], $component, ['HIDE_ICONS' => 'Y']);
?>
```

Либо целых страниц:
```php
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");

/**
 * @global $APPLICATION \CMain
 * @global $USER \CUser
 * @global $DB \CDatabase
 * @global $USER_FIELD_MANAGER \CUserTypeManager
 * @global $BX_MENU_CUSTOM \CMenuCustom
 * @global $stackCacheManager \CStackCacheManager
 */
$APPLICATION->SetPageProperty('title', 'Voice Termination Solutions');
$APPLICATION->SetTitle("Expand Your Reach with <br/> Reliable <span class=\"red-text\">Voice Termination Solutions</span>");
?>

<?$APPLICATION->IncludeComponent('hipot:includer', 'page.voice', [], null, ['HIDE_ICONS' => 'Y'])?>

<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?>
```

## hipot:iblock.list, hiblock.list, iblock.section и medialibrary.items.list, medialibrary.collection.list
Это семейство компонентов реализует один подход к построению блоков: параметры вызова описывают выборку, компонент получает и кеширует данные, `result_modifier.php` готовит модель представления, а `template.php` отвечает за вывод. Компоненты отличаются источником данных:

- [`hipot:iblock.list`](components/hipot_iblock_list.md) — элементы инфоблока;
- [`hipot:hiblock.list`](components/hipot_hiblock_list.md) — записи highload-блока;
- [`hipot:iblock.section`](components/hipot_iblock_section.md) — разделы инфоблока;
- [`hipot:medialibrary.items.list`](components/hipot_medialibrary_items_list.md) — элементы коллекций медиабиблиотеки;
- [`hipot:medialibrary.collection.list`](components/hipot_medialibrary_collection_list.md) — список коллекций медиабиблиотеки с довыборкой в них элементов при необходимости.

## hipot:iblock.menu_ext
Пример создания динамичного (_ext) [меню из разделов или элементов инфоблока](components/hipot_iblock_menu_ext.md).

## hipot:ajax
Пример создания [лайков для элементов инфоблока](components/hipot_ajax_likes.md)

## hipot:request.form.system — старый компонент-конструктор форм и обработчиков к ним
[Описание компонента request.form.system](components/hipot_request_form_system.md). Компонент устарел, использовать вместо него `hipot:ajax`.

## hipot:comments.blog
Пример [комментариев модуля блогов на детальных страницах элементов инфоблока](components/comments_blog.md).

