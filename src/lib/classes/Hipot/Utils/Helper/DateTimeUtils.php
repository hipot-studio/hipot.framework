<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:12
 * @version pre 1.0
 */
namespace Hipot\Utils\Helper;

use Hipot\Utils\UUtils;

trait DateTimeUtils
{
	/**
	 * Пересекаются ли времена заданные unix-таймштампами.
	 *
	 * Решение сводится к проверке границ одного отрезка на принадлежность другому отрезку
	 * и наоборот. Достаточно попадания одной точки.
	 *
	 * @param int $left1_ts
	 * @param int $right1_ts
	 * @param int $left2_ts
	 * @param int $right2_ts
	 * @return boolean
	 */
	public static function IsIntervalsTsIncl($left1_ts, $right1_ts, $left2_ts, $right2_ts): bool
	{
		if ($left1_ts <= $left2_ts) {
			return $right1_ts >= $left2_ts;
		}

		return $left1_ts <= $right2_ts;
	}

	/**
	 * вернуть время года по таймстампу
	 * @param \DateTimeInterface $dateTime
	 * @return string Winter|Spring|Summer|Fall
	 */
	public static function timestampToSeason(\DateTimeInterface $dateTime): string
	{
		$dayOfTheYear = $dateTime->format('z');
		if ($dayOfTheYear < 80 || $dayOfTheYear > 356) {
			return 'Winter';
		}
		if ($dayOfTheYear < 173) {
			return 'Spring';
		}
		if ($dayOfTheYear < 266) {
			return 'Summer';
		}
		return 'Fall';
	}

	/**
	 * Утилитарная функция для красивого вывода периода (в секундах) в дни, часы, минуты и секунды
	 *
	 * @param int|string $duration Длительность периода в секундах
	 * @param bool $seconds = true
	 * @return string
	 */
	public static function secondsToTimeString(int|string $duration, bool $seconds = true): string
	{
		$timeStrings = array();

		$converted = [
			'days'    => floor($duration / (3600 * 24)),
			'hours'   => floor($duration / 3600),
			'minutes' => floor(($duration / 60) % 60),
			'seconds' => ($duration % 60)
		];

		if ($converted['days'] > 0) {
			$timeStrings[] = $converted['days'] . ' ' . UUtils::Suffix($converted['days'], 'день|дня|дней');
			$seconds = false;
		}

		if ($converted['hours'] > 0) {
			$timeStrings[] = $converted['hours'] . ' ' . UUtils::Suffix($converted['hours'], 'час|часа|часов');
			$seconds = false;
		}
		if ($converted['minutes'] > 0) {
			$timeStrings[] = $converted['minutes'] . ' мин.';
		}
		if ($converted['seconds'] > 0 && $seconds) {
			$timeStrings[] = $converted['seconds'] . ' сек.';
		}

		if(!empty($timeStrings)) {
			return implode(' ', $timeStrings);
		}

		return ' 0 секунд';
	}

	/**
	 * @param int $time seconds
	 * @return string
	 * @see https://en.wikipedia.org/wiki/ISO_8601#Durations
	 */
	public static function timeToIso8601Duration(int $time): string
	{
		$units = [
			"Y" => 365*24*3600,
			"D" =>     24*3600,
			"H" =>        3600,
			"M" =>          60,
			"S" =>           1,
		];
		$str = "P";
		$istime = false;
		foreach ($units as $unitName => &$unit) {
			$quot  = (int)($time / $unit);
			$time -= $quot * $unit;
			$unit  = $quot;
			if ($unit > 0) {
				if (!$istime && in_array($unitName, ["H", "M", "S"])) { // There may be a better way to do this
					$str .= "T";
					$istime = true;
				}
				$str .= $unit . $unitName;
			}
		}
		return $str;
	}
}