# Интернет-магазин — Hipot\BitrixUtils\Sale

[Класс Sale](../src/lib/classes/Hipot/BitrixUtils/Sale.php) предоставляет статические методы для корзины, заказов, оплат и скидок. При загрузке файла подключается модуль Bitrix `sale`. Имя `SaleUtils` можно задать при импорте класса.

## Корзина текущего покупателя

Методы корзины используют текущий сайт и идентификатор покупателя `Bitrix\Sale\Fuser`. Это идентификатор покупателя в модуле магазина, который может быть и у гостя.

- `getFUserBasket(): ?BasketBase` загружает корзину с позициями, ещё не привязанными к заказу. В проверенном ядре при отсутствии позиций возвращается пустой объект корзины, хотя сигнатура обёртки допускает `null`.
- `getFUserBasketItems(): BasketBase` возвращает корзину с позициями, доступными для заказа, через штатный `getOrderableItems()`.
- `getBasketItemProperties(BasketItem $basketItem, array $ignoreItemsCode = ['CATALOG.XML_ID', 'PRODUCT.XML_ID', 'SUM_OF_CHARGE']): array` возвращает список свойств позиции. Из списка исключаются переданные коды, из каждого свойства убираются поля со значениями `false` и `null`. В `BASKET_ID` записывается код позиции из `getBasketCode()`. Переданный список исключений заменяет список по умолчанию.

```php
use Hipot\BitrixUtils\Sale as SaleUtils;

foreach (SaleUtils::getFUserBasketItems() as $item) {
    $properties = SaleUtils::getBasketItemProperties($item);
    // Используйте свойства текущей позиции.
}
```

## Заказы и оплаты

- `isOrderExists(int|string $orderId): bool` возвращает `true`, если по внутреннему ID удалось загрузить заказ через `Order::load()`. Для пустого идентификатора возвращает `false`.
- `getOrderStoreId(Order $order): int` возвращает первый положительный ID склада по порядку отгрузок. Если положительный ID отсутствует, возвращает `-1`.
- `getPaySystemServiceByOrder(Order $order): array` возвращает пару `[$service, $payment]` типов `Bitrix\Sale\PaySystem\Service` и `Bitrix\Sale\Payment`. Используется первая оплата из коллекции и запись платёжной системы по её ID. При отсутствии оплаты или записи платёжной системы выбрасывает `RuntimeException`.
- `orderHasPayedInHistory(int $orderId): bool` ищет в истории заказа запись сущности `PAYMENT` с `DATA['PAID'] == 'Y'`. Результат описывает факт оплаты в истории, даже если впоследствии её отменили.
- `setOrderProperties(int $orderId, array $orderProps): bool` принимает карту `[код свойства => значение]`. Обновляет существующие свойства и при необходимости создаёт их по настройкам текущего типа плательщика. Неизвестные коды пропускаются. Возвращает результат сохранения коллекции свойств, поэтому `true` возможен и при пропуске переданных кодов. Для неположительного ID, пустой карты или отсутствующего заказа возвращает `false`.

## Расчёт скидок

- `applyOrderDiscounts(?Order $order = null, ?array &$result = null): array` включает обновление заказа при расчёте скидок и после расчёта вызывает `doFinalAction(true)`, включая случай ошибки расчёта. Возвращаемый массив отражает ошибки расчёта скидок. Сохранение заказа выполняется отдельно.
- `applyBasketDiscounts(?Basket $basket, ?array &$result = null): array` использует контекст заказа для привязанной корзины и контекст Fuser для самостоятельной. Сохранение корзины выполняется отдельно.

При успешном расчёте оба метода записывают `getApplyResult(true)` в переданную по ссылке переменную и возвращают `[]`. При ошибке возвращается вложенный массив вида `[['сообщение 1', 'сообщение 2']]`. При передаче `null` вместо заказа или корзины возвращается `[]` без расчёта. В этих двух случаях выходной параметр сохраняет прежнее значение.

```php
use Hipot\BitrixUtils\Sale as SaleUtils;

$discountDetails = null;
$errors = SaleUtils::applyOrderDiscounts($order, $discountDetails);
if ($errors !== []) {
    $messages = $errors[0];
}
```

`getSaleDiscountModTimestamp(): int` возвращает Unix-время последнего изменения записи скидки по `TIMESTAMP_X` из `DiscountTable`. При опции `sale/use_sale_discount_only = Y` и доступном модуле `catalog` запрос дополнительно подсоединяет таблицу скидок каталога. Для пустой таблицы возвращает `0`.

## Устаревший обработчик

`deleteUnusedBasketProps($basketId = false): void` помечен как `@deprecated`. При наличии класса `\App` и непустого списка кодов `\App::NEED_BASKET_PROPERTY` выполняет прямое удаление из `b_sale_basket_props` по этому списку. Удаление ограничено сохранённой позицией с указанным положительным ID. При пропущенном или недопустимом ID, отсутствии класса или пустом списке метод завершает работу без удаления.
