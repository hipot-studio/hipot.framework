# Агенты Bitrix

Пространство имён `Hipot\BitrixUtils\Agent` предоставляет объектную обёртку над штатными агентами Bitrix. Оно позволяет собирать строку запуска через builder и выполнять агент как обычный объект с конструктором и публичным методом.

Реализация основана на подходе из [Console Jedi](https://github.com/notamedia/console-jedi/blob/master/docs/ru/agent.md), но работает независимо от его консольных команд.

В API входят три элемента:

- `AgentTrait` превращает класс в исполняемый агент;
- `AgentTask` собирает параметры и регистрирует агента через `CAgent::AddAgent()`;
- `AgentHelper` формирует строку агента, которая сохраняется в поле `b_agent.NAME`.

## Быстрый пример

Класс агента подключает `AgentTrait` и предоставляет публичный метод, содержащий полезную работу:

```php
<?php

namespace Vendor\Module\Agent;

use Hipot\BitrixUtils\Agent\AgentTrait;

final class CleanupAgent
{
	use AgentTrait;

	public function agentExecute(int $lastProcessedId = 0): string
	{
		// Обработать очередную порцию данных после $lastProcessedId.
		$nextProcessedId = $lastProcessedId + 100;
		$hasMoreItems = true;

		if (!$hasMoreItems) {
			return '';
		}

		return $this->getAgentName([
			'agentExecute' => [$nextProcessedId],
		]);
	}
}
```

Регистрация агента:

```php
<?php

use Hipot\BitrixUtils\Agent\AgentTask;
use Vendor\Module\Agent\CleanupAgent;

$agentTaskId = AgentTask::build()
	->setClass(CleanupAgent::class)
	->setConstructorArgs([])
	->setCallChain([
		'agentExecute' => [0],
	])
	->setInterval(15)
	->setModule('vendor.module')
	->create();
```

В таблицу агентов будет записана строка:

```php
\Vendor\Module\Agent\CleanupAgent::agent()->agentExecute(0);
```

`create()` возвращает идентификатор созданного агента либо `false`, если Bitrix не смог его зарегистрировать.

## Как выполняется агент

Bitrix исполняет сохранённую строку как PHP-код:

```php
CleanupAgent::agent()->agentExecute(0);
```

Выполнение проходит в следующем порядке:

1. Статический метод `agent()` сохраняет переданные аргументы конструктора и включает режим агента.
2. Через `ReflectionClass` создаётся экземпляр класса.
3. Аргументы `agent()` передаются в конструктор этого экземпляра.
4. На созданном объекте вызывается цепочка из `setCallChain()`.
5. Результат последнего метода возвращается Bitrix.

Если конструктору нужны аргументы, их сигнатуры должны совпадать:

```php
<?php

final class ImportAgent
{
	use \Hipot\BitrixUtils\Agent\AgentTrait;

	public function __construct(
		private readonly int $profileId,
		private readonly bool $dryRun,
	) {
	}

	public function execute(int $offset): string
	{
		// Выполнить порцию импорта.

		return $this->getAgentName([
			'execute' => [$offset + 100],
		]);
	}
}
```

Такой класс регистрируется следующим образом:

```php
$agentTaskId = \Hipot\BitrixUtils\Agent\AgentTask::build()
	->setClass(ImportAgent::class)
	->setConstructorArgs([15, false])
	->setCallChain([
		'execute' => [0],
	])
	->setInterval(60)
	->setModule('vendor.module')
	->create();
```

Результирующая строка:

```php
\Vendor\Module\Agent\ImportAgent::agent(15, false)->execute(0);
```

## Продолжение и завершение

Метод агента управляет следующим запуском своим возвращаемым значением:

- строка агента планирует следующий запуск;
- пустая строка завершает выполнение и удаляет непериодический агент из очереди.

Для продолжения с новыми аргументами используйте `getAgentName()`:

```php
public function agentExecute(int $lastProcessedId = 0): string
{
	$nextProcessedId = $this->processBatch($lastProcessedId);

	if ($nextProcessedId === null) {
		return '';
	}

	return $this->getAgentName([
		'agentExecute' => [$nextProcessedId],
	]);
}
```

`getAgentName()` повторно использует аргументы, с которыми объект был создан через `agent()`. Поэтому продолжение сохранит параметры конструктора и заменит только аргументы вызываемого метода.

Строку следующего запуска можно собрать и явно:

```php
return \Hipot\BitrixUtils\Agent\AgentTask::build()
	->setClass(self::class)
	->setConstructorArgs([])
	->setCallChain([
		'agentExecute' => [$nextProcessedId],
	])
	->createRunString();
```

`createRunString()` только формирует строку. Новый агент в базе данных при этом не создаётся.

## Длительное выполнение и `pingAgent()`

Если одна итерация работает долго, защищённый метод `pingAgent()` переносит `DATE_CHECK` текущего агента. Это сообщает Bitrix, что процесс продолжает работу:

```php
public function agentExecute(int $offset = 0): string
{
	foreach ($this->loadItems($offset) as $item) {
		$this->processItem($item);

		$this->pingAgent(20, [
			'agentExecute' => [$offset],
		]);
	}

	return '';
}
```

Первый аргумент `pingAgent()` задаётся в минутах. Метод выполняет обновление только для объекта, созданного через `agent()`. При обычном `new CleanupAgent()` вызов ничего не делает.

Цепочка, переданная в `pingAgent()`, должна соответствовать текущей строке агента: по ней выполняется поиск записи через `CAgent::GetList()`.

Проверить режим можно явно:

```php
if ($this->isAgentMode()) {
	// Объект создан вызовом ClassName::agent().
}
```

## Параметры `AgentTask`

| Метод | Назначение |
|---|---|
| `build()` | Создаёт новый builder. |
| `setClass(string $className)` | Задаёт FQCN класса с `AgentTrait`. |
| `setConstructorArgs(array $args)` | Задаёт аргументы `agent()` и конструктора класса. |
| `setCallChain(array $callChain)` | Задаёт методы и их аргументы после `agent()`. |
| `setModule(string $moduleName)` | Задаёт идентификатор модуля для `CAgent::AddAgent()`. |
| `setInterval(int $seconds)` | Задаёт интервал запуска в секундах. |
| `setPeriodically(bool $periodically)` | Управляет значением `IS_PERIOD`; по умолчанию `false`. |
| `setActive(bool $active)` | Создаёт активный или выключенный агент; по умолчанию `true`. |
| `setExecutionTime(DateTime $time)` | Задаёт время первого запуска; по умолчанию используется текущее время. |
| `setSort(int $sort)` | Задаёт сортировку агента. |
| `setUserId(int $userId)` | Задаёт пользователя, от имени которого выполняется агент. |
| `create(bool $checkExist = false)` | Регистрирует агента и возвращает его ID либо `false`. |
| `createRunString()` | Возвращает строку запуска без записи в базу. |

Перед `create()` явно задавайте как минимум класс, цепочку вызовов, модуль и интервал. Передавайте FQCN через `SomeAgent::class`, без начального обратного слеша: `AgentHelper` добавляет его самостоятельно. `setConstructorArgs()` можно пропустить только для конструктора без аргументов — начальное значение уже равно пустому массиву.

Для `setExecutionTime()` используется `Bitrix\Main\Type\DateTime`:

```php
use Bitrix\Main\Type\DateTime;
use Hipot\BitrixUtils\Agent\AgentTask;

$firstRun = DateTime::createFromTimestamp(time() + 300);

$agentTaskId = AgentTask::build()
	->setClass(CleanupAgent::class)
	->setConstructorArgs([])
	->setCallChain(['agentExecute' => [0]])
	->setInterval(60)
	->setExecutionTime($firstRun)
	->setModule('vendor.module')
	->create();
```

Чтобы Bitrix проверил существование агента с теми же параметрами, передайте `true` в `create()`:

```php
$agentTaskId = $task->create(checkExist: true);
```

При найденном дубликате метод вернёт `false`, а Bitrix запишет причину в глобальное исключение приложения.

## Формат `setCallChain()`

Фактическая реализация ожидает ассоциативный массив, где ключ является именем метода, а значение — массивом его аргументов:

```php
->setCallChain([
	'firstMethod' => [10],
	'secondMethod' => ['done'],
])
```

Он создаёт строку:

```php
\Vendor\Module\Agent\ExampleAgent::agent()->firstMethod(10)->secondMethod("done");
```

Если в цепочке несколько методов, каждый промежуточный метод должен вернуть объект, на котором можно вызвать следующий метод.

PHPDoc в текущем `AgentTask` показывает вложенную форму:

```php
[
	['execute' => [100500]],
]
```

Она не соответствует реализации `AgentHelper::createName()`: числовой ключ будет воспринят как имя метода. Используйте только ассоциативную форму `['execute' => [100500]]`.

## Ограничения аргументов

`AgentHelper` преобразует аргументы через `json_encode()`, а затем вставляет результат в исполняемую PHP-строку. Надёжно передавайте простые значения:

- `null`, `bool`, `int` и `float`;
- строки без пользовательского PHP-кода;
- индексные массивы из простых значений.

Не передавайте объекты, ресурсы, замыкания и ассоциативные массивы. JSON-объект записывается в виде `{...}`, который не является синтаксисом PHP. Unicode-символы по умолчанию преобразуются в последовательности `\uXXXX` и могут не восстановиться в исходное значение внутри PHP-строки.

Имена класса и методов также попадают в исполняемую строку. Они должны задаваться кодом приложения, а не поступать напрямую из HTTP-запроса.

Builder рассчитан на одно создание. `create()` преобразует внутренние boolean-поля в значения `Y`/`N`, поэтому для повторной регистрации создавайте новый объект через `AgentTask::build()`.

## Практический пример из модуля

Запуск диагностики в фоне можно зарегистрировать так:

```php
$agentTaskId = \Hipot\BitrixUtils\Agent\AgentTask::build()
	->setClass(self::class)
	->setConstructorArgs([])
	->setCallChain([
		'agentExecute' => [0],
	])
	->setInterval(15)
	->setModule(\CCleanMain::MODULE_ID)
	->create();
```

Метод `agentExecute()` обрабатывает одну порцию данных. Если работа не закончена, он возвращает строку следующего запуска:

```php
public function agentExecute(int $lastStepId = 0): string
{
	[$continue, $processedStepId] = $this->processStep($lastStepId);

	if (!$continue) {
		return '';
	}

	return \Hipot\BitrixUtils\Agent\AgentTask::build()
		->setClass(self::class)
		->setConstructorArgs([])
		->setCallChain([
			'agentExecute' => [$processedStepId],
		])
		->createRunString();
}
```

## Рекомендации

- Делите тяжёлую задачу на небольшие повторяемые порции.
- Возвращайте пустую строку только после полного завершения задачи.
- Для продолжения возвращайте строку с тем же классом, конструктором и актуальным состоянием шага.
- Делайте обработку идемпотентной: повтор одного шага не должен повреждать данные.
- Не храните большой payload в строке агента; сохраняйте состояние в таблице или настройках модуля, а агенту передавайте идентификатор.
- Используйте `pingAgent()` только для действительно долгой итерации.
- Проверяйте результат `create()` и сообщение `$APPLICATION->GetException()` при `false`.
