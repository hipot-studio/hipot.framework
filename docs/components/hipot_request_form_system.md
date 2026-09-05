# `hipot:request.form.system`: обработка форм и отправка почтовых событий

`hipot:request.form.system` — старый процедурный компонент для форм обратной связи, заявок и заказов звонка. Он проверяет обязательные поля и CAPTCHA, при необходимости создаёт элемент инфоблока, собирает поля почтового события, запускает проектные обработчики и выводит шаблон формы. Компонент умеет работать при обычной отправке страницы и в legacy AJAX-сценарии.

Компонент помечен в исходном коде как `Form maker framework`, версия 3.1, 2018 год. В нём используются характерные для старых проектов паттерны: глобальные массивы, прямое чтение `$_POST` и `$_FILES`, функции с именами, составленными из `POST_NAME`, `CEvent::Send`, `CModule`, jQuery и отдельный PHP-файл AJAX-обработчика. Для существующего проекта его можно поддерживать как совместимый механизм. Для новой формы сначала оцените перенос сценария на controller/action, `Bitrix\\Main\\Request`, штатный response и отдельный сервис отправки.

## Что делает компонент

- различает формы по параметру `POST_NAME`;
- принимает уже подготовленный POST-массив через `_POST`;
- проверяет сессию Битрикса, обязательные поля, e-mail и CAPTCHA;
- может создать элемент инфоблока до отправки письма;
- может сформировать HTML письма, повторно подключив собственный почтовый шаблон;
- отправляет почтовое событие через `CEvent::Send` либо передаёт отправку проектному обработчику;
- поддерживает четыре точки расширения в файлах `custom_*.php`;
- выводит обычную или AJAX-форму одним из комплектных шаблонов.

Компонент не использует кеш: результат зависит от текущего запроса и сессии.

## Последовательность выполнения `component.php`

1. Нормализует `DEFAULT_SEND_MAIL` и `SET_MAIN_JS_CHECKERS`.
2. Подключает языковой файл выбранного публичного шаблона: `templates/<имя>/lang/<LANGUAGE_ID>/template.php`.
3. Подключает `custom_spam_checks.php` и вызывает `CustomSpamChecks_<POST_NAME>($arParams)`, если такая функция объявлена. Проверка выполняется ещё до сброса `$arResult` и до проверки сессии.
4. Обнуляет `$arResult`.
5. Подключает `custom_select.php`, вызывает `CustomRequestSelects_<POST_NAME>()` и записывает возвращённое значение в `$arResult['CUSTOM_SELECTS']`. Эта стадия выполняется при каждом показе компонента, даже без отправленной формы.
6. Нормализует `$arParams['_POST'][$arParams['POST_NAME']]` в массив.
7. Начинает обработку только при непустом массиве формы и успешном `check_bitrix_sessid()`.
8. Проверяет `REQ_FIELDS`. Поле с именем `mail` дополнительно проверяется функцией `check_email()`.
9. При `USE_CAPTCHA => 'Y'` проверяет `captcha_word` и `captcha_sid`.
10. Если ошибок нет, собирает `$MAIL_VARS`: скалярные значения копируются как есть, вложенные массивы преобразуются в строку через `implode(', ', ...)`.
11. При заполненном `ADD_ELEMENT['FIELDS']` создаёт элемент инфоблока. ID попадает в `$arResult['ADDED_ID']` и `$MAIL_VARS['ADDED_ID']`; ошибка — в `$arResult['error']['add']`.
12. При заданном `TEMPLATE` временно переключает шаблон текущего компонента, повторно вызывает `IncludeComponentTemplate()` и сохраняет полученный HTML в `$MAIL_VARS['HTML']`. Затем восстанавливает имя публичного шаблона.
13. Подключает `custom_mailvars.php` и вызывает `CustomRequestMailVars_<POST_NAME>($arParams['_POST'], $arResult, $MAIL_VARS)`. Обработчик может изменить `$MAIL_VARS` по ссылке.
14. При `DEFAULT_SEND_MAIL => 'Y'` вызывает `CEvent::Send()`.
15. Подключает `custom_handlers.php` и вызывает `CustomRequestHandler_<POST_NAME>($arParams['_POST'], $MAIL_VARS, $arParams)`.
16. Записывает в сессию флаг `READY_request_<POST_NAME> = 'Y'` и при `REDIRECT => 'Y'` выполняет `LocalRedirect()`.
17. Для CAPTCHA получает новый код, при необходимости подключает общий JavaScript и CSS, затем выводит публичный шаблон, если `NO_TEMPLATE` не равен `Y`.

