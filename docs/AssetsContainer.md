# Двигатель болида css-битрикс

| Уровень | Что загружается            | Источник                                        | Кто управляет     | Примечание                                   |
| ------- | -------------------------- | ----------------------------------------------- | ----------------- | -------------------------------------------- |
| **1**   | Системные стили ядра       | `/bitrix/js/...`, `/bitrix/themes/.default/...` | Bitrix Core       | Загружаются первыми, нельзя изменить порядок |
| **2**   | Стили шаблона              | `.styles.php` → `template_styles.css`           | Asset Manager     | Объединяются в один файл                     |
| **3**   | Стили компонентов          | `/components/.../style.css`, `addExternalCss()` | Компоненты Bitrix | Грузятся **после** стилей шаблона            |
| **4**   | Стили, добавленные вручную | `Asset::getInstance()->addCss()`                | Разработчик       | Приоритет выше, чем у компонентов            |
| **5**   | Inline `<style>`           | Вёрстка шаблона/компонентов                     | Разработчик       | Всегда имеют приоритет над файлами           |
| **6**   | Стили, вставленные JS      | `document.createElement('link')`                | JS                | Загружаются последними                       |

</br>

_**Bitrix читает список CSS из шаблона styles.php → собирает их в template_styles.css → затем подключает стили компонентов → затем всё остальное, соблюдая фиксированный порядок через Asset Manager.**_

# AssetsContainer

Класс отвечает за сбор и подключение CSS-файлов с указанным режимом загрузки (**_[CSS_INLINE](#css_inline), [CSS_DEFER](#css_defer), [CSS](#css)_**), обходя стандартный механизм формирования файла **_template_styles.css_**. Позволяет гибко управлять стилями и снижать объём неиспользуемого CSS на сайте.

**_Для инициализации класса необходимо добавить его в обработчик событий:_**</br>
_<code>EventManager::getInstance()->addEventHandler('main', 'OnEpilog', [\Hipot\BitrixUtils\AssetsContainer::class, 'onEpilogSendAssets']);</code>_

## Примеры использования:

### **_CSS_INLINE:_**

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/stylesheets/screen.css", AssetsContainer::CSS_INLINE);
```

_Вставляет CSS инлайном:_

```html
<style>
    /* __screen.min.css__ */
    img {
        border: 0;
    }
    body {
        margin: 0;
    }

    /* __CSS_INLINE_SIZE__ = 2.25 КБ */
</style>
```

### **_CSS_DEFER:_**

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/screen.css", AssetsContainer::CSS_DEFER);
```

_Вставляет `link` на минифицированную версию файла (если такова имеется):_

```html
<link
    rel="preload"
    href="/local/templates/SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    as="style"
    onload="this.onload=null;this.rel='stylesheet'"
/>
<noscript>
    <link
        rel="stylesheet"
        href="/local/templates/SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    />
</noscript>
```

### **_CSS:_**

```php
use Hipot\BitrixUtils\AssetsContainer;

AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/screen.css");
```

_Вставляет CSS через `Asset::getInstance()->addCss($css)`:_

```html
<link
    rel="stylesheet"
    href="/local/templates/SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    as="style"
    onload="this.onload=null;this.rel='stylesheet'"
/>
<noscript>
    <link
        rel="stylesheet"
        href="/local/templates/SITE_TEMPLATE_PATH/css/screen.min.css?17500973131517"
    />
</noscript>
```

## Подключение глобальных и стилей компонента

Представим что у нас есть стили которые мы используем на всех страницах, например - _bootstrap_ - стили.
Подключаем их в `header.php`:

```php
AssetsContainer::addCss(SITE_TEMPLATE_PATH . "/css/bootstrap.css", AssetsContainer::CSS_INLINE);
```

Далее, на странице кроме стилей _bootstrap_ у нас используются и стили компонента
`courses-card` - компонент вывода карточек с доступными курсами и их описанием.
В - `/courses-card/component_epilog.php` добавляем стили нашего компонента:

```php
AssetsContainer::addCss($templateFolder . '/style_inline.css', AssetsContainer::CSS_INLINE);
```

Тем самым мы получим:

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
