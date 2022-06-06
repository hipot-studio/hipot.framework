Профилирование страниц проектов при помощи xhprof
------------------------------------------

Установка в битрикс-окружении:

```shell
yum install -y gcc php-devel php-pear
pecl install xhprof
sudo -u bitrix mkdir -p /home/bitrix/profiler

# добавить конфигурацию в php
vi /etc/php.d/z_bx_custom_settings.ini
```

конфигурация:
```text
[xhprof]
extension = xhprof.so
xhprof.output_dir = /home/bitrix/profiler
```

перезапуск апача для включения загрузки модуля:

```shell
apachectl restart
```

качаем клиент профайлера, написанный на php
```shell
wget https://pecl.php.net/get/xhprof-2.3.5.tgz

#copy dirs from tarball xhprof_lib, xhprof_html to
#/home/bitrix/ext_www/domain.com/local
```

1/\
use hipot studio script bitrix/php_interface/include/lib/xhprof.php on top of any php-file\
(or in bitrix dbconn.php):

```php
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/lib/xhprof.php';
define('ENABLE_XHPROF', true);
define('XHPROF_MIN_TIME_SEC', 1);
// include header.php below
```

OR

2/\
use two anonymous function in hard block
```php
<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/php_interface/include/lib/xhprof.php';
// include header.php below

$xhprofStart();
// some hard code block
$xhprofEnd();
```