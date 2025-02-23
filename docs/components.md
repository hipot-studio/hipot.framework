# TODO; описать все компоненты с примерами

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