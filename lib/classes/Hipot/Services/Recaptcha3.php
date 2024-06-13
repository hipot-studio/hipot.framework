<?php

namespace Hipot\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\Request;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Bitrix\Main\Service\GeoIp;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;


/**
 * Represents a Recaptcha3 class.
 */
final class Recaptcha3
{
	const bool GLOBAL_ENABLED = true;
	const string CONFIG_FILE = '/local/php_interface/secrets/recaptcha_keys.php';

	const float SCORE_LIMIT = 0.6;
	const string EVENT_NAME = 'loadpage';
	const string TOKEN_REQUEST_NAME = 'token';

	/*
		CREATE TABLE `hi_recaptcha3_log` (
		  `ID` int(11) unsigned NOT NULL AUTO_INCREMENT,
		  `UF_DATETIME` datetime DEFAULT NULL,
		  `UF_URL` text,
		  `UF_REFERER` text,
		  `UF_USER_AGENT` text,
		  `UF_ADDR` VARCHAR(100),
	      `UF_SCORE` FLOAT,
		  `UF_DATA` text,
		  PRIMARY KEY (`ID`),
		  KEY `IDX_DT` (`UF_DATETIME`),
		  KEY `IDX_AR` (`UF_ADDR`)
		  KEY `IDX_SC` (`UF_SCORE`)
		);
	*/
	const string LOG_TABLE_NAME = 'hi_recaptcha3_log';
	const string URI_403 = '/403.php';
	const string URI_AJAX_BACKEND = '/ajax/recaptcha_token.php';

	const array ANTIVIRUS_BLOCK_ONLY_COUNTRIES = ['FI', 'DE', 'US'];
	const array ANTIVIRUS_BLOCK_PATH = [
		'/ru/teach/courses/*',
		'/en/learn/courses/*',
		'/ru/learn/courses/*',
		'/ru/*',
		'/en/*'
	];

	/**
	 * An array of country codes to be allowed by the application by default
	 * @var array OK_COUNTYRIES
	 */
	const array OK_COUNTRIES = ['RU'];

	/**
	 * Array of countries with bad traffic
	 * @var array
	 */
	const array ONLY_CHECK_BAD_TRAFIC_COUNTRIES = ['FI', 'DE'];

	/**
	 * Path to the file containing the list of IP addresses to be ignored by the reCAPTCHA verification process.
	 * @var string OK_IPS_FILE
	 */
	const string OK_IPS_FILE = '/local/php_interface/secrets/recaptcha_ignore_ips.php';

	/**
	 * dynamic enabled by request
	 * @var bool
	 */
	private bool $ENABLED;

	public function __construct(
		private ?Request $request = null,
		?string $httpCode = null
	)
	{
		$this->ENABLED = !preg_match('#(yandex)|(google)|(facebook)|(Lighthouse)|(bingbot)#i', $this->request->getServer()->getUserAgent());
		if ($httpCode == 404 || $httpCode == 403) {
			$this->ENABLED = false;
		}
	}

	public function isEnabled(): bool
	{
		return  self::GLOBAL_ENABLED && $this->ENABLED;
	}

	// region integration

	/**
	 * Retrieves the HTML code for the reCAPTCHA widget.
	 * Use outside <body> tag
	 *
	 * @return string The HTML code for the reCAPTCHA widget.
	 */
	public function getHtml(): string
	{
		if (! $this->isEnabled()) {
			return '';
		}

		$reCAPTCHA_site_key = include Loader::getDocumentRoot() . self::CONFIG_FILE;
		$reCAPTCHA_site_key = $reCAPTCHA_site_key['public'] ?? '';

		ob_start();
		if (!empty($reCAPTCHA_site_key)) {
			?>
			<script data-skip-moving="true" src="https://www.google.com/recaptcha/api.js?render=<?=$reCAPTCHA_site_key?>"></script>
			<script data-skip-moving="true">
				grecaptcha.ready(function() {
					grecaptcha.execute('<?=$reCAPTCHA_site_key?>', {action: '<?=self::EVENT_NAME?>'}).then(function(token) {
						$.post('<?=self::URI_AJAX_BACKEND?>', {
							'<?=self::TOKEN_REQUEST_NAME?>' : token,
							'uri' : location.href
						}, function (response) {
							try {
								if (!response['success'] && !response['is_good']) {
									window.location.replace('<?=self::URI_403?>');
								}
							} catch (e){}
							if (typeof appParams !== 'undefined' && appParams.IS_DEV) {
								console.log(response);
							}
						}, 'json');
					});
				});
			</script>
			<?
		}
		return ob_get_clean();
	}

