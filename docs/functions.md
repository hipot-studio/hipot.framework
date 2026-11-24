## "Плавающие" функции в фрейморке

"Плавающие" функции называются так, поскольку они не привязаны ни к какому классу (в глобальном пространстве имен).

Рассмотрим их назначение.

### Отладка
1/ Дампер в браузер (свой "велосипед"), но с проверкой прав.
```php
// проверяет права и выводит только для пользователя по константе IS_BETA_TESTER или админа:
// также проверяет доступность d() - в этом случае использует их
// либо использует \Bitrix\Main\Diag\Debug::dump()
my_print_r($var);

// вывести также и debug_backtrace
my_print_r($var,  backtrace: true);
```
2/ Тут же идет функция для формирования backtrace [для скрипта ошибки](error_php_monitoring.md):
Можно ее менять от проекта к проекту в зависимости от задачи отлова ошибок скриптом показа ошибки.
```php
string debug_string_backtrace();
```

### Для работы с HL-блоками

В фреймоврке идет две используемые функции для работы с HL-блоками:

1/ `__getHl(string|int $hiBlockName, bool $staticCache = true): string|false` возвращает имя ORM-класса HL-блока и по умолчанию кеширует результат в пределах вызовов функции. Для пустого имени или неположительного числового ID возвращает `false`.

```php
$list = __getHl('CustomSettings')::getList(['select' => '*'])->fetchAll();
// можно и по числовому ID HL-блока
$item = __getHl(3)::getById(1)->fetch();
```

2/ Реестр различных не-структурированных настроек по сайту. Удобно создать для такого реестра настроек отдельный HL-блок, дернуть один раз в PHP-консоли:
```php
// Создание HL-блока для хранения настроек. Запускается 1 раз для создания нужного блока.
\Hipot\BitrixUtils\HiBlockApps::installCustomSettingsHiBlock();
```
и затем в любых местах сайта (в шаблоне, компонентах, событиях..) использовать получение настройки с "ленивой инициализацией" и кешем:
```php
// hi - фирменный префикс, Cs - CustomSettings - различные настройки.
// default_value - если значения в HL-блоке настроек нет, по аналогии use-case Bitrix\Main\ConOption::get():
// Bitrix\Main\Config\Option::get('my_module', 'my_param', 'default_value');
echo __hiCs('my_param', 'default_value');
```

### Работа с массивами

`array_trim_r()` и `array_get_dot()` устарели. Используйте методы [ArrayTools](uutils.md#массивы-и-объекты) через `Hipot\Utils\UUtils`.

```php
use Hipot\Utils\UUtils;

$data = UUtils::trimRecursive($data);
$userId = UUtils::getDot($data, 'user.profile.id', 0);
```

### Кодирование email

Глобальная функция `mailHideWithMailme()` устарела. Для кодирования email используйте `UUtils::mailHideWithMailme()` совместно с jQuery-плагином [$.mailme](https://github.com/hipot-studio/hipot.framework/blob/master/install/js/hipot.framework/lib.js#L14).
```php
use Hipot\Utils\UUtils;

// info AT hipot-studio DOT com
$hideEmail = UUtils::mailHideWithMailme('info@hipot-studio.com');
```
```html
<span class="mailme"><?=$hideEmail?></span>
<script>
$(function(){
    // transform with browser to a mailto link
    $(".mailme").mailme();
});
</script>
```
