# Утилиты — Hipot\Utils\UUtils

[UUtils](../src/lib/classes/Hipot/Utils/UUtils.php) объединяет восемь трейтов из `Hipot\Utils\Helper`. Все перечисленные методы вызываются статически. `UnsortedUtils` можно использовать как псевдоним при импорте.

```php
use Hipot\Utils\UUtils;

$flat = UUtils::flatten(['filter' => ['active' => true]]);
// ['filter.active' => true]
$nested = UUtils::unflatten($flat);
$slug = UUtils::TranslitText('Новая запись');
```

## Массивы и объекты

Методы [ArrayTools](../src/lib/classes/Hipot/Utils/Helper/ArrayTools.php):

- `trimRecursive(array $array): array` рекурсивно удаляет значения, чьё строковое представление становится пустым после `trim()`. Сохраняет ключи, исходные значения оставшихся элементов и пустые вложенные массивы.
- `getDot(array $array, ?string $key, mixed $default = null): mixed` читает значение по точечному пути, например `user.profile.id`. Прямой ключ со значением, отличным от `null`, имеет приоритет. Для отсутствующего пути возвращает `$default`, для ключа `null` возвращает весь массив.
- `flatten(array $array, string $separator = '.', string $prefix = ''): array` рекурсивно соединяет части ключей и сохраняет пустые массивы как значения. При коллизии составного ключа побеждает позднее значение.
- `unflatten(array $array, string $separator = '.'): array` строит вложенные ветки из составных ключей. Поздняя запись может заменить ранее собранную ветку или превратить лист в ветку. Оба метода выбрасывают `InvalidArgumentException` для пустого разделителя. Разделитель внутри исходного ключа не экранируется, поэтому при таких ключах обратное преобразование может изменить структуру.
- `removeTildaKeys(array $data): array` удаляет ключи с начальной тильдой на верхнем уровне.

Методы [ObjectTools](../src/lib/classes/Hipot/Utils/Helper/ObjectTools.php):

- `createObjectFromTrait(string $traitName): object` создаёт новый объект анонимного класса с указанным трейтом. Передавайте полное имя, например `ArrayTools::class` после импорта трейта. Трейт должен работать без аргументов конструктора и реализации абстрактных методов. Отсутствующий трейт приводит к `InvalidArgumentException`.
- `getPrivateProperty(object|string $target, string $propertyName): mixed` и `setPrivateProperty(object|string $target, string $propertyName, mixed $value): void` читают и меняют объявленные свойства через Reflection, включая свойства родителей. Для поля экземпляра передаётся объект, для статического можно передать имя класса. Отсутствующее свойство приводит к `RuntimeException`. Ограничения PHP на тип, инициализацию и изменение свойства сохраняются.

## Строки и даты

Методы [StringUtils](../src/lib/classes/Hipot/Utils/Helper/StringUtils.php):

- `mailHideWithMailme(string $email): string` заменяет `@` на ` AT ` и `.` на ` DOT ` для вывода через плагин `$.mailme`.
- `Suffix(int|float|string $n, array|string $forms): string` выбирает одну из трёх форм через Bitrix Declension. Количество приводится к целому. Формы передаются массивом или строкой `'товар|товара|товаров'`.
- `TranslitText(?string $text, string $lang = 'ru', int $maxLen = 200): string` удаляет пробелы по краям и транслитерирует текст в нижний регистр через `CUtil`. Пробелы и прочие заменяемые символы превращаются в дефисы, повторные замены схлопываются.
- `getClearHtmlValue(?string $value, array $allowedTags = [...]): string` возвращает пустую строку для `null`, удаляет теги вне списка и при наличии расширения Tidy форматирует результат. Список по умолчанию включает базовую разметку текста, ссылки, изображения и таблицы. `strip_tags()` сохраняет атрибуты разрешённых тегов, поэтому для недоверенного HTML нужна отдельная очистка атрибутов.
- `isValidRegx(string $regx): bool` проверяет, возвращает ли `preg_match()` значение, отличное от `false`. PHP может выдавать предупреждение для ошибочного шаблона.

Методы [DateTimeUtils](../src/lib/classes/Hipot/Utils/Helper/DateTimeUtils.php):

- `IsIntervalsTsIncl($left1_ts, $right1_ts, $left2_ts, $right2_ts): bool` проверяет пересечение двух интервалов Unix-времени, включая общую граничную точку. Границы каждого интервала передаются в порядке от начала к концу.
- `timestampToSeason(\DateTimeInterface $dateTime): string` возвращает `Winter`, `Spring`, `Summer` или `Fall` по номеру дня года из `format('z')`. Код использует фиксированные пороги 80, 173, 266 и 356.
- `secondsToTimeString(int|string $duration, bool $seconds = true): string` форматирует длительность по-русски. При наличии дней или часов секунды скрываются. Часы считаются после вычета полных дней, поэтому 25 часов выводятся как `1 день 1 час`. При отсутствии выводимых единиц результат — `' 0 секунд'`.
- `timeToIso8601Duration(int $time): string` разворачивает положительное число секунд в строку наподобие `PT1H30M`. Год равен 365 дням. Для нуля возвращает `PT0S`, для отрицательного числа выбрасывает `InvalidArgumentException`.

