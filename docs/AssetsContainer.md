# Двигатель болида css-битрикс

| Уровень | Что загружается            | Источник                                        | Кто управляет     | Примечание                                                  |
| ------- | -------------------------- |-------------------------------------------------| ----------------- |-------------------------------------------------------------|
| **1**   | Системные стили ядра       | `/bitrix/js/...`, `/bitrix/themes/.default/...` | Bitrix Core       | Загружаются первыми, нельзя изменить порядок                |
| **2**   | Стили шаблона              | `styles.css`, `template_styles.css`             | Asset Manager     | Объединяются в один файл                                    |
| **3**   | Стили компонентов          | `/components/.../style.css`, `addExternalCss()` | Компоненты Bitrix | Грузятся **после** стилей шаблона<br/>Объединяются в один файл |
| **4**   | Стили, добавленные вручную | `Asset::getInstance()->addCss()`                | Разработчик       | Приоритет выше, чем у компонентов                           |
| **5**   | Inline `<style>`           | Вёрстка шаблона/компонентов                     | Разработчик       | Всегда имеют приоритет над файлами                          |
| **6**   | Стили, вставленные JS      | `document.createElement('link')`                | JS                | Загружаются последними                                      |

_**Bitrix читает список CSS из шаблона template_styles.css и styles.css → затем подключает стили компонентов → затем всё остальное, соблюдая фиксированный порядок через Asset Manager
→ затем объединяет в общий файл при соответствующей настройке в главном модуле.**_

# AssetsContainer

Класс отвечает за сбор и подключение CSS-файлов с указанным режимом загрузки (**_[CSS_INLINE](#css_inline), [CSS_DEFER](#css_defer), [CSS](#css)_**), обходя стандартный механизм формирования файла **_template_styles.css_**. Позволяет гибко управлять стилями и снижать объём неиспользуемого CSS на сайте.

Второе использование класса заключается в задании js-параметров шаблона сайта.

Для инициализации класса необходимо добавить его в обработчик событий:</br>

```php
// init.php
use Bitrix\Main\EventManager;
EventManager::getInstance()->addEventHandler('main', 'OnEpilog', [
    \Hipot\BitrixUtils\AssetsContainer::class, 
    'onEpilogSendAssets'
]);
```


## Примеры использования:

### CSS_INLINE:

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/stylesheets/screen.css", AssetsContainer::CSS_INLINE);
AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/stylesheets/page.css", AssetsContainer::CSS_INLINE);
```

_Вставляет CSS инлайном в head:_

```html
<style>
    /* __screen.min.css__ */
    img {border: 0;}
    body {margin: 0;}

    /* __page.min.css__ */
    div {margin: 0;}

    /* __CSS_INLINE_SIZE__ = 2.25 КБ */
</style>
```

### CSS_DEFER:

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/screen.css", AssetsContainer::CSS_DEFER);
```

_Вставляет `link` на минифицированную версию файла (если такова имеется):_

```html
<link
    rel="preload"
    href="SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    as="style"
    onload="this.onload=null;this.rel='stylesheet'"
/>
<noscript>
    <link
        rel="stylesheet"
        href="SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    />
</noscript>
```

### CSS:

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/screen.css");
```

_Вставляет CSS через `Asset::getInstance()->addCss($css)`:_

```html
<link rel="stylesheet" href="SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517" />
```

## Подключение глобальных и стилей компонента

Представим что у нас есть стили которые мы используем на всех страницах, например - _bootstrap_ - стили.
Подключаем их в `header.php`:

```php
AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/bootstrap.css", AssetsContainer::CSS_INLINE);
```

Далее, на странице кроме стилей _bootstrap_ у нас используются и стили компонента
` courses-card` - компонент вывода карточек с доступными курсами и их описанием.
В эпилоге шаблона компонента - `/courses-card/component_epilog.php` добавляем стили нашего компонента:

```php
AssetsContainer::addCss($templateFolder . '/style_inline.css', AssetsContainer::CSS_INLINE);
```

Тем самым мы получим в head-страницы:

```html
<style>
    /* __bootstrap.min.css__ */
    .col-xs-1 {
        float: left;
    }
    /* __style_inline.min.css__ */
    .course-card p {
        text-align: left;
        font-size: 18px;
    }
    /* __CSS_INLINE_SIZE__ = 2.55 КБ */
</style>
```

Обратите внимание, что файл в шаблоне компонента назван отличным от style.css, чтобы он не подключился автоматически движком системы.

# Инициализация глобальных параметров js-приложения

```php
// header.php

// all template scripts (used all above plugins and siteJsConfigs)
$siteJsConfigs = [
	'SITE_TEMPLATE_PATH' => SITE_TEMPLATE_PATH,
	'IS_DEV'             => IS_BETA_TESTER || IS_CONTENT_MANAGER,	
	'requireJSs' => [
		'jquery.fancybox'   => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/fancybox/jquery.fancybox.min.js"),
		'owl.carousel'      => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/owl.carousel.min.js"),
		'jquery.formstyler' => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/formstyler/1.7.8/jquery.formstyler.min.js"),
		'ion.rangeSlider'   => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/rangeSlider/2.3.1/ion.rangeSlider.min.js"),
	],
	'requireCss' => [
		'jquery.fancybox'   => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/fancybox/jquery.fancybox.min.css"),
		'jquery.formstyler'	=> CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/formstyler/1.7.8/jquery.formstyler.css"),
		'ion.rangeSlider'	=> CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/js/rangeSlider/2.3.1/ion.rangeSlider.min.css"),
		'calculator'        => CUtil::GetAdditionalFileURL(SITE_TEMPLATE_PATH . "/stylesheets/calculator.min.css"),
	]
];

use Hipot\BitrixUtils\AssetsContainer;
AssetsContainer::addJsConfig($siteJsConfigs);
```

Затем в подключенных на странице js-скриптах можно использовать объект с параметрами appParams

```js
// site-global-script.js
if (appParams.IS_DEV === false) {
	$('body').on('contextmenu', 'img, *[data-observer-block-bg], .online-cources-form-bg-img', function (e) {
		e.preventDefault();
	});
}
```

# Методы

<table width="100%" class="tg"><thead>
  <tr>
    <th width="20%">Метод</th>
    <th width="30%">Описание</th>
    <th width="40%">Аргументы</th>
  </tr></thead>
<tbody>
 <tr>
    <td><code>onEpilogSendAssets()</code></td>
    <td><a href="#assetscontainer">Метод для инициализации класса</a></br></td>
    <td></td>
  </tr>
  <tr>
    <td><code>addCss($path, $type)</code></td>
    <td>Метод добавляет CSS в <code>&lt;head&gt;&lt;/head&gt;</code></td>
    <td>
        <code>$path</code>: Путь к файлу
        <ul>
            <li><code>type</code>: string</li>
        </ul>
        <code>$type</code>: Тип вставки
        <ul>
            <li>
            <code>type</code>: int</br>
            </li>
            <li>
                <code>variants</code>:
                <a href="#css_inline"><code>AssetsContainer::CSS_INLINE</code></a>,
                <a href="#css_defer"><code>AssetsContainer::CSS_DEFER</code></a>,
                <a href="#css"><code>AssetsContainer::CSS</code></a>
            </li>
            <li>
                <code>default</code>: <a href="#css"><code>AssetsContainer::CSS</code></a>
            </li>
        </ul>
    </td>
  </tr>
 
</tbody>
</table>
