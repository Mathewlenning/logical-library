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
use JString;

/**
 * Class Status
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Status implements FilterInterface
{
	/**
	 * @var string Aliased field name (e.g. a.published_status)
	 */
	protected $statusField;

	/**
	 * @var string Status that should be shown to everyone
	 */
	protected $publishedStatus;

	/**
	 * Constructor
	 *
	 * @param string $fieldName       Aliased field name (e.g. a.published_status)
	 * @param string $publishedStatus Status that should be shown to everyone
	 */
	public function __construct($fieldName, $publishedStatus = 'PUBLISHED')
	{
		$this->statusField = $fieldName;
		$this->publishedStatus = $publishedStatus;
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
		//@todo add ability to set different access permission for different status values

		$user = JFactory::getUser();
		$app = JFactory::getApplication();

		if($user->authorise('core.manage') || !$app->isClient('site'))
		{
			return $query;
		}

		$query->where($dbo->qn($this->statusField) . ' = ' . $dbo->q($this->publishedStatus));

		return $query;
	}
}