При ошибке валидации этапы создания элемента, подготовки письма и обработчики после отправки не выполняются. Публичный шаблон получает `$arResult['error']` и выводит форму повторно.

## Параметры

У компонента нет `.parameters.php`, поэтому параметры задаются непосредственно в PHP-вызове.

| Параметр | Тип | По умолчанию | Описание |
|---|---:|---|---|
| `_POST` | `array` | `[]` фактически | Подготовленные данные всего POST-запроса. Компонент ищет внутри массив с ключом `POST_NAME`. Исторический контракт ожидает результат рекурсивного `htmlspecialcharsEx`; исходные данные при необходимости следует хранить отдельно. |
| `POST_NAME` | `string` | — | Обязательный идентификатор формы и имя массива её полей, например `feedback`. Он также становится суффиксом функций `Custom..._<POST_NAME>`. Исходная документация допускает `[a-z0-9_]`. |
| `REQ_FIELDS` | `array` | `[]` | Обязательные поля. Поддерживаются плоский список `['name', 'mail']` и вложенная структура `['customer' => ['name', 'mail']]`. Любое обязательное поле с именем `mail` проверяется как e-mail. |
| `USE_CAPTCHA` | `Y/N` | `N` фактически | Включает проверку стандартной CAPTCHA Битрикса по полям `captcha_word` и `captcha_sid`, а также создаёт `$arResult['CAPTCHA_CODE']` для шаблона. |
| `ADD_ELEMENT` | `array` | `[]` | Настройки создания элемента инфоблока: `FIELDS` передаются в `CIBlockElement::Add`, `PROPS` — как `PROPERTY_VALUES`. Операция запускается только при непустом `FIELDS`. |
| `TEMPLATE` | `string` | `''` | Имя дополнительного шаблона компонента, который рендерится в `$MAIL_VARS['HTML']`. Обычно это `mail`; в почтовом шаблоне Битрикса используется макрос `#HTML#`. |
| `DEFAULT_SEND_MAIL` | `Y/N` | `Y` | Отправлять почтовое событие встроенным вызовом `CEvent::Send`. Установите `N`, если отправкой полностью занимается `custom_handlers.php`. |
| `EVENT_TYPE` | `string` | — | Символьный код типа почтового события Битрикса. |
| `SITE_ID` | `string` | текущее значение окружения | ID сайта для `CEvent::Send`. Компонент читает `$arParams['SITE_ID']`; обычно его следует передавать явно. |
| `DUBLICATE_MAIL` | `Y/N` | `N` фактически | Значение четвёртого аргумента `CEvent::Send`: дублировать письмо на адрес из настроек главного модуля. В публичном контракте сохранено историческое ошибочное написание `DUBLICATE_MAIL`. |
| `EVENT_ID` | `int` | `0` | ID конкретного почтового шаблона. При значении больше нуля передаётся в `CEvent::Send`, иначе компонент разрешает Битриксу выбрать шаблон по типу события и сайту. |
| `REDIRECT` | `Y/N` | `N` фактически | Выполнить перенаправление после успешной обработки. |
| `REDIRECT_URL` | `string` | текущая страница | URL для перенаправления. |
| `NO_TEMPLATE` | `Y/N` | `N` фактически | Не выводить публичный шаблон компонента. Серверная обработка при этом сохраняется. |
| `SET_MAIN_JS_CHECKERS` | `Y/N` | `Y` | Подключить `js/main_checker_script.js` с клиентской валидацией и AJAX-отправкой старых шаблонов. Только точное значение `N` отключает скрипт. |

Комплектные шаблоны используют ещё два параметра, хотя сам `component.php` их не обрабатывает:

| Параметр | Шаблон | Описание |
|---|---|---|
| `AJAX_CALL` | `recall_good_ajax` | При `Y` выводит только обновляемое внутреннее содержимое, без повторной оболочки `.ajax_form`. |
| `ELEMENT_ID` | `recall_good_ajax` | Записывается в скрытое поле `<POST_NAME>[good_id]` для заявки по конкретному товару или материалу. |

Параметр `NO_REDIRECT`, встречающийся в комплектном `ajax/request.php`, компонентом не читается. Для управления переходом используется `REDIRECT`.

## Результат и сообщения

В шаблоне доступны:

```php
$arResult = [
    'CUSTOM_SELECTS' => [], // результат CustomRequestSelects_<POST_NAME>()
    'error' => [             // появляется при ошибках
        'phone' => '...',
        'mail' => '...',
        'captcha' => '...',
        'add' => '...',
    ],
    'ADDED_ID' => 123,       // после успешного ADD_ELEMENT
    'MAIL_VARS' => [],       // только во время рендера шаблона TEMPLATE
    'CAPTCHA_CODE' => '...', // при USE_CAPTCHA => Y
];
```

