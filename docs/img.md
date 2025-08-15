Ресайзер изображений hiImg
------------------------------------------------------
Рассмотрим на примерах использования:

1/ В шаблоне компонента:
```php
// шаблон компонента, template.php
use Hipot\Utils\Img as hiImg;
$imgResizer = hiImg::getInstance([
    'tag'            => basename(__DIR__),  // уменьшенная копия будет сохранена в подпапке с именем шаблона
    'jpgQuality'     => 65,
    'decodeToFormat' => 'webp'
]);
$imgSmall = $imgResizer->doResize($arItem['PREVIEW_PICTURE']["ID"], 74, null, hiImg::M_PROPORTIONAL, true);
$imgBig = $imgResizer->doResize($arItem['PREVIEW_PICTURE']["ID"], 100, null, hiImg::M_PROPORTIONAL, true);
```
2/ Старый способ использования без создания объекта-сервиса:
```php
Img::oneResizeParams([
    'tag'            => 'test.detail',
    'decodeToFormat' => 'webp',
    'saveAlpha'      => true
]);
$pic_answer = Img::Resize($val, 225, 186, Img::M_FULL_S, true);
```
3/ Если нужно два формата, можно создать два объекта:
```php
// модификатор шаблона, result_modifier.php
use Hipot\Utils\Img as hiImg;
if ((int)$arResult["DETAIL_PICTURE"]["ID"] > 0) {
    $imgResizerJpg = hiImg::getInstance([
        'decodeToFormat' => 'jpg'
    ]);
    $imgResizer = hiImg::getInstance([
        'decodeToFormat' => 'webp'
    ]);
    
    // тег можно поставить и так (как и поменять другие атрибуты, см. методы set...()    
    $imgResizer->setTagInternal(basename(__DIR__ ));
    $arResult['SEO_IMAGE'] = $imgResizerJpg->doResize($arResult['DETAIL_PICTURE']["ID"], 1000, null, hiImg::M_PROPORTIONAL, true);
    
    $imgResizer->setTagInternal(basename(__DIR__ ) . '_mob');
    $arResult["DETAIL_PICTURE_MOBILE"] = $imgResizer->doResize($arResult['DETAIL_PICTURE']["ID"], 360, null, hiImg::M_PROPORTIONAL, true);
    
    $imgResizer->setTagInternal(basename(__DIR__ ) . '_dp');
    $arResult["DETAIL_PICTURE"] = $imgResizer->doResize($arResult['DETAIL_PICTURE']["ID"], 800, null, hiImg::M_PROPORTIONAL, true);
}
unset($imgResizer, $imgResizerJpg);
```
