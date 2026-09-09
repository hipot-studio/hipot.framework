<?php

declare(strict_types=1);

namespace Bitrix\Main {
	class HttpRequest extends Request
	{
		/** @var string[] */
		public static array $systemParameters = ['sessid'];

		public static function getSystemParameters(): array
		{
			return self::$systemParameters;
		}
	}

	final class ExceptionHandler
	{
		/** @var \Throwable[] */
		public array $exceptions = [];

		public function writeToLog(\Throwable $exception): void
		{
			$this->exceptions[] = $exception;
		}
	}

	final class UserFieldLangTable
	{
		public static function getEntity(): string
		{
			return self::class;
		}
	}
}

namespace Bitrix\Main\Grid {
	final class Declension
	{
		public function __construct(
			private readonly string $one,
			private readonly string $few,
			private readonly string $many,
		) {
		}

		public function get(int $number): string
		{
			$number = abs($number) % 100;
			$last = $number % 10;
			if ($number > 10 && $number < 20) {
				return $this->many;
			}
			return match (true) {
				$last === 1 => $this->one,
				$last >= 2 && $last <= 4 => $this->few,
				default => $this->many,
			};
		}
	}
}

namespace Bitrix\Main\Text {
	final class StringHelper
	{
		public static function camel2snake(string $value): string
		{
			return strtolower((string)preg_replace('/(?<!^)[A-Z]/', '_$0', $value));
		}
	}
}

namespace Bitrix\Main\Web {
	final class HttpHeaders
	{
		public function __construct(private readonly array $headers)
		{
		}

		public function toArray(): array
		{
			return $this->headers;
		}
	}

	final class HttpClient
	{
		public static string $body = '';
		public static array $headers = [];
		public static int $status = 200;
		public static array $requests = [];

		public function __construct(public readonly array $options = [])
		{
		}

		public function get(string $url): string
		{
			self::$requests[] = ['method' => 'GET', 'url' => $url, 'options' => $this->options];
			return self::$body;
		}

		public function head(string $url): HttpHeaders
		{
			self::$requests[] = ['method' => 'HEAD', 'url' => $url, 'options' => $this->options];
			return new HttpHeaders(self::$headers);
		}

		public function getStatus(): int
		{
			return self::$status;
		}
	}

	final class Uri
	{
		private string $path;
		private array $params = [];

		public function __construct(string $uri)
		{
			$this->path = (string)(parse_url($uri, PHP_URL_PATH) ?: '');
			parse_str((string)parse_url($uri, PHP_URL_QUERY), $this->params);
		}

		public function getQuery(): string
		{
			return http_build_query($this->params);
		}

		public function getPath(): string
		{
			return $this->path;
		}

		public function deleteParams(array $names): void
		{
			foreach ($names as $name) {
				unset($this->params[$name]);
			}
		}

		public function addParams(array $params): void
		{
			$this->params = array_replace($this->params, $params);
		}

		public function getUri(): string
		{
			$query = $this->getQuery();
			return $this->path . ($query === '' ? '' : '?' . $query);
		}
	}
}

namespace Bitrix\Main\Service\GeoIp {
	final class Data
	{
		public function __construct(public readonly ?string $countryCode = null)
		{
		}
	}

	final class DataResult
	{
		public function __construct(private readonly ?Data $data)
		{
		}

		public function getGeoData(): ?Data
		{
			return $this->data;
		}
	}

	final class Manager
	{
		public static ?DataResult $result = null;
		public static ?\Throwable $exception = null;

		public static function getDataResult(string $ip, string $languageId): ?DataResult
		{
			if (self::$exception !== null) {
				throw self::$exception;
			}
			return self::$result;
		}
	}
}

namespace Bitrix\Main\Composite {
	final class Storage
	{
		public bool $deleted = false;

		public function delete(): void
		{
			$this->deleted = true;
		}
	}

	final class Page
	{
		public static ?Storage $storage = null;
		public static string $lastUri = '';

		public function __construct(string $uri)
		{
			self::$lastUri = $uri;
		}

		public function getStorage(): ?Storage
		{
			return self::$storage;
		}
	}
}

namespace Bitrix\Main\Config {
	final class Option
	{
		public static array $options = ['main' => ['-' => ['use_minified_assets' => 'Y']]];

		public static function get(string $module, string $name, string $default = ''): string
		{
			return self::$options[$module]['-'][$name] ?? $default;
		}
	}
}

namespace Bitrix\Iblock\Component {
	final class Tools
	{
		public static array $calls = [];

