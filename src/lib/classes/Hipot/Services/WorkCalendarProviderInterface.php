<?php

declare(strict_types=1);

namespace Hipot\Services;

interface WorkCalendarProviderInterface
{
	/** @return list<string|\DateTimeInterface> */
	public function getHolidays(): array;

	/** @return list<string|\DateTimeInterface> */
	public function getWorkdays(): array;
}
