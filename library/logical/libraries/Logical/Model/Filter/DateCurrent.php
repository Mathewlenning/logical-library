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
class DateCurrent implements FilterInterface
{
	/**
	 * @var string Name of the field to filter by date
	 */
	protected $dateField = '';

	/**
	 * @var string SQL operator used for the date comparison
	 */
	protected $operator = '>';


    /**
     * @var bool Is the date field a datetime value
     */
	protected $isDateTime = true;

    /**
     * @var string|null
     */
	protected $timeZone = null;
    /**
     * DateRange constructor.
     *
     * @param string $dateField  field to filter from
     * @param string $operator   SQL operator used for the date comparison
     * @param bool   $isDateTime true if the filter field is a datetime data type
     * @param string $timezone   Timezone to use if needed
     */
	public function __construct($dateField, $operator = '>=', $isDateTime = true, $timezone = null)
	{
		$this->setDateField($dateField);

		$this->operator = $operator;
		$this->isDateTime = $isDateTime;
		$this->timeZone = $timezone;
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
	 * @param   Registry         $state  to search for the value
	 * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
	 * @param   JDatabaseQuery   $query  the query to add the filter to
	 *
	 * @return JDatabaseQuery
	 */
	public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
	{
		$today = new JDate('now', $this->timeZone);

		$localize = false;

		if (!empty($this->timeZone))
        {
           $localize = true;
        }

		$dateString = $today->format('Y-m-d', $localize);

        if ($this->isDateTime)
        {
            $timeStamp = ' 00:00:00';

            if ($this->operator != '>')
            {
                $timeStamp = ' 23:59:59';
            }

            $dateString .= $timeStamp;
        }

		$toFilter = new JDate($dateString);

        $dateQuery = ($this->isDateTime)? $toFilter->toSql(): $toFilter->format('Y-m-d');
		$query->where($this->dateField . ' '. $this->operator .' ' . $dbo->quote($dateQuery));
	}
}
