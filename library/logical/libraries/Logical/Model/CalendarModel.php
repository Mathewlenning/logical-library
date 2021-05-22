<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Model;

// No direct access
defined('_JEXEC') or die;

use \InvalidArgumentException;
use \ErrorException;
use \Exception;
use Logical\Registry\Registry;
use Logical\Table\TableInterface;

use Joomla\CMS\Date\Date;


/**
 * Class  CalendarModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.345
 */
abstract class CalendarModel extends RecordModel
{
	/**
	 * Method to return the model that provides event records
	 *
	 * @param   bool  $ignoreRequest should the model ignore the request?
	 *
	 * @return RecordModel
	 */
	abstract public function getEventsModel($ignoreRequest = true);

	/**
	 * Method to return the name of the date field that will be used when building the calendar
	 *
	 * @return string
	 */
	abstract protected function getDateKeyName();

	/**
	 * Method to prepare the model for further use
	 *
	 * @param  RecordModel $eventModel
	 *
	 * @return RecordModel
	 */
	protected function prepareEventModel($eventModel)
	{
		return $eventModel;
	}

	protected function getSortingKey()
	{
		$model = $this->getEventsModel();
		return $model->getKeyName();
	}

	/**
	 * Method to authorise the current user for an action.
	 * This method is intended to be overridden to allow for customized access rights
	 *
	 * @param string $action      ACL action string. I.E. 'core.create'
	 * @param string $assetName   Asset name to check against.
	 * @param int    $pk          Primary key to check against
	 * @param int    $impersonate User id to impersonate, null to use the current user
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 * @see JUser::authorise
	 */
	public function allowAction($action, $assetName = null, $pk = null, $impersonate = null)
	{
		$model = $this->getEventsModel();
		return $model->allowAction($action, $assetName, $pk, $impersonate);
	}


	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string $prefix The class prefix. Optional.
	 * @param   string $name   The table name. Optional.
	 * @param   array  $config Configuration array for model. Optional.
	 *
	 * @return  TableInterface a table object
	 *
	 * @throws \ErrorException
	 */
	public function getTable($prefix = null, $name = null, $config = array())
	{
		$model = $this->getEventsModel();
		return $model->getTable($prefix, $name, $config);
	}

	/**
	 * Method to get a default table name
	 *
	 * Default Format: strtolower('#__'.substr($config['option'], 4).'_'.$config['resource'])
	 * This is intended to be overridden, if you use a different naming system
	 *
	 * @param   array  $config  Configuration
	 *
	 * @return string
	 */
	public function getTableName($config = array())
	{
		$model = $this->getEventsModel();
		return $model->getTableName($config);
	}

	/**
	 * Method to pre-populate the model state
	 * This does not populate the event model state.
	 * filter.year is set to current year if one is not provided
	 *
	 *
	 * @param   string $ordering  Field name to order by
	 * @param   string $direction Direction to order by (ASC, DESC)
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$context = $this->getContext();
		$this->setUserState($context.'.filter.month', null);
		$this->setUserState($context.'.filter.day', null);

		parent::populateState($ordering, $direction);

		$now = new Date();
		$year = $this->getState('filter.year');

		if (empty($year))
		{
			$year = $now->format('Y');
			$this->setState('filter.year', $year);
		}

		$month = $this->getState('filter.month');

		if ($month >= 13)
		{
			$this->setUserState($context.'.filter.month', null);
			$this->setUserState($context.'.filter.day', null);
			throw new InvalidArgumentException(\JText::_('LOGICAL_CALENDAR_MODEL_ERROR_INVALID_MONTH'));
		}

		if (empty($month))
		{
			$this->setUserState($context.'.filter.month', null);
			$this->setUserState($context.'.filter.day', null);

			return;
		}

		$day = $this->getState('filter.day');

		$dayDate = new Date($year .'-' . $month . '-01 00:00:00');


		if($day > $dayDate->format('t'))
		{
			$this->setUserState($context.'.filter.day', null);
			$this->setState('filter.day', null);
		}
	}


	/**
	 * Method to get an array of data items.
	 *
	 * @param   string  $key            the name of a field on which to key the result array.
	 * @param   string  $class          the class name of the return item default is Logical\Registry\Registry
	 * @param   bool    $appendFilters  if set to false, will only return the main query.
	 * @param   array   $ignore         An optional array of fields to remove from the result.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 */
	public function getList($key = null, $class = 'Logical\Registry\Registry', $appendFilters = true, $ignore = array())
	{
		$result = $this->getEventCalendar();

		if (empty($result['months']))
		{
			$result = array('months' => $result, 'max' => $result['max']);
		}

		return $result;
	}