		public static function process404(string $message, bool $defineConstant, bool $setStatus, bool $showPage): void
		{
			self::$calls[] = compact('message', 'defineConstant', 'setStatus', 'showPage');
		}
	}
}

namespace {
	if (!defined('LANGUAGE_ID')) {
		define('LANGUAGE_ID', 'en');
	}
	if (!defined('SITE_DIR')) {
		define('SITE_DIR', '/');
	}

	function randString(int $length = 8): string
	{
		return str_repeat('x', $length);
	}

	class CUtil
	{
		public static array $lastTranslit = [];

		public static function translit(string $text, string $lang, array $options): string
		{
			self::$lastTranslit = compact('text', 'lang', 'options');
			$value = strtolower(trim($text));
			$value = (string)preg_replace('/[^a-z0-9]+/i', $options['replace_other'], $value);
			return substr(trim($value, $options['replace_other']), 0, $options['max_len']);
		}
	}

	final class StubLegacyResult
	{
		public function __construct(private array $rows)
		{
		}

		public function Fetch(): array|false
		{
			return array_shift($this->rows) ?? false;
		}
	}

	class CFormResult
	{
		public static int|false $nextId = 1;
		public static array $addCalls = [];
		public static array $events = [];
		public static array $mail = [];

		public static function Add(int $formId, array $values): int|false
		{
			self::$addCalls[] = compact('formId', 'values');
			return self::$nextId;
		}

		public static function SetEvent(int $resultId): void
		{
			self::$events[] = $resultId;
		}

		public static function Mail(int $resultId): void
		{
			self::$mail[] = $resultId;
		}
	}

	class CFormCRM
	{
		public static array $calls = [];

		public static function onResultAdded(int $formId, int $resultId): void
		{
			self::$calls[] = compact('formId', 'resultId');
		}
	}

	class CFile
	{
		public static function MakeFileArray(mixed $value): array
		{
			return ['source' => $value];
		}
	}

	class _CIBElement
	{
	}

	class CBitrixComponent
	{
		public static array $available = [];
		public static array $includedClasses = [];
		public array $icons = [];

		public function initComponent(string $name): bool
		{
			echo "init:{$name}";
			return self::$available[$name] ?? false;
		}

		public static function includeComponentClass(string $name): ?string
		{
			return self::$includedClasses[$name] ?? null;
		}

		public function addIncludeAreaIcons(array $icons): void
		{
			$this->icons = $icons;
		}
	}

	class CBitrixComponentTemplate
	{
		public array $editActions = [];
		public array $deleteActions = [];

		public function AddEditAction(mixed ...$args): void
		{
			$this->editActions[] = $args;
		}

		public function AddDeleteAction(mixed ...$args): void
		{
			$this->deleteActions[] = $args;
		}

		public function GetEditAreaId(int $id): string
		{
			return 'edit-' . $id;
		}
	}

	class CIBlock
	{
		public static function GetPanelButtons(int $iblockId, int $elementId, int $sectionId, array $options): array
		{
			return [
				'edit' => [
					'edit_element' => ['ACTION_URL' => "/edit/{$elementId}"],
					'delete_element' => ['ACTION_URL' => "/delete/{$elementId}"],
					'edit_section' => ['ACTION_URL' => "/edit-section/{$sectionId}"],
					'delete_section' => ['ACTION_URL' => "/delete-section/{$sectionId}"],
				],
			];
		}

		public static function GetComponentMenu(string $mode, array $buttons): array
		{
			return compact('mode', 'buttons');
		}

		public static function GetArrayByID(int $iblockId, string $field): string
		{
			return "{$iblockId}:{$field}";
		}
	}

	class CComponentEngine
	{
		public static function MakeComponentUrlTemplates(array $defaults, array $templates): array
		{
			return array_replace($defaults, $templates);
		}

		public static function MakeComponentVariableAliases(array $defaults, array $aliases): array
		{
			return array_replace($defaults, $aliases);
		}

		public static function ParseComponentPath(string $folder, array $templates, array &$variables): string
		{
			$variables['PARSED'] = $folder;
			return array_key_first($templates) ?: '';
		}

		public static function InitComponentVariables(string $page, array $names, array $aliases, array &$variables): void
		{
			foreach ($names as $name) {
				$variables[$name] = $aliases[$name] ?? $name;
			}
		}
	}

	class CSite
	{
		public static array $matchingDirectories = [];

		public static function InDir(string $directory): bool
		{
			return in_array($directory, self::$matchingDirectories, true);
		}
	}

	class CHTTP
	{
		public static ?string $status = null;

		public static function setStatus(string $status): void
		{
			self::$status = $status;
		}
	}
}
