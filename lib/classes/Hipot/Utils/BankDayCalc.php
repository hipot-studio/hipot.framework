<?
/**
 * @copyright 2011, WebExpert
 */

/**
 * Работаем с банковскими (рабочими) днями
 * Класс позволяет получить кол-во рабочий дней диапазона дат, либо проверить еще некоторые моменты
 *
 * @author hipot
 * @see http://habrahabr.ru/blogs/php/67092/
 *
 * @example
 * $oBankDay = new BankDay($arNoWorkCustomDays, $arWorkHollydays);<br />
 * // можно передавать таймштампы<br />
 * $countWorkDays = $oBankDay->getNumDays($arTask['Start'], $arTask['Finish']);<br />
 *
 */
class BankDayCalc
{

	/**
	 * Массив выходных (формат m-d)
	 *
	 * 1, 2, 3, 4 и 5 января - Новогодние каникулы;
	 * 7 января - Рождество Христово;
	 * 23 февраля - День защитника Отечества;
	 * 8 марта - Международный женский день;
	 * 1 мая - Праздник Весны и Труда;
	 * 9 мая - День Победы;
	 * 12 июня - День России;
	 * 4 ноября - День народного единств
	 *
	 *
	 * @var array
	 */
	static $def_holidays = array('01-01', '01-02', '01-03', '01-04', '01-05', '01-07', '02-23', '03-08',
		'05-01', '05-09', '06-12', '11-04');

	/**
	 * Выходные в неделе
	 * 0 - Воскресенье
	 * 6 - Суббота
	 *
	 * @var array
	 */
	static $def_weekends = array(0, 6);


	/**
	 * массив рабочих выходных (исключения, формат Y-m-d)
	 *
	 * @var array
	 */
	var $work_exceptions;


	/**
	 * Текущие выходные, либо берется значение по умолчанию из $def_holidays
	 *
	 * @var array
	 */
	var $holidays;

	/**
	 * Держит настройку выходных
	 *
	 * @var array
	 */
	var $weekends;


	/**
	 * конструктор php4
	 *
	 * @param array $holidays массив выходных, если false, то берется из $def_holidays
	 * @param array $work_exceptions массив рабочих дней (исключения)
	 */
	function BankDay($holidays = false, $work_exceptions = array())
	{
		if ($holidays === false) {
			$this->holidays = self::$def_holidays;
		} else {
			$this->holidays = $holidays;
		}
		$this->work_exceptions = $work_exceptions;

		$this->weekends = self::$def_weekends;
	}

	/**
	 * Конструктор
	 *
	 * @param array $holidays массив выходных, если false, то берется из $def_holidays
	 * @param array $work_exceptions массив рабочих дней (исключения)
	 */
	function __construct($holidays = false, $work_exceptions = array())
	{
		$this->BankDay($holidays, $work_exceptions);
	}


	/**
	 * Подготавливает дату для дальнейшей работы
	 *
	 * @param string $date Дата отсчета
	 *
	 * @return timestamp
	 */
	function prepareDate($s)
	{
		if ($s !== null && !is_int($s)) {
			$ts = strtotime($s);
			if ($ts === -1 || $ts === false) {
				// throw new Exception('Unable to parse date/time value from input: ' . var_export($s, true));
				return false;
			}
		} else {
			$ts = $s;
		}
		return $ts;
	}

	/**
	 * Определяет выходной ли день
	 *
	 * @param string $date Дата
	 *
	 * @return boolean
	 */
	function isWeekend($date)
	{
		$ts = $this->prepareDate($date);
		return in_array(date('w', $ts), $this->weekends) && !in_array(date('Y-m-d', $ts), $this->work_exceptions);
	}

	/**
	 * Определяет праздничный ли день
	 *
	 * @param string $date Дата
	 *
	 * @return boolean
	 */
	function isHoliday($date)
	{
		$ts = $this->prepareDate($date);
		return in_array(date('m-d', $ts), $this->holidays);
	}

	/**
	 * Определяет рабочий ли день
	 *
	 * @param string $date Дата
	 *
	 * @return boolean
	 */
	function isWorkDay($date)
	{
		$ts = $this->prepareDate($date);
		$holidays = $this->getHolidays($ts);
		return !in_array(date('Y-m-d', $ts), $holidays);
	}

	/**
	 * Возвращает массив выходных дней с учетом праздников
	 *
	 * @param string  $date Дата отсчета
	 * @param integer $interval Интервал (дней)
	 *
	 * @return array
	 */
	function getHolidays($date, $interval = 30)
	{
		$ts = $this->prepareDate($date);
		$holidays = array();

		for ($i = -$interval; $i <= $interval; $i++) {
			$curr = strtotime($i . ' days', $ts);

			if ($this->isWeekend($curr) || $this->isHoliday($curr)) {
				$holidays[] = date('Y-m-d', $curr);
			}
		}

		// Перенос праздников
		foreach ($holidays as $date) {
			$ts = $this->prepareDate($date);
			if ($this->isHoliday($ts) && $this->isWeekend($ts)) {
				$i = 0;
				while (in_array(date('Y-m-d', strtotime($i . ' days', $ts)), $holidays)) {
					$i++;
				}
				$holidays[] = date('Y-m-d', strtotime($i . ' days', $ts));
			}
		}

		return $holidays;
	}

	/**
	 * Возвращает дату +$days банковских дней
	 *
	 * @param string  $start Дата отсчета
	 * @param integer $days Кол-во банковских дней
	 * @param string  $format Формат date()
	 *
	 * @return integer, string
	 */
	function getEndDate($start, $days, $format = null)
	{
		$ts = $this->prepareDate($start);
		$holidays = $this->getHolidays($start);

		for ($i = 0; $i <= $days; $i++) {
			$curr = strtotime('+' . $i . ' days', $ts);
			if (in_array(date('Y-m-d', $curr), $holidays)) {
				$days++;
			}
		}

		if ($format) {
			return date($format, strtotime('+' . $days . ' days', $ts));
		} else {
			return strtotime('+' . $days . ' days', $ts);
		}
	}

	/**
	 * Возвращает кол-во банковских дней заданном периоде
	 *
	 * @param string $start_in Дата отсчета
	 * @param string $end_in Кол-во банковских дней
	 *
	 * @return integer
	 */
	function getNumDays($start_in, $end_in)
	{
		$start = $this->prepareDate($start_in);
		$end = $this->prepareDate($end_in);

		if ($start > $end) {
			//throw new Exception(sprintf('Start date ("%s") bust be greater then end date ("%s"). ', $start_in, $end_in));
			return false;
		}

		$bank_days = 0;
		$days = ceil(($end - $start) / 3600 / 24);

		$holidays = $this->getHolidays($start, $days);

		for ($i = 0; $i < $days; $i++) {
			$curr = strtotime('+' . $i . ' days', $start);
			if (!in_array(date('Y-m-d', $curr), $holidays)) {
				$bank_days++;
			}
		}

		return $bank_days;
	}
}

?>