<?php

declare(strict_types=1);

function runSqlProfileScenario(string $scenario): array
{
	$process = proc_open(
		[PHP_BINARY, dirname(__DIR__) . '/Fixtures/sql_profile.php', $scenario],
		[1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
		$pipes,
	);
	if (!is_resource($process)) {
		throw new RuntimeException('Cannot start SQL profile test');
	}
	$output = stream_get_contents($pipes[1]);
	$error = stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	expect(proc_close($process))->toBe(0);
	return [json_decode($output, true, flags: JSON_THROW_ON_ERROR), $error];
}

it('does not start SQL tracking without a matching secret or perfmon', function (string $scenario): void {
	[$result] = runSqlProfileScenario($scenario);
	expect($result['started'])->toBe(0)
		->and($result['hits'])->toBe([]);
})->with(['no-secret', 'empty-secret', 'wrong-secret', 'array-trigger', 'no-module']);

it('saves one hit with SQL totals after stopping the tracker', function (): void {
	[$result, $error] = runSqlProfileScenario('success');
	expect($error)->toBe('')
		->and($result['started'])->toBe(1)
		->and($result['stopped'])->toBe(1)
		->and($result['hits'])->toHaveCount(1);
	$hit = $result['hits'][0];
	expect($hit['table'])->toBe('b_perf_hit')
		->and($hit['fields']['QUERIES'])->toBe(2)
		->and($hit['fields']['~QUERIES_TIME'])->toBe(0.75)
		->and($hit['fields']['IS_ADMIN'])->toBe('N')
		->and($hit['fields']['SCRIPT_NAME'])->toBe('/catalog/index.php')
		->and($hit['fields']['testTimeFormat'])->toBeTrue()
		->and($result['savedQueries']['hitId'])->toBe(42)
		->and($result['savedQueries']['queries'])->toHaveCount(2);
});

it('preserves an existing SQL tracker', function (): void {
	[$result, $error] = runSqlProfileScenario('existing-tracker');
	expect($result['started'])->toBe(0)
		->and($result['stopped'])->toBe(0)
		->and($result['hits'])->toBe([])
		->and($error)->toContain('SQL tracker');
});

it('does not stop a tracker installed by other code', function (): void {
	[$result, $error] = runSqlProfileScenario('replaced-tracker');
	expect($result['started'])->toBe(1)
		->and($result['stopped'])->toBe(0)
		->and($result['hits'])->toBe([])
		->and($error)->toContain('SQL tracker');
});

it('does not save SQL when the hit cannot be inserted', function (string $scenario): void {
	[$result, $error] = runSqlProfileScenario($scenario);
	expect($result['stopped'])->toBe(1)
		->and($result['savedQueries'])->toBe([])
		->and($error)->toContain('hipot SQL profile:');
})->with(['insert-failed', 'exception']);