	/**
	 * Sends a request to the reCAPTCHA server to verify the token.
	 * use in URI_AJAX_BACKEND page
	 * @example use Bitrix\Main\Web\Json;<br>
	 * use Hipot\Services\BitrixEngine;<br><br>
	 * $request = BitrixEngine::getInstance()->request;<br>
	 * $recaptcha = new Mgu\Recaptcha3($request);<br>
	 * $responseKeys = $recaptcha->sendRequestToCaptchaServer();<br>
	 * die(Json::encode($responseKeys));
	 *
	 * @return array The response from the reCAPTCHA server.
	 */
	public function sendRequestToCaptchaServer(): array
	{
		$userIp = $this->request->getServer()->getRemoteAddr();
		$userCountry = self::getCountryByIp($userIp);

		$bOkByCountry = false;
		if (self::isIgnoredIp($userIp) || (count(self::ONLY_CHECK_BAD_TRAFIC_COUNTRIES) > 0 && !in_array($userCountry, self::ONLY_CHECK_BAD_TRAFIC_COUNTRIES))) {
			$bOkByCountry = true;
		}

		if (!$bOkByCountry) {
			if (count(self::OK_COUNTRIES) > 0 &&
				(in_array($this->request->getServer()->get('GEOIP_COUNTRY_CODE'), self::OK_COUNTRIES) || in_array($userCountry, self::OK_COUNTRIES))
			) {
				$bOkByCountry = true;
			}
		}

		if (! $this->isEnabled() || $bOkByCountry) {
			return ['success' => true, 'is_good' => true, 'score' => 1.0];
		}

		$token = $this->request[self::TOKEN_REQUEST_NAME];
		if (empty($token)) {
			return ['success' => false, 'error' => 'empty token', 'score' => 0.0];
		}

		$secretKey = include Loader::getDocumentRoot() . self::CONFIG_FILE;
		$secretKey = $secretKey['private'] ?? '';
		if (empty($secretKey)) {
			return ['success' => false, 'error' => 'empty private key', 'score' => 1.0];
		}

		$url = 'https://www.google.com/recaptcha/api/siteverify';

		$data = ['secret' => $secretKey, 'response' => $token];
		$options = [
			'http' => [
				'header' => "Content-type: application/x-www-form-urlencoded\r\n",
				'method' => 'POST',
				'content' => http_build_query($data)
			]
		];
		$context = stream_context_create($options);
		try {
			$response     = \file_get_contents($url, false, $context);
			$responseKeys = json_decode($response, true);

			// 1 - значит это точно человек, а 0 - это точно бот
			// @see https://developers.google.com/recaptcha/docs/v3?hl=ru
			if ($responseKeys["success"] && $responseKeys["score"] > self::SCORE_LIMIT && $responseKeys["action"] == self::EVENT_NAME) {
				$responseKeys['is_good'] = true;
			} else {
				$responseKeys["success"] = false;
				$responseKeys['is_good'] = false;
				if (empty($responseKeys['score'])) {
					$responseKeys['score'] = 0.0;
				}
			}
			return $this->saveLogData($responseKeys);
		} catch (\Throwable $e) {
			return $this->saveLogData(['success' => false, 'is_good' => false, 'error' => $e->getMessage(), 'score' => 1.0]);
		}
	}