Тексты обязательных полей берутся из языкового файла публичного шаблона:

- `RTN_<field>` — поле не заполнено;
- `RTEN_<field>` — неверный e-mail;
- `REGISTER_WRONG_CAPTCHA` — неверная CAPTCHA;
- `CALL_REQUIRE_ERRORS` — заголовок блока ошибок;
- `CALL_FRM_OK_SEND` — сообщение об успехе.

Для каждого публичного шаблона нужен файл `templates/<template>/lang/<language>/template.php`: `component.php` подключает его без проверки существования. В поставке такие файлы есть у `order`, `recall_good_ajax` и `recall_popup_ajax`. Шаблон `mail` предназначен для параметра `TEMPLATE`, а не для показа формы. Перед использованием `.default_ajax` добавьте ему языковой файл в проектном шаблоне.

## Порядок и назначение `custom_*.php`

Все четыре файла лежат в корне компонента и подключаются через `include_once`. Имя функции образуется из фиксированного префикса и `POST_NAME`, поэтому формы с разными идентификаторами могут иметь разные обработчики в одном файле.

| Порядок | Файл и функция | Когда вызывается | Назначение |
|---:|---|---|---|
| 1 | `custom_spam_checks.php` → `CustomSpamChecks_<POST_NAME>(array &$arParams)` | До инициализации результата и до основной валидации | Отклонить подозрительную отправку, изменив `$arParams`, обычно удалив `$arParams['_POST'][POST_NAME]`. |
| 2 | `custom_select.php` → `CustomRequestSelects_<POST_NAME>()` | При каждом подключении компонента | Подготовить справочники и варианты полей. Возвращённое значение доступно как `$arResult['CUSTOM_SELECTS']`. |
| 3 | `custom_mailvars.php` → `CustomRequestMailVars_<POST_NAME>(array $_post, array $arResult, array &$mailVars)` | После валидации, создания элемента и рендера `TEMPLATE`, непосредственно перед стандартной отправкой | Добавить или преобразовать макросы почтового события. |
| 4 | `custom_handlers.php` → `CustomRequestHandler_<POST_NAME>(array $_post, array $mailVars, array $arParams)` | После стандартного `CEvent::Send`, перед success-флагом и redirect | Выполнить дополнительное действие: отправить письмо своим транспортом, передать заявку интеграции или обработать вложение. |

`custom_mailvars.php` выполняется после формирования `MAIL_VARS['HTML']`. Поэтому добавленная им переменная доступна почтовому событию как отдельный макрос, но уже не попадёт внутрь ранее отрендеренного HTML. Если значение нужно в HTML, подготовьте его раньше: передайте параметром, получите через `custom_select.php` или сформируйте непосредственно в почтовом шаблоне.

Комплектный `CustomRequestHandler_order()` показывает старую отправку вложения через проектный класс `AttachesBmailer` и напрямую читает `$_FILES`. Этот класс не входит в компонент. Такой обработчик следует заменить проектной реализацией с проверкой `UPLOAD_ERR_OK`, размера, допустимого MIME-типа и имени файла; при собственной отправке задайте `DEFAULT_SEND_MAIL => 'N'`, чтобы письмо не ушло дважды.

Файлы находятся внутри поставляемого компонента, поэтому изменение или обновление пакета затрагивает все формы. Храните проектную версию компонента в локальном пространстве имён либо переносите расширения в проектный код и оставляйте в `custom_*.php` только тонкие вызовы этих сервисов.

### Пример четырёх обработчиков для формы `feedback`

```php
<?php

use Bitrix\\Main\\Loader;

function CustomSpamChecks_feedback(array &$arParams): void
{
    $form = (array)($arParams['_POST']['feedback'] ?? []);
    $message = (string)($form['message'] ?? '');

    if (preg_match('~(?:<a\\s|\\[url\\s*=|https?://)~iu', $message)) {
        unset($arParams['_POST']['feedback']);
    }
}

function CustomRequestSelects_feedback(): array
{
    return [
        'departments' => [
            'sales' => 'Отдел продаж',
            'support' => 'Поддержка',
        ],
    ];
}

function CustomRequestMailVars_feedback(array $_post, array $arResult, array &$mailVars): void
{
    $departmentCode = (string)($_post['feedback']['department'] ?? '');
    $departments = (array)($arResult['CUSTOM_SELECTS']['departments'] ?? []);

    $mailVars['department_name'] = (string)($departments[$departmentCode] ?? 'Не указан');
}

function CustomRequestHandler_feedback(array $_post, array $mailVars, array $arParams): void
{
    // Здесь вызывается проектный сервис CRM, очереди или собственной отправки.
    // Исключение не следует скрывать: иначе компонент всё равно установит success-флаг.
}
```

