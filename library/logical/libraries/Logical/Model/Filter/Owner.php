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
 * Class Search
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Owner implements FilterInterface
{
	/**
	 * Array of prefixed field names to search
	 * @var array
	 */
	protected $ownerField;

	/**
	 * Should we should guests records where ownerField = 0?
	 * @var bool
	 */
	protected $showGuest = false;

	/**
	 * Constructor
	 *
	 * @param   array  $fieldName  aliased field name
	 */
	public function __construct($fieldName, $showGuest = false)
	{
		$this->ownerField = $fieldName;
	}

	/**
	 * This method appends WHERE $fieldName = JFactory::getUser()->id to the query
	 *
	 * @param   Registry         $state  to search for the value
	 * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
	 * @param   JDatabaseQuery   $query  the query to add the filter to
	 *
	 * @return JDatabaseQuery
	 */
	public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
	{
		$user = \JFactory::getUser();

		$query->where($dbo->qn($this->ownerField) . ' = ' . (int) $user->id);

		if ($user->guest && !$this->showGuest)
		{
			$query->where($dbo->qn($this->ownerField) . ' != ' . 0);
		}

		return $query;
	}
}
