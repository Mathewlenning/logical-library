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
use JString;

/**
 * Class GroupBy
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class GroupBy implements FilterInterface
{
	/**
	 * @var array of aliased field names (e.g. array[a.record_id, a.organization_id])
	 */
	protected $acceptableFields = array();

	/**
	 * Constructor
	 *
	 * @param   array  $fieldNames  of aliased field names (e.g. array[a.record_id, a.organization_id])
	 */
	public function __construct($fieldNames)
	{
		$this->acceptableFields = $fieldNames;
	}

	/**
	 * This method filter by current user access levels
	 *
	 * @param   Registry         $state  to search for the value
	 * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
	 * @param   JDatabaseQuery   $query  the query to add the filter to
	 *
	 * @return JDatabaseQuery
	 */
	public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
	{
		$groupByFilter = $state->get('filter.groupby');

		if(empty($groupByFilter) || !in_array($groupByFilter, $this->acceptableFields))
		{
			return $query;
		}

		$query->group($groupByFilter);

		return $query;
	}
}