## Окружение, запрос и URL

Методы [ContextUtils](../src/lib/classes/Hipot/Utils/Helper/ContextUtils.php):

| Метод | Поведение |
| --- | --- |
| `getPhpPath(): string` | Возвращает команду `php` с параметром `-d default_charset`. Использует UTF-8 при определённой `BX_UTF`, иначе CP1251. |
| `sessionStart(): void` | Запускает PHP-сессию, если она ещё не активна, с HTTP-only cookie и строгим режимом сессии. |
| `getUserIp(BitrixEngine $bitrixEngine): ?string` | Возвращает первый валидный IP в порядке `HTTP_CLIENT_IP`, `HTTP_X_FORWARDED_FOR`, `HTTP_X_REAL_IP`, `REMOTE_ADDR`. Для списка проверяет последнее значение. Для решений о доступе эти заголовки требуют доверенной настройки прокси. |
| `getGeoIpDataByIp(string $ip): ?GeoIp\Data` | Читает геоданные Bitrix на текущем языке. При исключении пишет в журнал и возвращает `null`. |
| `getCountryByIp(string $ip): string` | Извлекает код страны. При отсутствии данных возвращает пустую строку. |
| `isPageNavigation(): bool` | Проверяет GET и POST параметры `PAGEN_1` — `PAGEN_3`. Условие срабатывает при значении больше 1. |
| `isAjaxRequest(BitrixEngine $bitrixEngine): bool` | Учитывает штатный признак AJAX, параметры `testajax`, `is_ajax_post`, `via_ajax` со значением `Y` и заголовки AJAX. |
| `siteIdDirs(array $dirs): bool` | Проверяет, подходит ли текущая страница хотя бы под одну директорию по `CSite::InDir()`. |
| `setUriToBitrixCurPage(string $uri): void` | Меняет текущую страницу в приложении, заменяет `$_GET` разобранными параметрами и копирует их в глобальные переменные. Используйте URI, сформированный приложением. |
| `getBitrixCurPageParam(string $addParams = '', array $arParamKill = [], bool $get_index_page = false): string` | Собирает URL через `GetCurPageParam()`. Удаляет параметры навигации для индексов 1–3, служебные параметры AJAX, системные параметры запроса и ключи из `$arParamKill`. |
| `getCurPageParamD7(array $arAddParams = [], array $arDeleteParams = []): string` | Берёт URI текущего запроса, удаляет указанные ключи и добавляет карту параметров через `Bitrix\Main\Web\Uri`. |
| `getPageContentByUrl($url): string` | Выполняет GET через Bitrix HttpClient и приводит результат к строке, включая пустую строку при `false`. |
| `getHttpHeadersByUrl($url): array` | Выполняет HEAD без перенаправлений и проверки TLS, возвращает заголовки и ключ `status`. Если `head()` вернул `false`, выбрасывает `RuntimeException`. Оба HTTP-метода рассчитаны на доверенные адреса. |
| `getInlineBase64Image(string $img_file): ?string` | Читает файл и собирает data URI с определённым MIME-типом. Для отсутствующего файла возвращает `null`. |

## Обработка страницы и диагностика

Эти методы также входят в `ContextUtils`.

- `setStatusNotFound(bool $showPage = false): void` вызывает `process404()` при доступном `iblock`. Без модуля при наличии `404_inc.php` подключает его через буфер и выводит только при `$showPage = true`. В этой ветке сам метод статус не устанавливает. При отсутствии файла устанавливает статус 404 и определяет `ERROR_404`.
- `logException(\Throwable $exception): void` передаёт исключение в журнал обработчика исключений Bitrix.
- `stopSqlQueryExecution(): void` временно отключает выполнение запросов на основном соединении. `getQueueStoppedQueryExecution(): ?array` включает выполнение новых запросов и читает накопленный диагностический дамп. Чтение очищает дамп. Пропущенные запросы автоматически не выполняются.
- `disableAllPageProcessEvents(BitrixEngine $bitrixEngine): void` регистрирует ранний обработчик `OnPageStart`, который снимает найденные обработчики этапов от `OnPageStart` до `OnEndBufferContent`, перечисленных в исходнике.
- `disableAssetMinifying(): void` отключает оптимизацию CSS и JS и размещение JS в конце страницы. Для выбора оригинальных файлов обращается к внутреннему состоянию `Option` через Reflection. `isMainUsedMinifiedAssets(): bool` проверяет, равна ли опция `main/use_minified_assets` значению `Y`, которое используется и по умолчанию.
- `clearCompositePage(string $uri): void` просит композитное хранилище удалить запись для указанного URI, если хранилище доступно.
- `disableHttpAuth(): void` добавляет обработчик события Bitrix `onBeforeUserLoginByHttpAuth`, который запрещает вход через HTTP-аутентификацию пользователя.