	public function getEventCalendar($year= null, $month = null, $toDay = null)
	{
		if(empty($year))
		{
			$year = $this->getState('filter.year');
		}

		if (empty($month))
		{
			$month = $this->getState('filter.month');
		}

		if (empty($month))
		{
			return $this->getEventCalendarYear($year);
		}

		if (empty($toDay))
		{
			$toDay = $this->getState('filter.day');
		}

		$calendar = \Logical\Utility\Calendar::getInstance($year.'-'.$month);
		$days = $calendar->getCalendarDays();

		$from = $calendar->getFirstCalendarDay();
		$to = $calendar->getLastCalendarDay();

		$eventDateModel = $this->getEventsModel();
		$eventDateModel->setState('filter.from', $from->format('Y-m-d'));
		$eventDateModel->setState('filter.to', $to->format('Y-m-d'));
		$eventDateModel = $this->prepareEventModel($eventDateModel);

		$eventDates = $eventDateModel->getList();
		$dateKey = $this->getDateKeyName();
		$sortingKey = $this->getSortingKey();

		$events = array();
		$maxTr = 5;
		$dateDay = null;

		foreach ($days AS $index => &$day)
		{
			$day['events'] = array();

			foreach ($eventDates AS $key => $date)
			{
				if ($date->{$dateKey} != $day['date'])
				{
					continue;
				}

				if (empty($events[$date->{$sortingKey}]))
				{
					$events[$date->{$sortingKey}] = array();
					$events[$date->{$sortingKey}]['current_lead'] = null;
					$date->first_day = true;
				}

				if(empty($events[$date->{$sortingKey}][$date->{$dateKey}]))
				{
					$events[$date->{$sortingKey}][$date->{$dateKey}] = array();
				}

				$events[$date->{$sortingKey}][$date->{$dateKey}] = $date;
				$dateTime = new Date($date->{$dateKey});

				$date->skip = false;
				//Check if we have one directly before this date
				$dateTime->modify('-1 day');

				if (!empty($events[$date->{$sortingKey}][$dateTime->format('Y-m-d')]) && $dateTime->format('w') != 6)
				{
					$date->skip = true;

					if (!empty($events[$date->{$sortingKey}]['current_lead']))
					{
						$events[$date->{$sortingKey}]['current_lead']->span++;
					}
				}

				if (!$date->skip)
				{
					$events[$date->{$sortingKey}]['current_lead'] = $date;
					$date->span = 1;
				}

				$day['events'][] = $date;
				unset($eventDates[$key]);
			}

			if(!empty($toDay))
			{
				$currentDate = new Date($year .'-' . $month . '-' . $toDay);

				if($day['date'] == $currentDate->format('Y-m-d'))
				{
					$dateDay = $day;
				}
			}


			$eventCount = count($day['events']);

			if($eventCount > $maxTr)
			{
				$maxTr = $eventCount;
			}
		}

		$result = array('weeks' => array_chunk($days, 7), 'max' => $maxTr, 'month' => $calendar, 'day' => $dateDay);

		return $result;
	}

	/**
	 * Method to load an entire year of events
	 *
	 * @param $year
	 *
	 * @return array
	 *
	 * @throws Exception
	 */
	protected function getEventCalendarYear($year)
	{
		$calendarMonths = \Logical\Utility\Calendar::getCalandarYear($year);

		$months = array();

		$maxTr = 5;

		foreach ($calendarMonths AS $month)
		{
			$firstDay = $month->getFirstDay();

			$results = $this->getEventCalendar($year, $firstDay->format('m'));

			$months[] = $results;

			if($results['max'] > $maxTr)
			{
				$maxTr = $results['max'];
			}
		}

		$return = array('months' => $months, 'max' => $maxTr);

		return $return;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk     The id of the primary key.
	 * @param   string   $class  The class name of the return item default is Logical\Registry\Registry
	 *
	 * @throws ErrorException
	 * @return  object  instance of $class.
	 */
	public function getItem($pk = null, $class = 'Logical\Registry\Registry')
	{
		$model = $this->prepareEventModel($this->getEventsModel(false));
		return $model->getItem($pk, $class);
	}

	/**
	 * Method to validate data and insert into db
	 *
	 * @param   array $src    Form data to be inserted
	 * @param   array $ignore An optional array properties to ignore while binding.
	 *
	 * @return int primary key of the created record
	 *
	 * @since 0.0.1
	 * @throws ErrorException
	 */
	public function create($src, $ignore = array())
	{
		$model = $this->getEventsModel();
		return $model->create($src, $ignore);
	}

	/**
	 * Method to validate data and update into db
	 *
	 * @param   array  $src     Data to be used to update the record
	 * @param   array  $ignore  An optional array properties to ignore while binding.
	 *
	 * @throws ErrorException
	 *
	 * @return Registry
	 *
	 * @since 0.0.1
	 */
	public function update($src, $ignore = array())
	{
		$model = $this->getEventsModel();
		return $model->update($src, $ignore);
	}


	/**
	 * Method to delete one or more records.
	 *
	 * @param   int  $id  record primary keys.
	 *
	 * @throws ErrorException
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since    0.0.1
	 */
	public function delete($id)
	{
		$model = $this->getEventsModel();
		return $model->delete($id);
	}

	/**
	 * Method to delete records that use compound keys
	 *
	 * @param  array  $src
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function deleteCompoundPk($src)
	{
		$model = $this->getEventsModel();
		return $model->deleteCompoundPk($src);
	}

	/**
	 * Convenience Method to get the JForm object with the JForm control value
	 *
	 * @param   string  $name    The name of the form.
	 * @param   string  $source  The form file name.
	 * @param   array   $config  Configuration
	 *
	 * @return bool|\JForm
	 */
	public function getForm($name = null, $source = null, $config = array())
	{
		$model = $this->prepareEventModel($this->getEventsModel());
		return $model->getForm($name, $source, $config);
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param  \JForm  $form  The form to validate against.
	 * @param   array  $src   The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @throws ErrorException
	 * @return  mixed  Array of filtered data if valid
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 */
	public function validate($form, $src, $group = null)
	{
		$model = $this->getEventsModel();
		return $model-$this->validate($form, $src, $group);
	}

}
