<?php

declare(strict_types=1);

use Hipot\Services\DbResultGenerator;
use Hipot\Types\ObjectArItem;

final class LegacyDbResultFake extends CDBResult
{
	public int $fetchCalls = 0;
	/** @var list<array{0: bool, 1: bool}> */
	public array $getNextCalls = [];

	private int $fetchIndex = 0;
	private int $getNextIndex = 0;

	/**
	 * @param list<array<string, mixed>> $fetchRows
	 * @param list<array<string, mixed>> $getNextRows
	 */
	public function __construct(
		private readonly array $fetchRows = [],
		private readonly array $getNextRows = [],
		private readonly int $selectedRowsCount = 0,
	) {
	}

	public function Fetch(): array|false
	{
		$this->fetchCalls++;
		return $this->fetchRows[$this->fetchIndex++] ?? false;
	}

	public function GetNext(bool $textHtmlAuto = true, bool $useTilda = true): array|false
	{
		$this->getNextCalls[] = [$textHtmlAuto, $useTilda];
		return $this->getNextRows[$this->getNextIndex++] ?? false;
	}

	public function SelectedRowsCount(): int
	{
		return $this->selectedRowsCount;
	}
}

final class IterableDbResultFake implements IteratorAggregate
{
	/** @param list<array<string, mixed>|object> $rows */
	public function __construct(
		private readonly array $rows,
		private readonly int $selectedRowsCount = 0,
	) {
	}

	public function getIterator(): Traversable
	{
		yield from $this->rows;
	}

	public function getSelectedRowsCount(): int
	{
		return $this->selectedRowsCount;
	}
}

it('rejects an unsupported result object', function (): void {
	new DbResultGenerator(new stdClass());
})->throws(InvalidArgumentException::class, 'Wrong type of $result :: stdClass');

it('iterates a legacy result through Fetch', function (): void {
	$result = new LegacyDbResultFake(fetchRows: [
		['ID' => 10, 'NAME' => 'First'],
		['ID' => 20, 'NAME' => 'Second'],
	]);

	$rows = iterator_to_array(new DbResultGenerator($result), false);

	expect($rows)->toBe([
		['ID' => 10, 'NAME' => 'First'],
		['ID' => 20, 'NAME' => 'Second'],
	])->and($result->fetchCalls)->toBe(3)
		->and($result->getNextCalls)->toBe([]);
});

it('uses GetNext with escaped and tilde fields when extra data is requested', function (): void {
	$result = new LegacyDbResultFake(getNextRows: [
		['NAME' => '&lt;b&gt;Title&lt;/b&gt;', '~NAME' => '<b>Title</b>'],
	]);

	$rows = iterator_to_array(new DbResultGenerator($result, getExtra: true), false);

	expect($rows)->toBe([
		['NAME' => '&lt;b&gt;Title&lt;/b&gt;', '~NAME' => '<b>Title</b>'],
	])->and($result->fetchCalls)->toBe(0)
		->and($result->getNextCalls)->toBe([
			[true, true],
			[true, true],
		]);
});

it('converts legacy rows to ObjectArItem instances recursively', function (): void {
	$result = new LegacyDbResultFake(fetchRows: [
		['ID' => 10, 'META' => ['ACTIVE' => true]],
	]);

	$rows = (new DbResultGenerator($result, returnObjects: true))->fetchAll();

	expect($rows)->toHaveCount(1)
		->and($rows[0])->toBeInstanceOf(ObjectArItem::class)
		->and($rows[0]['ID'])->toBe(10)
		->and($rows[0]['META'])->toBeInstanceOf(ObjectArItem::class)
		->and($rows[0]['META']['ACTIVE'])->toBeTrue();
});

it('iterates an IteratorAggregate result and applies the configured transformation', function (): void {
	$result = new IterableDbResultFake([
		['ID' => 10],
		(object)['ID' => 20, 'META' => (object)['TYPE' => 'orm']],
	]);

	$rows = iterator_to_array(new DbResultGenerator($result, returnObjects: true), false);

	expect($rows)->toHaveCount(2)
		->and($rows[0])->toBeInstanceOf(ObjectArItem::class)
		->and($rows[0]['ID'])->toBe(10)
		->and($rows[1])->toBeInstanceOf(ObjectArItem::class)
		->and($rows[1]['ID'])->toBe(20)
		->and($rows[1]['META']['TYPE'])->toBe('orm');
});

it('fetches all rows from an IteratorAggregate result as arrays by default', function (): void {
	$result = new IterableDbResultFake([
		['ID' => 10],
		(object)['ID' => 20],
	]);

	expect((new DbResultGenerator($result))->fetchAll())->toBe([
		['ID' => 10],
		['ID' => 20],
	]);
});

it('reads the selected row count from both supported result types', function (): void {
	$legacy = new DbResultGenerator(new LegacyDbResultFake(selectedRowsCount: 7));
	$iterable = new DbResultGenerator(new IterableDbResultFake([], selectedRowsCount: 11));

	expect($legacy->getSelectedRowsCount())->toBe(7)
		->and($iterable->getSelectedRowsCount())->toBe(11);
});

it('returns zero when a result does not expose a selected row count', function (): void {
	$result = new class implements IteratorAggregate {
		public function getIterator(): Traversable
		{
			return new ArrayIterator([]);
		}
	};

	expect((new DbResultGenerator($result))->getSelectedRowsCount())->toBe(0);
});
