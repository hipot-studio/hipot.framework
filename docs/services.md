## Сервисный слой для коммуникации с внешними системами

// todo: показать примеры использования

1/ Движок битрикса, как самый большой и тотальный монолитный сервис для фрейморка, класс 
<code>\Hipot\Services\BitrixEngine</code>

2/ Итератор над результатами-выборками в битриксе
<code>\Hipot\Services\DbResultGenerator</code>

3/ Класс для удобства взаимодействия с файловой системой 
<code>\Hipot\Services\FileSystem</code>

4/ Класс для работы с memcached через интерфейс ArrayAccess
<code>\Hipot\Services\MemcacheWrapper</code>

5/ Класс для хранения различных значений (реестр значений), чтобы не использовать $GLOBALS 
<code>\Hipot\Services\Registry</code>

6/ Сервис для чтения xml-файлов через php_xmlreader
<code>\Hipot\Services\SimpleXMLReader</code>

7/ Сервис создания простого excel-файла <code>\Hipot\Services\SimpleXlsx</code> на основе PhpSpreadsheet

8/ Сервис для работы с сервисом Google Recaptcha3 и его внедрением в битрикс
<code>\Hipot\Services\Recaptcha3</code>

9/ Класс-обертка над запуском wkhtmltopdf для создания pdf из страницы:
<code>\Hipot\Services\PdfPageGenerator</code>

10/ Класс-обертка над пакетом ffmpeg для работы с видео
<code>\Hipot\Services\FfmpegExec</code>

11/ Класс для работы с календарем и рабочими (банковскими) днями
<code>\Hipot\Services\BankDayCalc</code>

12/ Сервис для работы с AI <code>\Hipot\Services\OpenAI</code>