<?php

namespace Hipot\Services;

use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Entity;
use Bitrix\Main\Request;
use Bitrix\Main\ORM\Fields;
use Bitrix\Main\Type\Date;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web\Json;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\UUtils;

/**
 * Represents a Recaptcha3 class.
 */
final class Recaptcha3
{
	const string CONFIG_FILE = '/bitrix/php_interface/secrets/recaptcha_keys.php';
	const float SCORE_LIMIT = 0.5;
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

	const bool GLOBAL_ENABLED = true;
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
		if (! $this->isEnabled()) {
			return ['success' => true, 'is_good' => true, 'score' => 1.0];
		}

		$token = $this->request[self::TOKEN_REQUEST_NAME];
		if (empty($token)) {
			return $this->saveLogData(['success' => false, 'error' => 'empty token', 'score' => 0.0]);
		}

		$secretKey = include Loader::getDocumentRoot() . self::CONFIG_FILE;
		$secretKey = $secretKey['private'] ?? '';
		if (empty($secretKey)) {
			return $this->saveLogData(['success' => false, 'error' => 'empty private key', 'score' => 0.0]);
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
			$response     = file_get_contents($url, false, $context);
			$responseKeys = json_decode($response, true);

			// 1 - значит это точно человек, а 0 - это точно бот
			// @see https://developers.google.com/recaptcha/docs/v3?hl=ru
			if ($responseKeys["success"] && $responseKeys["score"] > self::SCORE_LIMIT && $responseKeys["action"] == self::EVENT_NAME) {
				$responseKeys['is_good'] = true;
			} else {
				$responseKeys["success"] = false;
				$responseKeys['is_good'] = false;
			}

			return $this->saveLogData($responseKeys);
		} catch (\Throwable $e) {
			return $this->saveLogData(['success' => false, 'error' => $e->getMessage(), 'score' => 0.0]);
		}
	}

	private function saveLogData(array $data): array
	{
		$data['uri'] = $this->request['uri'];
		$data['IP'] = $this->request->getServer()->getRemoteAddr();

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

		$r = $logTableEntity->getDataClass()::add([
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
	 * Checks if an IP address is locked based on the score and recent activity.
	 *
	 * @param string $ipAddress The IP address to check.
	 * @param int    $intervalDayCheck The number of days to check for recent activity.
	 *
	 * @return bool True if the IP address is locked, false otherwise.
	 */
	public static function isAddrLocked(?string $ipAddress, int $intervalDayCheck = 3): bool
	{
		if (! \filter_var($ipAddress, FILTER_VALIDATE_IP)) {
			return false;
		}
		$rs = BitrixEngine::getInstance()->connection->query(
			sprintf('SELECT `UF_ADDR` FROM %s WHERE `UF_SCORE` <= 0.5 AND `UF_DATETIME` > DATE_ADD(NOW(), INTERVAL -%s DAY) AND `UF_ADDR` = "%s" GROUP BY `UF_ADDR` ORDER BY `UF_ADDR`',
				self::LOG_TABLE_NAME, $intervalDayCheck, $ipAddress)
		);
		return $rs->getSelectedRowsCount() > 0;
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

		// block bad recaptcha3 ips by bx-security module
		$ip = (string)UUtils::getUserIp($be);
		$intervalDayCheck = 2;
		if (self::isAddrLocked($ip, $intervalDayCheck)) {
			$ruleId = 0;
			if (Loader::includeModule('security')) {
				$blockedRow = \Bitrix\Security\IPRuleInclIPTable::getByPrimary([
					'RULE_IP' => $ip
				])->fetch();

				$ob = new \CSecurityIPRule();
				$arFields = [
					"RULE_TYPE" => "M",
					"ACTIVE" => 'Y',
					"ADMIN_SECTION" => 'N',
					"SITE_ID" => false,
					"SORT" => 1010,
					"NAME" => 'Recaptcha3 block ' . $ip,
					"ACTIVE_FROM" => false,
					"ACTIVE_TO" => (new \DateTime('now'))
						->modify( sprintf('+%s days', $intervalDayCheck) )
						->format( Date::convertFormatToPhp(FORMAT_DATETIME) ),
					"INCL_IPS" => [$ip],
					"EXCL_IPS" => [],
					"INCL_MASKS" => ['/*'],
					"EXCL_MASKS" => []
				];

				if (! isset($blockedRow['RULE_IP'])) {
					$ruleId = $ob->Add($arFields);
				} else {
					$ruleId = $blockedRow['RULE_ID'];
					$ob->Update($ruleId, $arFields);
				}
			}
			LocalRedirect(self::URI_403 . '#' . $ruleId, true, '302 Found');
		}
	}
}