В рабочем коде оберните объявления в `function_exists`, если файл может подключаться из нескольких копий компонента.

## Комплектные шаблоны

| Шаблон | Сценарий | Особенности |
|---|---|---|
| `.default_ajax` | Минимальная AJAX-форма с именем и e-mail | Демонстрационная заготовка. Требует собственного языкового файла, кнопки отправки и атрибута `uri`. |
| `order` | Обычная заявка | Выводит настоящий `<form method="post" enctype="multipart/form-data">`, поля ФИО, компании, должности, телефона, e-mail, сообщения и файла. Подходит как основа статической формы. |
| `recall_good_ajax` | AJAX-заявка по элементу | Передаёт `good_id` из `ELEMENT_ID`; при `AJAX_CALL => 'Y'` возвращает только внутренний HTML для замены формы. |
| `recall_popup_ajax` | AJAX-заказ звонка во всплывающем окне | Использует глобальные `ShowWin`/`HideWin`, jQuery и плагин `.center()`. |
| `mail` | HTML письма | Не является публичной формой. Подключается через `TEMPLATE => 'mail'` и получает данные в `$arResult['MAIL_VARS']`. |

Шаблоны — примеры 2018 года, а не готовый универсальный UI. Перед использованием проверьте HTML, доступность, экранирование значений и JavaScript-зависимости. В частности, общий скрипт ожидает глобальные `isEmpty()`, `isMail()` и jQuery-плагин `serializeJSON()`; popup-шаблон дополнительно ожидает `.center()`. Серверная проверка остаётся обязательной независимо от клиентских проверок.

## Пример обычной формы `order`

Сначала подготовьте POST через request API Битрикса, сохранив совместимый экранированный контракт компонента:

```php
<?php

use Bitrix\\Main\\Context;

/** @global CMain $APPLICATION */

$escapeArray = static function (array $values) use (&$escapeArray): array {
    foreach ($values as $key => $value) {
        $values[$key] = is_array($value)
            ? $escapeArray($value)
            : htmlspecialcharsEx((string)$value);
    }

    return $values;
};

$post = Context::getCurrent()->getRequest()->getPostList()->toArray();
$escapedPost = $escapeArray($post);

$APPLICATION->IncludeComponent(
    'hipot:request.form.system',
    'order',
    [
        '_POST' => $escapedPost,
        'POST_NAME' => 'order',
        'REQ_FIELDS' => ['fio', 'company', 'phone', 'message'],
        'EVENT_TYPE' => 'ORDER_REQUEST',
        'EVENT_ID' => 0,
        'SITE_ID' => SITE_ID,
        'DUBLICATE_MAIL' => 'N',
        'DEFAULT_SEND_MAIL' => 'Y',
        'TEMPLATE' => 'mail',
        'ADD_ELEMENT' => [],
        'USE_CAPTCHA' => 'N',
        'REDIRECT' => 'Y',
        'REDIRECT_URL' => '/request/success/',
        'SET_MAIN_JS_CHECKERS' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

В типе почтового события `ORDER_REQUEST` доступны макросы из полей формы (`#fio#`, `#company#`, `#phone#`, `#mail#`, `#message#`) и `#HTML#`. Комплектный `mail` рассчитан на поля `name`, `phone`, `mail`, `comment`, поэтому для `order` создайте проектную копию почтового шаблона с совпадающими именами полей либо не задавайте `TEMPLATE` и используйте отдельные макросы.

Шаблон `order` содержит поле файла, но сам компонент его не валидирует и не прикрепляет. Это делает только проектный `CustomRequestHandler_order()`.

## Пример AJAX-заявки по элементу

Начальный вызов выводит оболочку `recall_good_ajax`:

```php
<?php

use Bitrix\\Main\\Context;

/** @global CMain $APPLICATION */

$post = Context::getCurrent()->getRequest()->getPostList()->toArray();

$APPLICATION->IncludeComponent(
    'hipot:request.form.system',
    'recall_good_ajax',
    [
        '_POST' => $post,
        'POST_NAME' => 'recall',
        'REQ_FIELDS' => ['phone'],
        'ELEMENT_ID' => $elementId,
        'EVENT_TYPE' => 'RECALL_REQUEST',
        'EVENT_ID' => 0,
        'SITE_ID' => SITE_ID,
        'DEFAULT_SEND_MAIL' => 'Y',
        'REDIRECT' => 'N',
        'AJAX_CALL' => 'N',
        'SET_MAIN_JS_CHECKERS' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
?>
<script>
document.getElementById('recall_form')?.setAttribute(
    'uri',
    '/bitrix/components/hipot/request.form.system/ajax/request.php'
);
</script>
```