## Компоненты и включаемые области

Методы [ComponentUtils](../src/lib/classes/Hipot/Utils/Helper/ComponentUtils.php):

- `isComponentExists(string $componentName): bool` пробует инициализировать компонент и перехватывает его вывод. `loadHipotComponentClass(string $className): ?string` сопоставляет имена классов из `Hipot\Components` и имена вида `Hipot…Component` с именами `hipot:…`. Подключает класс найденного компонента и возвращает его имя или `null` при неподходящем имени или отсутствующем компоненте.
- `getComponent(string $name, string $template = '', array $params = [], &$componentResult = null): string` возвращает HTML из буфера, а результат `IncludeComponent()` записывает в переменную по ссылке.
- `getIncludeArea(string $path, array $params = [], array $functionParams = []): string` перехватывает вывод `IncludeFile()`. `getIncludeAreas(string $includesDir, $params = [], $functionParams = []): array` перебирает файлы директории без рекурсии и возвращает карту HTML по именам файлов с удалённым суффиксом `.php`. Цикл не фильтрует по расширению. Для отсутствующей директории возвращается `[]`.
- `setComponentEdit(CBitrixComponent $component, array $arParams = [], array $arOptions = []): void` добавляет меню включаемой области, если их показ включён. Передавайте `IBLOCK_ID`, `ELEMENT_ID` и `SECTION_ID`, при отсутствии элемента или раздела используйте 0.
- `setElementEdit(CBitrixComponentTemplate $template, &$arElement, ?string $confirmDelete): void` и `setSectionEdit(CBitrixComponentTemplate $template, &$arSection, ?string $confirmDelete): void` заполняют ссылки редактирования и удаления через [Iblock](iblock.md) и регистрируют действия в шаблоне. `getEditAreaAttrId(CBitrixComponentTemplate $template, array $entity): string` возвращает готовый атрибут `id="…"` для сущности с ключом `ID`.
- `insertVideoBxPlayer(string $videoFile, int $width = 800, int $height = 450, $component = null, bool $adaptiveFixs = true): void` выводит `bitrix:player` с указанными размерами и выключенным автозапуском. По умолчанию добавляет JS для адаптивного режима.
- `getSefComponentVariables(array $arParams, array $arComponentVariables): array` возвращает переменные текущего маршрута по `SEF_FOLDER`, `SEF_URL_TEMPLATES` и необязательным `VARIABLE_ALIASES`.

## Пользовательские поля и веб-формы

Методы [UserFieldUtils](../src/lib/classes/Hipot/Utils/Helper/UserFieldUtils.php):

- `getUserFieldList(array $filter = [], array $getList = []): array` читает пользовательские поля с подписями текущего языка. `$filter` дополняет фильтр по языку, а `$getList` может заменить верхнеуровневые параметры ORM-запроса, включая весь фильтр. Возвращается список строк.
- `normalizeUfFieldValue(string $entityId, string $fieldName, $value)` обрабатывает скаляр или каждый элемент массива. Для целых и дробных чисел удаляет пробелы, у дробных заменяет запятую точкой. Для файла вызывает `CFile::MakeFileArray()`. Для списка ищет ID по тексту и при отсутствии пытается создать вариант в базе. Для неизвестного поля или типа возвращает исходное значение. Метаданные загружаются отдельно для каждой сущности и повторно используются в текущем процессе.

Методы [WebFormUtils](../src/lib/classes/Hipot/Utils/Helper/WebFormUtils.php) требуют модуль `form`:

- `getFormWithFields(int $webFormId, bool $includeAdditionalFields = false, bool $includeInactive = false): ?array` возвращает `FORM` с метаданными и `FIELDS` по SID. Каждый элемент содержит данные поля и ключи `ANSWERS`, `DROPDOWN`, `MULTISELECT` там, где их добавляет обёртка. По умолчанию выбираются активные вопросы и ответы без дополнительных полей результата. Для неположительного ID или ненайденной формы возвращает `null`.
- `formResultAddSimple(int $WEB_FORM_ID, array $arrVALUES = []): UpdateResult|false` принимает значения в формате веб-форм Bitrix, например `form_text_18`, где 18 — ID ответа. Устанавливает служебные ключи `WEB_FORM_ID` и `web_form_submit`. После успешного добавления вызывает обработку CRM и штатные уведомления веб-форм. Возвращает `UpdateResult` со статусом `STATUS_OK` и ID результата или `false` при неположительном ID и отказе добавления. Сбой загрузки модуля приводит к исключению.
