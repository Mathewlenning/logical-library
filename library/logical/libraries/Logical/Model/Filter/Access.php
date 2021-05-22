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
use JFactory;

/**
 * Class Access
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Access implements FilterInterface
{
	/**
	 * @var string Aliased field name (e.g. a.access)
	 */
	protected $accessField;

	/**
	 * Constructor
	 *
	 * @param   string  $fieldName  Aliased field name (e.g. a.access)
	 */
	public function __construct($fieldName)
	{
		$this->accessField = $fieldName;
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
	public function addFilter(Registry $state, JDatabaseDriver $dbo, JDatabaseQuery $query)
	{
		$user = JFactory::getUser();

		if($user->authorise('core.admin'))
		{
			return $query;
		}

		$accessLevels = $user->getAuthorisedViewLevels();
		$query->where($dbo->qn($this->accessField) . ' IN (' . implode(',', $accessLevels) . ')');

		return $query;
	}
}
