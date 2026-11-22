# Торговый каталог — Hipot\BitrixUtils\Catalog

[Класс Catalog](../src/lib/classes/Hipot/BitrixUtils/Catalog.php) предоставляет статические методы для торговых предложений, складских остатков, цен и полей товара. При загрузке файла подключается модуль Bitrix `catalog`. Для чтения метаданных инфоблоков используются API `iblock` и `Hipot\BitrixUtils\Iblock`.

## Торговые предложения

В эти методы передаётся ID **инфоблока товаров**.

- `getOffersIblockInfo(int $productIblockId): ?array` возвращает метаданные связи с инфоблоком предложений. Все поля приводятся к `int`: `IBLOCK_ID` — инфоблок предложений, `PRODUCT_IBLOCK_ID` — инфоблок товаров, `SKU_PROPERTY_ID` — свойство привязки предложения к товару, `VERSION` — версия хранения свойств инфоблока предложений. Для неположительного ID или отсутствующей связи возвращает `null`. Для положительного ID явно требует модуль `catalog`.
- `getOffersIblockId(int $productIblockId): ?int` возвращает положительный ID инфоблока предложений либо `null`.
- `getOfferProperties(int $productIblockId, array $excludeCodes = ['CML2_LINK']): array` возвращает метаданные активных свойств предложений по символьным кодам. Свойство связи удаляется по `SKU_PROPERTY_ID` независимо от переданного списка исключений. Дополнительные коды исключаются после удаления пробелов по краям. При отсутствии связи возвращается `[]`. Метаданные берутся из [кешированной карты свойств инфоблока](reference_data_cache.md#кеш-справочных-данных-инфоблоков).

```php
use Hipot\BitrixUtils\Catalog;

$offersIblockId = Catalog::getOffersIblockId($productIblockId);
$offerProperties = Catalog::getOfferProperties($productIblockId, ['CML2_LINK', 'INTERNAL_CODE']);
```

## Остатки по складам

- `getStoreAmounts(int $productId, array $storeIds = []): array` возвращает карту `[ID склада => количество]` из `StoreProductTable`, отсортированную по ID склада. Количества приводятся к `float`. Склад без строки остатка отсутствует в карте.
- `getStoreQuantity(int $productId, array $storeIds = []): float` суммирует значения карты и возвращает `0.0`, если записей нет.

Пустой список складов означает все склады. Переданные ID приводятся к целым, неположительные значения и дубликаты отбрасываются. Если непустой входной список не дал ни одного положительного ID либо ID товара неположительный, карта остатков будет пустой. В качестве ID можно передать ID конкретного предложения, для которого ведутся остатки.

```php
use Hipot\BitrixUtils\Catalog;

$amounts = Catalog::getStoreAmounts($productId, [2, 5]);
$quantity = Catalog::getStoreQuantity($productId, [2, 5]);
```

## Цены и данные товара

`upsertPrice(int $productId, int $priceTypeId, float $price, string $currency): Result` ищет цену по ID товара и типа цены. При отсутствии строки создаёт её через `Bitrix\Catalog\Model\Price::add()`, иначе обновляет найденную строку. При нескольких ценах одного типа запрос берёт одну строку без явной сортировки и без фильтра по диапазону количества. Поиск и запись выполняются отдельными операциями.

Неположительные ID, отрицательная цена и пустой после удаления пробелов код валюты приводят к `InvalidArgumentException`. Код валюты переводится в верхний регистр, нулевая цена допустима. Результат записи представлен как `Bitrix\Main\Result`. ID типа цены можно получить через [PriceType](reference_data_cache.md#поиск-типов-цен).

```php
use Hipot\BitrixUtils\Catalog;

$result = Catalog::upsertPrice($productId, $priceTypeId, 1490.0, 'RUB');
if (!$result->isSuccess()) {
    $messages = $result->getErrorMessages();
}
```

`updateProduct(int $productId, array $arItemProduct = []): Bitrix\Main\ORM\Data\Result` добавляет или обновляет запись в `ProductTable` по ID элемента. При обновлении отсутствующие во входном массиве `QUANTITY`, `QUANTITY_TRACE` и `CAN_BUY_ZERO` сохраняются. Для новой записи по умолчанию количество равно `0`, а настройки учёта и покупки при нулевом остатке получают `ProductTable::STATUS_DEFAULT`. Явно переданные поля имеют приоритет. Если `AVAILABLE` не передана, метод рассчитывает её на основе переданных и текущих значений этих полей.

## Штрихкод и коэффициент единицы измерения

- `getProductBarCode(int $productId): string` возвращает первый найденный штрихкод. При отсутствии штрихкода или неположительном ID возвращает `''`.
- `setProductBarCode(int $productId, string $barcode, int $userId): bool` создаёт штрихкод с `CREATED_BY` или обновляет первую найденную запись с `MODIFIED_BY`. Возвращает результат записи или `true`, если штрихкод уже совпадает. Для неположительного ID возвращает `false`.
- `getProductMeasureRatio(int $productId): float|false` возвращает поле `RATIO` первой найденной записи, приведённое к `float`. Для неположительного ID или отсутствующей записи возвращает `false`.

## Скидки товара

`getDiscountForProduct(int $productId, int $iblockId = 0): array` возвращает список скидок из `CCatalogDiscount::GetDiscountForProduct()`. При `$iblockId = 0` обёртка определяет инфоблок по ID элемента. Список сайтов берётся из привязок инфоблока. Среди запрашиваемых полей есть `ID`, `NAME`, `SITE_ID`, `SORT`, `ACTIVE_FROM`, `ACTIVE_TO`, `VALUE_TYPE` и `VALUE`.

Метаданные каталога хранятся в статической карте по ID инфоблока, а выборка привязок сайтов использует ORM-кеш на 7 суток. Для инфоблока предложений передаются параметры SKU из его метаданных. При запуске в CLI после вызова очищается кеш скидок по товарам, разделам и свойствам.
