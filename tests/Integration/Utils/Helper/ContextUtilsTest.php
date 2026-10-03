<?php

declare(strict_types=1);

use Bitrix\Main\Application;
use Bitrix\Main\Context;
use Bitrix\Main\HttpRequest;
use Bitrix\Main\HttpResponse;
use Bitrix\Main\Server;
use Hipot\Services\BitrixEngine;
use Hipot\Utils\Helper\ContextUtils;

function contextUtilsFixture(): object
{
	return new class {
		use ContextUtils;
	};
}

function contextUtilsRequest(array $serverValues = [], array $query = [], array $post = []): HttpRequest
{
	$server = new Server(array_replace([
		'HTTP_HOST' => 'example.test',
		'REQUEST_METHOD' => 'GET',
		'REQUEST_URI' => '/index.php',
		'QUERY_STRING' => http_build_query($query),
		'SCRIPT_NAME' => '/index.php',
	], $serverValues));

	return new HttpRequest($server, $query, $post, [], []);
}

function withContextUtilsRequest(HttpRequest $request, callable $callback): mixed
{
	$application = Application::getInstance();
	$originalContext = $application->getContext();
	$context = new Context($application);
	$context->initialize($request, new HttpResponse(), $request->getServer());
	$application->setContext($context);
	BitrixEngine::resetInstance();

	try {
		return $callback();
	} finally {
		$application->setContext($originalContext);
		BitrixEngine::resetInstance();
	}
}

beforeEach(function (): void {
	BitrixEngine::resetInstance();
});

afterEach(function (): void {
	BitrixEngine::resetInstance();
});

it('builds the PHP command for the Bitrix charset', function (): void {
	$expectedCharset = defined('BX_UTF') ? 'utf-8' : 'cp1251';

	expect(contextUtilsFixture()::getPhpPath())
		->toContain('php', 'default_charset="' . $expectedCharset . '"');
});

it('returns the first valid client IP source and the last forwarded address', function (): void {
	$request = contextUtilsRequest([
		'HTTP_CLIENT_IP' => 'not-an-ip',
		'HTTP_X_FORWARDED_FOR' => '198.51.100.4, 203.0.113.27',
		'HTTP_X_REAL_IP' => '192.0.2.18',
		'REMOTE_ADDR' => '127.0.0.1',
	]);
	$engine = new BitrixEngine(request: $request);

	expect(contextUtilsFixture()::getUserIp($engine))->toBe('203.0.113.27')
		->and(contextUtilsFixture()::getUserIp(new BitrixEngine(
			request: contextUtilsRequest(['REMOTE_ADDR' => 'invalid']),
		)))->toBeNull();
});

it('detects AJAX requests from parameters and Bitrix HTTP headers', function (): void {
	$parameterRequest = contextUtilsRequest(query: ['via_ajax' => 'Y']);
	$headerRequest = contextUtilsRequest(['HTTP_BX_AJAX' => 'true']);
	$regularRequest = contextUtilsRequest();

	expect(contextUtilsFixture()::isAjaxRequest(new BitrixEngine(request: $parameterRequest)))->toBeTrue()
		->and(contextUtilsFixture()::isAjaxRequest(new BitrixEngine(request: $headerRequest)))->toBeTrue()
		->and(contextUtilsFixture()::isAjaxRequest(new BitrixEngine(request: $regularRequest)))->toBeFalse();
});

it('detects navigation parameters in the active Bitrix request context', function (): void {
	$paginatedRequest = contextUtilsRequest(query: ['PAGEN_2' => '3']);
	$firstPageRequest = contextUtilsRequest(post: ['PAGEN_1' => '1']);

	expect(withContextUtilsRequest(
		$paginatedRequest,
		static fn (): bool => contextUtilsFixture()::isPageNavigation(),
	))->toBeTrue()
		->and(withContextUtilsRequest(
			$firstPageRequest,
			static fn (): bool => contextUtilsFixture()::isPageNavigation(),
		))->toBeFalse();
});

it('adds and removes query parameters from the current request URI', function (): void {
	$request = contextUtilsRequest([
		'REQUEST_URI' => '/catalog/items/?keep=1&drop=2',
	], [
		'keep' => '1',
		'drop' => '2',
	]);

	$result = withContextUtilsRequest(
		$request,
		static fn (): string => contextUtilsFixture()::getCurPageParamD7(
			['added' => 'value'],
			['drop'],
		),
	);

	expect($result)->toBe('/catalog/items/?keep=1&added=value');
});

it('creates an inline base64 URL for an existing image', function (): void {
	$imagePath = dirname(__DIR__, 4) . '/docs/img/hipot-studio-logo-horizontal.png';
	$inlineImage = contextUtilsFixture()::getInlineBase64Image($imagePath);

	expect($inlineImage)->toStartWith('data:image/png;base64,')
		->and(base64_decode(substr($inlineImage, strpos($inlineImage, ',') + 1), true))
		->toBe(file_get_contents($imagePath))
		->and(contextUtilsFixture()::getInlineBase64Image($imagePath . '.missing'))->toBeNull();
});
