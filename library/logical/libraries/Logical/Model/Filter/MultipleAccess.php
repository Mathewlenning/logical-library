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
 * Class MultipleAccess
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class MultipleAccess implements FilterInterface
{
	/**
	 * @var string Aliased id field name (e.g. a.access)
	 */
	protected $primaryKeyField;

	/**
	 * @var string Unaliased access id intersect name (e.g. access_id)
	 */
	protected $accessFieldName;

	/**
	 * @var string Intersecting table name  (e.g. #__access_intersect)
	 */
	protected $accessIntersectTable;

	/**
	 * Constructor
	 *
	 * @param   string $primaryKeyField      Aliased id field name (e.g. a.access)
	 * @param   string $accessFieldName      Unaliased access id intersect name (e.g. access_id)
	 * @param   string $accessIntersectTable Intersect table name  (e.g. #__access_intersect)
	 */
	public function __construct($primaryKeyField, $accessFieldName, $accessIntersectTable)
	{
		$this->primaryKeyField = $primaryKeyField;
		$this->accessFieldName = $accessFieldName;
		$this->accessIntersectTable = $accessIntersectTable;
	}

	/**
	 * This method filter by current user access levels over a many to many relationship
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

		if($user->authorise('core.admin'))
		{
			return $query;
		}

		$accessLevels = $user->getAuthorisedViewLevels();

		list($alias, $key) = explode('.', $this->primaryKeyField);

		$subQuery = $dbo->getQuery(true);
		$subQuery->select($key)
			->from($this->accessIntersectTable)
			->where($this->accessFieldName . ' IN (' . implode(',', $accessLevels) . ')');

		$query->where($dbo->qn($this->primaryKeyField) . ' IN (' . $subQuery . ')');

		return $query;
	}
}