	/**
	 * Event handler for the "OnPageStart" event.
	 *
	 * @param BitrixEngine $be The Bitrix engine object.
	 *
	 * @return void
	 */
	public static function OnPageStartHandler(BitrixEngine $be): void
	{
		$self = new self($be->request);
		if (! $self->isEnabled()) {
			return;
		}
		unset($self);

		// region grecaptcha-badge-custom

		global $APPLICATION;
		ob_start();
		// see https://developers.google.com/recaptcha/docs/faq?hl=ru#id-like-to-hide-the-recaptcha-badge.-what-is-allowed
		?>
		<!--noindex-->
		<p class="grecaptcha-badge-custom">This site is protected by reCAPTCHA and the <a href="https://policies.google.com/privacy" rel="nofollow" target="_blank">Google Privacy Policy</a>.</p>
		<!--/noindex-->
		<?
		$APPLICATION->AddViewContent('MISC_END_FOOTER_CONTENT', ob_get_clean());

		// endregion

		$ip = (string)UUtils::getUserIp($be);
		if (self::isIgnoredIp($ip)) {
			return;
		}

		// region block bad recaptcha3 ips by bx-security module
		if (! Loader::includeModule('security')) {
			return;
		}

		$intervalDayCheck = 2;
		if (self::isAddrLocked($ip, $intervalDayCheck)) {
			$ruleId = self::addToSecurityStopList($ip, 3);

			if (!\CSite::InDir(self::URI_403)) {
				\LocalRedirect(self::URI_403 . '#' . $ruleId, true, '302 Found');
			}
		}
		// endregion
	}

	// endregion

	// region other stuff

	/**
	 * Checks if an IP address is locked based on the score and recent activity.
	 *
	 * @param ?string $ipAddress The IP address to check.
	 * @param int    $intervalDayCheck The number of days to check for recent activity.
	 * @param bool $checkMaxScore need check max score by all hits on needed interval
	 *
	 * @return bool True if the IP address is locked, false otherwise.
	 */
	public static function isAddrLocked(?string $ipAddress, int $intervalDayCheck = 3, bool $checkMaxScore = true): bool
	{
		if (! \filter_var($ipAddress, FILTER_VALIDATE_IP)) {
			return false;
		}
		$rs = BitrixEngine::getInstance()->connection->query(
			str_replace(['%table%', '%ip%', '%score%', '%days%'], [self::LOG_TABLE_NAME, $ipAddress, self::SCORE_LIMIT, $intervalDayCheck],
			' SELECT `UF_ADDR` FROM `%table%` '
					. ' WHERE `UF_SCORE` <= %score% AND `UF_ADDR` = "%ip%" '
					. (!$checkMaxScore ? '' : ' AND (SELECT `UF_SCORE` FROM `%table%` WHERE `UF_ADDR` = "%ip%" AND `UF_DATETIME` > DATE_ADD(NOW(), INTERVAL -%days% DAY) ORDER BY `UF_SCORE` DESC LIMIT 1) <= %score% ')
					. ' AND `UF_DATETIME` > DATE_ADD(NOW(), INTERVAL -%days% DAY) GROUP BY `UF_ADDR` ORDER BY `UF_ADDR`'
			)
		);
		return $rs->getSelectedRowsCount() > 0;
	}

	private static function isIgnoredIp(string $ip): bool
	{
		if (is_file(Loader::getDocumentRoot() . self::OK_IPS_FILE)) {
			$ips = include Loader::getDocumentRoot() . self::OK_IPS_FILE;
			if (in_array($ip, $ips)) {
				return true;
			}
		}
		return false;
	}

	private static function getCountryByIp(string $ip): string
	{
		try {
			$r = GeoIp\Manager::getDataResult($ip, \LANGUAGE_ID);
			$recheckCountryCode = $r?->getGeoData()?->countryCode;
		} catch (\Throwable $exception) {
			UUtils::logException($exception);
			$recheckCountryCode = '';
		}
		return (string)$recheckCountryCode;
	}

