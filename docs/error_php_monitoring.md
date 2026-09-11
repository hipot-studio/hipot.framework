# Мониторинг PHP-ошибок на почту

В процессе работы и эксплуатации проекта в боевом режиме удобно сразу получать фатальные ошибки на почту ответственного лица (разработчика). 

Риски: важно учесть, что ошибок может быть много и некоторые хостеры блокируют отправку повторяющихся писем.

Реализация основана на зашитую в ядро битрикса возможность:
[<code>\Bitrix\Main\Diag\HttpExceptionHandlerOutput::renderExceptionMessage()</code>](https://github.com/hipot-studio/bxApiDocs/blob/master/modules/main/lib/diag/httpexceptionhandleroutput.php#L31)

### Инструкция подключения мониторинга ошибок

- в корень сайта закинуть файл [error.php](../src/install/pages/error.php)
- поменять емейл того, кому присылать фатальные PHP-ошибки через константу [<code>ERROR_EMAIL</code>](https://dev.1c-bitrix.ru/api_help/main/functions/debug/senderror.php)
```php
// файл /bitrix/php_interface/dbconn.php
// определим константу ERROR_EMAIL, в которой зададим E-Mail администратора
define("ERROR_EMAIL", "admin1@site.com, admin2@site.com");
```
Можно это сделать позже в почтовом сообщении: при возникновении первой ошибки скрипт создаст почтовое событие <code>DEBUG_MESSAGE</code>.
В нем можно указать через админку получателей данных ошибок
- в файле [<code>.settings.php</code>](https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=43&LESSON_ID=2795#exception_handling) прописать <code>'debug' => false</code>, чтобы ошибки не отображались всем посетителям, а только админам

