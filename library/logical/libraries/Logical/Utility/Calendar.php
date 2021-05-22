<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Utility;


// No direct access
defined('_JEXEC') or die;

use \DateTime as Date;


class Calendar
{
	/**
	 * @var array Array of calendars (e.g. $calendars[$year][$month] = Calendar)
	 */
	protected static $calendars = array();

	/**
	 * @var Date|null First day of the month
	 */
	protected $firstDay = null;

	/**
	 * @var Date|null Last day of the month
	 */
	protected $lastDay = null;

	/**
	 * @var Date|null First day of the calendar
	 */
	protected $firstCalendarDay = null;

	/**
	 * @var Date|null
	 */
	protected $lastCalendarDay = null;

	/**
	 * @var array of calendar days
	 */
	protected $calender = array();

	/**
	 * @var array of calendar days split into 7 day week arrays
	 */
	protected $weeks = array();

	public function __construct($month, $year)
	{
		$this->firstDay = new Date($year.'-'. $month .'-01');
		$this->lastDay = new Date($year.'-'. $month .'-'. $this->firstDay->format('t'));

		$firstCalendarDay = new  Date($this->firstDay->format('Y-m-d H:i:s'));
		$firstCalendarDay->modify($this->firstDay->format('w') . ' days ago');

		$this->firstCalendarDay = $firstCalendarDay;

		$lastCalendarDay = new  Date($firstCalendarDay->format('Y-m-d H:i:s'));
		$lastCalendarDay->modify('41 days');
		$this->lastCalendarDay = $lastCalendarDay;

	}

	/**
	 * Method to get first day of the calendar month
	 *
	 * @return Date
	 */
	public function getFirstDay()
	{
		return $this->firstDay;
	}

	/**
	 * Method to get the last day of the calendar month
	 *
	 * @return Date
	 */
	public function getLastDay()
	{
		return $this->lastDay;
	}

	/**
	 * Method to get the first day of the calendar (this is based on a 7x6 calendar grid)
	 *
	 * @return Date
	 */
	public function getFirstCalendarDay()
	{
		return $this->firstCalendarDay;
	}

	/**
	 * Method to get the last day of the calendar (this is based on a 7x6 calendar grid)
	 *
	 * @return Date
	 */
	public function getLastCalendarDay()
	{
		return $this->lastCalendarDay;
	}

	/**
	 * @param Date|string $date either a date object or a time string
	 *
	 * @return Calendar
	 * @throws \Exception
	 */
	public static function getInstance($date)
	{
		if (is_string($date))
		{
			$date = new Date($date);
		}

		$year = $date->format('Y');
		$month = $date->format('m');

		if (!empty(static::$calendars[$year][$month]))
		{
			return static::$calendars[$year][$month];
		}

		static::$calendars[$year][$month] = new Calendar($date->format('m'), $date->format('Y'));

		return static::$calendars[$year][$month];
	}

	/**
	 * Method to get an entire calendar year by date
	 *
	 * @param   Date|String $date either a date object or a time string
	 *
	 * @return  array of calendar objects
	 *
	 * @throws \Exception
	 */
	public static function getCalandarYear($date)
	{
		if (is_string($date))
		{
			$date = new Date($date);
		}

		$firstMonth = new Date($date->format('Y') .'-01-01');

		$year = array();
		$i = 0;

		while ($i != 12)
		{
			$year[$firstMonth->format('n')] = static::getInstance($firstMonth);

			$firstMonth->modify('1 month');
			$i++;
		}

		return $year;
	}


	/**
	 * Get an array of calendar days
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function getCalendarDays()
	{
		if(!empty($this->calender))
		{
			return $this->calender;
		}

		$calendar = array();
		$firstCalDay = new Date($this->firstCalendarDay->format('Y-m-d'));
		$today = new Date();

		$i = 0;

		while ($i != 42)
		{
			$day = array();
			$day['day'] = $firstCalDay->format('j');
			$day['month'] = $firstCalDay->format('m');
			$day['year'] = $firstCalDay->format('Y');
			$day['date'] = $firstCalDay->format('Y-m-d');
			$day['D'] = $firstCalDay->format('D');
			$day['current_month'] = ($day['month'] === $this->firstDay->format('m'))? 'YES': 'NO';
			$day['is_today'] = ($firstCalDay->format('Y-m-d') === $today->format('Y-m-d')) ? 'YES' : 'NO';

			$calendar[] = $day;
			$firstCalDay->modify('1 day');
			$i++;
		}

		$this->calender = $calendar;

		return $this->calender;
	}

	/**
	 * Method to get an array of arrays containing calendar days divided by weeks
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function getCalendarWeeks()
	{
		if (!empty($this->weeks))
		{
			return $this->weeks;
		}

		$calendar = $this->getCalendarDays();
		$this->weeks = array_chunk($calendar, 7);

		return $this->weeks;
	}

	/**
	 * Method to get an array of 7 calendar days sunday thru saturday by week index
	 *
	 * @param   int   $index a number between 0 and 5
	 *
	 * @return mixed
	 *
	 * @throws \Exception
	 */
	public function getWeekByIndex($index = 0)
	{
		if($index >= 6)
		{
			throw new \InvalidArgumentException('Calendar week index must be between 0 and 5');
		}

		if (empty($this->weeks))
		{
			$this->getCalendarWeeks();
		}


		return $this->weeks[$index];
	}

	/**
	 * Method to get an array of 7 calendar days sunday thru saturday by date
	 *
	 * @param  Date|string   $date
	 *
	 * @return array
	 *
	 * @throws \Exception
	 */
	public function getWeekByDate($date)
	{
		if (is_string($date))
		{
			$date = new Date($date);
		}

		if ($date->format('Y-m') !== $this->firstDay->format('Y-m'))
		{
			$newCalendar = self::getInstanceByDate($date);
			return $newCalendar->getWeekByDate($date);
		}

		return $this->getWeekByIndex($this->getWeekIndexByDate($date));
	}

	/**
	 * Method to get an array of 7 calendar days sunday thru saturday by date
	 *
	 * @param  Date|string $date
	 *
	 * @return int
	 *
	 * @throws \Exception
	 */
	public function getWeekIndexByDate($date)
	{
		if (is_string($date))
		{
			$date = new Date($date);
		}

		$firstWeek = (int) $this->firstDay->format('W');
		$dateWeek = (int) $date->format('W');

		if($date->format('D') == 'Sun')
		{
			$dateWeek++;
		}

		return $dateWeek - $firstWeek;
	}
}