	private function saveLogData(array $data): array
	{
		$data['uri'] = $this->request['uri'];
		$data['IP'] = $this->request->getServer()->getRemoteAddr();

		$logTableDm = self::getLogTableEntity();

		$r = $logTableDm::add([
			'UF_DATETIME'		=> DateTime::createFromTimestamp(time()),
			'UF_URL'			=> $data['uri'],
			'UF_REFERER'		=> $this->request->getServer()->get('HTTP_REFERER'),
			'UF_USER_AGENT'		=> $this->request->getServer()->getUserAgent(),
			'UF_ADDR'			=> $data['IP'],
			'UF_SCORE'          => $data['score'],
			'UF_DATA'           => Json::encode($data)
		]);

		$needKeys = ['success', 'is_good', 'error'];
		foreach ($data as $key => $value) {
			if (!in_array($key, $needKeys)) {
				unset($data[$key]);
			}
		}

		return $data;
	}

	/**
	 * @noinspection MissingReturnTypeInspection
	 * @return \Bitrix\Main\ORM\Data\DataManager | string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\SystemException
	 */
	private static function getLogTableEntity()
	{
		$logTableEntity = Entity::compileEntity('Recaptcha3LogTable', [
			new Fields\IntegerField('ID', [
				'primary' => true
			]),
			new Fields\DatetimeField('UF_DATETIME'),
			new Fields\TextField('UF_URL'),
			new Fields\TextField('UF_REFERER'),
			new Fields\TextField('UF_USER_AGENT'),
			new Fields\TextField('UF_ADDR'),
			new Fields\FloatField('UF_SCORE'),
			new Fields\TextField('UF_DATA'),
		], [
			'table_name' => self::LOG_TABLE_NAME
		]);
		return $logTableEntity->getDataClass();
	}


	/**
	 * @param string $ip
	 * @param int    $intervalDayLock
	 *
	 * @return int|string
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function addToSecurityStopList(string $ip, int $intervalDayLock = 3): int|string
	{
		$ruleId = 0;

		$blockedRow = \Bitrix\Security\IPRuleInclIPTable::getByPrimary([
			'RULE_IP' => $ip
		])->fetch();

		$obIPRule  = new \CSecurityIPRule();
		$arFields = [
			"RULE_TYPE"     => "M",
			"ACTIVE"        => 'Y',
			"ADMIN_SECTION" => 'N',
			"SITE_ID"       => false,
			"SORT"          => 1010,
			"NAME"          => 'Recaptcha3 block ' . $ip,
			"ACTIVE_FROM"   => false,
			"ACTIVE_TO"     => (new \DateTime('now'))
				->modify(sprintf('+%s days', $intervalDayLock))
				->format(Date::convertFormatToPhp(\FORMAT_DATETIME)),
			"INCL_IPS"      => [$ip],
			"EXCL_IPS"      => [],
			"INCL_MASKS"    => self::ANTIVIRUS_BLOCK_PATH,
			"EXCL_MASKS"    => []
		];

		if (!isset($blockedRow['RULE_IP'])) {
			if (count(self::ANTIVIRUS_BLOCK_ONLY_COUNTRIES) > 0) {
				$recheckCountryCode = self::getCountryByIp($ip);
				if ($recheckCountryCode === '' || in_array($recheckCountryCode, self::ANTIVIRUS_BLOCK_ONLY_COUNTRIES)) {
					$ruleId = $obIPRule->Add($arFields);
				} else {
					$ruleId = 'tmp';
				}
			} else {
				$ruleId = $obIPRule->Add($arFields);
			}
		} else {
			// get field with check
			$ruleId = (int)\Hipot\Model\EntityHelper::getRowField($blockedRow, 'IPRULE_ID', \Bitrix\Security\IPRuleInclIPTable::getEntity());
			$blockedRow += $obIPRule::GetList([
				"RULE_TYPE",
				"ACTIVE",
				"ADMIN_SECTION",
				"SITE_ID",
				"NAME",
				"ACTIVE_FROM",
				"ACTIVE_TO",
			], ['ID' => $ruleId], ['ID' => 'ASC'])->Fetch();
			$cntUpd = 0;
			if (preg_match('#\((?<cnt>\d+)\)$#', $blockedRow['NAME'], $m)) {
				$cntUpd = (int)$m['cnt'];
			}
			$cntUpd++;
			$arFields['NAME'] .= sprintf(' (%d)', $cntUpd);
			$bUpd             = $obIPRule->Update($ruleId, $arFields);
		}
		return $ruleId;
	}

	// endregion
}