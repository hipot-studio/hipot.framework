#
#!/usr/bin/env bash
#
# Наиболее частые команды на серваке
# author hipot
#

# 1/ узнать объем свободной оперативки
free -m

# 2/ узнать кол-во запущенных процессов апатча:
ps axww | grep httpd | wc -l

# 3/ узнать объем свободного дискового пространства
df -h

# 4/ Кол-во открытых соединений на 80й порт
netstat -na | grep ":80\ " | wc -l

# 5/ Посмотреть настройки фаервола (список правил в фаерволе)
iptables -L -n -v

# 6/ забанить IP напрочь
iptables -A INPUT -s 116.100.169.16 -j DROP

# 8/ удалить строку настроек из фаервола
iptables -L INPUT --line-numbers

# 9/ Принудительно завершить апатч (если служба не остановилась)
pkill -9 httpd

# процессы в системе
ps -auxefw | more

# 10/ Нельзя редактировать файл /etc/crontab, это системный crontab который может
# быть перезаписан после обновления системы. Нужно использовать пользовательский crontab, напр.
#
crontab -e  #для root, или
crontab -e -u bitrix # для пользователя bitrix.
# Важно: /etc/crontab имеет другой формат: он требует 6ю колонку ‘под каким пользователем запустить’.
# Пользовательские crontabs обычно не требуют этого.

# 11/ вывести список самых активных посетителей
cat /var/log/httpd/access_log | awk ' {print $1}' | sort | uniq -c | sort -n | tail -n 10

# 12/ Инфо по посетителю 5.255.253.63:
cat /var/log/httpd/access_log | grep 5.255.253.63 | head -1

# Список открытых портов
netstat -an | grep LISTEN

# Настройки фаервола
iptables -L -n -v





# Файл с логами оборудования сервера
cat /var/log/dmesg



# Включение в апатче server-info
# /etc/httpd/bx/custom/z_bx_custom.conf
#
<Location /server-status>
     SetHandler server-status
     Order Deny,Allow
     Deny from all
     Allow from 217.19.221.242
     # Allow from ...
</Location>
ExtendedStatus On
#
# /home/bitrix/www/.htaccess
RewriteCond %{REQUEST_FILENAME} !/bitrix/urlrewrite.php$
RewriteCond %{REQUEST_URI} !/server-status # ДОБАВИТЬ СТРОЧКУ
RewriteRule ^(.*)$ /bitrix/urlrewrite.php [L]



# install imagick on php 5.6:
yum install -y gcc php-devel php-pear
yum install -y ImageMagick ImageMagick-devel
pecl install imagick

# установленные модули:
ls -1 /usr/lib64/php/modules/

# загруженные модули:
php -m

# установить soap
yum install php-soap

# see https://webtatic.com/packages/php70/ vm7
yum install php-pecl-zip



# Расширение xmlreader.so требует предварительного подключения dom.so:
#
# PHP Warning: PHP Startup: Unable to load dynamic library '/usr/lib64/php/modules/xmlreader.so'
# - /usr/lib64/php/modules/xmlreader.so: undefined symbol: dom_node_class_entry in Unknown on line 0

# иногда на свежем серваке после установки bitrix vm нет почтового сервера.
# устанавливается просто:
yum install postfix
service postfix start
chkconfig postfix on
netstat -an | grep LISTEN # проверяем что 25й порт теперь слушается



# загрузка из консоли кусков бекапа:
for i in {1..217}; do wget http://site.ru/bitrix/backup/site.ru_20160812_165653_full_95284e0d.tar.gz.$i; done


# Для MySQL у root не меняем пароль, а добавляем еще одного пользователя:
# // новый пользователь и доступ ко всем базам
CREATE USER 'dblogin'@'localhost' IDENTIFIED BY ',4akMN{,4akMN{';
GRANT ALL PRIVILEGES ON * . * TO 'dblogin'@'localhost';
# // better
GRANT ALL PRIVILEGES ON dbname . * TO 'dblogin'@'localhost';
# // смена пароля
SET PASSWORD FOR 'dblogin'@'localhost' = PASSWORD(',4akMN{,4akMN{');
#// применение изменений в доступах
FLUSH PRIVILEGES;



#ssl
#nginx s1.cnf
if ($request_uri !~* "/(robots.txt)|(sitemap.xml)") {
    rewrite ^(.*)$ https://$host$1 permanent;
}

### --> your_domain.crt
-----BEGIN CERTIFICATE-----
#Ваш сертификат#
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
#Промежуточный сертификат#
-----END CERTIFICATE-----
-----BEGIN CERTIFICATE-----
#Корневой сертификат#
-----END CERTIFICATE-----

# поиск вирусяки
grep -iRI "###=CACHE START=###" /home/bitrix/www

# еще один поиск вирусни
cd /home/bitrix/
php ./ai-bolit.php -r ./aibolit-report-17-01-2017.html

# установка фтп на битрикс вм
yum install proftpd
chkconfig proftpd on
service proftpd start
# Узнаем id группы и id пользователя, под которым работает Apache.
# По умолчанию логин и группа этого пользователя - bitrix
id bitrix
adduser valenkiopt -g600 -o -u600 -d /home/bitrix/ext_www/valenkiopt.ru
passwd valenkiopt

# открыть порты для ftp
iptables -I INPUT -p tcp -m multiport --destination-port 20,21,50000:50400 -m state --state NEW -j ACCEPT
service iptables save
/etc/init.d/iptables restart

# get green A-type
openssl dhparam -out /etc/nginx/ssl/dhparams.pem 2048
ssl_dhparam /etc/nginx/ssl/dhparams.pem; #nginx config
nginx -s reload





###### centos7 ######
# Service restart
systemctl restart|start|status|stop httpd.service

# centos7 change hostname
hostnamectl status
hostnamectl set-hostname Your-New-Host-Name-Here
hostnamectl status

#centos7 timezone
timedatectl #see config
timedatectl set-timezone Europe/Moscow

# If you need to enable Apache service, you just need to run:
systemctl enable httpd.service

# To check if any of your services is configured at boot time, you can use this command:
systemctl list-unit-files | grep httpd






# crontab header
MAILTO=hipot@ya.ru
# For details see man 4 crontabs
# Example of job definition:
# .---------------- minute (0 - 59)
# |  .------------- hour (0 - 23)
# |  |  .---------- day of month (1 - 31)
# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun 0 ,mon 1, tue 2, wed 3, thu 4, fri 5, sat 6
# |  |  |  |  |
# *  *  *  *  * user-name command to be executed

# min h dom mon dow



# Use q flag for quiet mode, and tell wget to output to stdout with O- (uppercase o)
# and redirect to /dev/null to discard the output:
wget -qO- $url &> /dev/null

# > redirects application output (to a file). if > is preceded by ampersand, shell redirects all outputs (error and normal) to the file right of >. If you don't specify ampersand, then only normal output is redirected.

# ./app &>  file # redirect error and standard output to file
# ./app >   file # redirect standard output to file
# ./app 2>  file # redirect error output to file
# if file is /dev/null then all is discarded.

# centOs7 install wkhtmltopdf (wont work((
yum install wkhtmltopdf


##
## install bitrix VM CentOS Linux release 7.3.1611 (Core)
##
wget http://repos.1c-bitrix.ru/yum/bitrix-env.sh
chmod +x bitrix-env.sh
./bitrix-env.sh
# А также зайти в Виртуальную машину, сменить пароли при первой авторизации и создать пулл.


cd /var/lib/mysql
mysqldumpslow -t 50 -v acritserverdb-slow.log > top50-slow.log
