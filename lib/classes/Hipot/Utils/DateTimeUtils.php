<?php
/**
 * hipot studio source file <info AT hipot-studio DOT com>
 * Created 11.05.2023 17:12
 * @version pre 1.0
 */
namespace Hipot\Utils;

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
}