Общий `main_checker_script.js` временно оборачивает `.ajax_form_wrapper` в `<form>`, сериализует поля, добавляет `__form__ = 'recall'` из ID `recall_form` и отправляет POST на `uri`. Ответ заменяет содержимое контейнера `#recall_form`.

Комплектный `ajax/request.php`:

1. принимает только запрос с заголовком `X-Requested-With: XMLHttpRequest` и пустым верхнеуровневым `token`;
2. загружает пролог Битрикса;
3. рекурсивно экранирует `$_POST`;
4. принимает только `__form__ === 'recall'`;
5. при наличии `good_id` довыбирает элемент инфоблока и добавляет в POST его имя и URL;
6. повторно подключает компонент с `AJAX_CALL => 'Y'` и тем же `POST_NAME`.

Это жёстко заданный legacy-маршрут. Для другого `POST_NAME`, другого набора параметров или другого шаблона нужен отдельный проектный endpoint. Проверка заголовка `X-Requested-With` и скрытого поля сама по себе не является полноценной защитой от спама; основная обработка дополнительно требует корректный `sessid`, а бизнес-ограничения следует реализовать в `CustomSpamChecks_<POST_NAME>` или в современном endpoint.

## Пример popup-формы обратного звонка

`recall_popup_ajax` подключается теми же параметрами, что предыдущий пример, но без `ELEMENT_ID`:

```php
$APPLICATION->IncludeComponent(
    'hipot:request.form.system',
    'recall_popup_ajax',
    [
        '_POST' => $escapedPost,
        'POST_NAME' => 'callback',
        'REQ_FIELDS' => ['phone'],
        'EVENT_TYPE' => 'CALLBACK_REQUEST',
        'SITE_ID' => SITE_ID,
        'DEFAULT_SEND_MAIL' => 'Y',
        'REDIRECT' => 'N',
        'SET_MAIN_JS_CHECKERS' => 'Y',
    ],
    false,
    ['HIDE_ICONS' => 'Y'],
);
```

Для него нужен endpoint, который повторно вызывает компонент с `POST_NAME => 'callback'`, шаблоном `recall_popup_ajax` и `AJAX_CALL => 'Y'`, а оболочке `#callback_form` требуется атрибут `uri`. Проектный шаблон также должен подключить реализацию `.center()` либо заменить `ShowWin`/`HideWin` современным диалогом.

## Добавление элемента инфоблока

`ADD_ELEMENT` формируется вызывающим кодом из уже проверенных и нормализованных значений:

```php
'ADD_ELEMENT' => [
    'FIELDS' => [
        'IBLOCK_ID' => $requestIblockId,
        'NAME' => 'Заявка от ' . $customerName,
        'ACTIVE' => 'N',
    ],
    'PROPS' => [
        'PHONE' => $phone,
        'EMAIL' => $email,
        'MESSAGE' => $message,
    ],
],
```

Компонент не проверяет права на инфоблок, типы свойств и допустимость значений. Не передавайте `FIELDS` или `PROPS` напрямую из произвольного пользовательского массива. При ошибке добавления письмо и последующие обработчики не запускаются, а текст `CIBlockElement::LAST_ERROR` попадает в `$arResult['error']['add']`.

## Ограничения старой реализации

- результат `CEvent::Send()` не проверяется: после вызова компонент устанавливает success-флаг даже при проблеме постановки письма в очередь;
- антиспам-хук может только изменить входные параметры до обработки; комплектный пример молча удаляет данные формы;
- HTML почтового шаблона и комплектные публичные шаблоны местами выводят значения без явного контекстного экранирования;
- имя поля `mail` имеет специальное значение, а другое имя e-mail не валидируется автоматически;
- вложенные значения `$MAIL_VARS` сворачиваются только одним `implode`, сложные структуры для этого контракта не подходят;
- загрузка файла не входит в основной pipeline;
- встроенный AJAX endpoint поддерживает только `recall` и содержит проектную довыборку элемента инфоблока;
- общий JavaScript зависит от jQuery и внешних функций/плагинов, которых нет в каталоге компонента;
- `READY_request_<POST_NAME>` хранится в сессии и снимается самим шаблоном после показа сообщения;
- встроенные шаблоны следует считать исходными примерами и копировать в шаблон сайта перед адаптацией.

