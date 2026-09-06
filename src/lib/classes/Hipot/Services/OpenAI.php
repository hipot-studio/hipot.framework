<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 16.05.2023 11:07
 * @version pre 1.0
 */
namespace Hipot\Services;

use Bitrix\Main\Web\Json;
use Orhanerday\OpenAi\OpenAi as OrhOpenAi;

final class OpenAI
{
	private OrhOpenAi $openAi;

	public function __construct(OrhOpenAi $openAi)
	{
		$this->openAi = $openAi;
	}

	public function getOpenAi(): OrhOpenAi
	{
		return $this->openAi;
	}

	/**
	 * @return array
	 * @param array{'prompt': string} $params
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function completion(array $params): array
	{
		if (empty($params['prompt'])) {
			throw new \RuntimeException('Empty prompt to generate completion');
		}

		$complete = $this->openAi->completion(array_merge([
			'model' => 'text-davinci-002',
			'temperature' => 0.9,
			'max_tokens' => 150,
			'frequency_penalty' => 0,
			'presence_penalty' => 0.6,
		], $params));
		return Json::decode($complete);
	}

	/**
	 * @param array{'messages':array{'role':string, 'content':string}} $params
	 * @return array{'choices':array}
	 * @throws \Bitrix\Main\ArgumentException
	 */
	public function chat(array $params): array
	{
		if (!is_array($params['messages']) || count($params['messages']) <= 0) {
			throw new \RuntimeException('Empty messages to generate chat');
		}

		$chat = $this->openAi->chat(array_merge([
			'model' => 'gpt-3.5-turbo',
			'messages' => [],
			'temperature' => 1.0,
			'max_tokens' => 4000,
			'frequency_penalty' => 0,
			'presence_penalty' => 0,
		], $params));
		return Json::decode($chat);
	}
}