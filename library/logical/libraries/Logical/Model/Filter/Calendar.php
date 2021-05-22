<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Model\Filter;

// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Date\Date;
use Logical\Registry\Registry;
use JDatabaseDriver;
use JDatabaseQuery;

use JDate;

/**
 * Class DateRange
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Calendar implements FilterInterface
{
	/**
	 * Name of the field to filter by date range
	 * @var string
	 */
	protected $dateField = '';

	/**
	 * DateRange constructor.
	 *
	 * @param   string  $dateField  field to filter from
	 */
	public function __construct($dateField)
	{
		$this->setDateField($dateField);
	}

	/**
	 * Method to set the field name used when setting adding the filter
	 *
	 * @param   string  $fieldName  alias prefixed field name I.E. a.field_name
	 *
	 * @return void
	 */
	public function setDateField($fieldName)
	{
		$this->dateField = $fieldName;
	}


	/**
	 * This method appends WHERE $datefield BETWEEN  $fromDate AND $toDate to the query
	 * From and to dates are stored in filter.from and filter.to respectively
	 *
	 * @param   Registry        $state to search for the value
	 * @param   JDatabaseDriver $dbo   database object to use for quoting/escaping
	 * @param   JDatabaseQuery  $query the query to add the filter to
	 *
	 * @return JDatabaseQuery
	 * @throws \Exception
	 */
	public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
	{
		$yearFilter = $state->get('filter.year', null);

		if (empty($yearFilter))
		{
			return $query;
		}

		$year = \Logical\Utility\Calendar::getCalandarYear($yearFilter . '-01-01');

		$fromDate = $year[1]->getFirstCalendarDay();
		$toDate = $year[12]->getLastCalendarDay();

		$monthFilter = $state->get('filter.month', null);

		if (!empty($monthFilter))
		{
			$monthFilter = (int) $monthFilter;

			$fromDate = $year[$monthFilter]->getFirstCalendarDay();
			$toDate = $year[$monthFilter]->getLastCalendarDay();
		}

		$query->where($this->dateField . ' BETWEEN ' . $dbo->quote($fromDate->format('Y-m-d') . ' 00:00:00') . ' AND ' . $dbo->quote($toDate->format('Y-m-d') . ' 23:59:59'));
	}
}
