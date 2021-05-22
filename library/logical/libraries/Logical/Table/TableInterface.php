<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Table;

// No direct access
defined('_JEXEC') or die;

use ErrorException, InvalidArgumentException, UnexpectedValueException;
use JDatabaseDriver, JDatabaseQuery;
use Joomla\Registry\Registry;

/**
 * Interface TableInterface
 *
 * @package  Logical\Table
 * @since    0.0.1
 */
interface TableInterface
{
	/**
	 * Method to get the JDatabaseDriver object.
	 *
	 * @return  JDatabaseDriver  The internal database driver object.
	 */
	public function getDbo();

	/**
	 * Get the columns from database table.
	 *
	 * @return  array  An array of the field names.
	 *
	 * @throws  UnexpectedValueException
	 */
	public function getFields();

	/**
	 * Method to get the primary key field name for the table.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return  string  The name of the primary key for the table.
	 */
	public function getKeyName($raw);

	/**
	 * Method to get the name of the table field storing published state information
	 *
	 * @return string
	 */
	public function getStateField();

	/**
	 * Method to get a list of available record states from the table
	 *
	 * @return array
	 *
	 * @throws ErrorException
	 */
	public function getAvailableStates();

	/**
	 * Method to get the name of the table field storing ordering information
	 *
	 * @return string
	 */
	public function getOrderingField();

	/**
	 * Method to get the name of the table field storing owner user ID
	 *
	 * @return string
	 */
	public function getOwnerField();

	/**
	 * Method to check if the current table supports the ordering field
	 *
	 * @return bool
	 */
	public function supportsOrdering();

	/**
	 * Method to check if this table supports record locking
	 *
	 * @return bool
	 */
	public function supportsCheckout();

	/**
	 * Method to get the name of the check out field
	 *
	 * @return mixed  string or null if the value isn't set
	 */
	public function getCheckedOutField();

	/**
	 * Method to get an SQL WHERE clause to order by
	 * This method is intended to be overridden by children classes if needed
	 *
	 * @param   array|Registry  $src    The record data
	 * @param   JDatabaseQuery  $query  to add the condition to
	 *
	 * @return string
	 */
	public function getReorderConditions($src, $query = null);

	/**
	 * Method to create a row in the database from the JTable instance properties.
	 * If primary key values are set they will be ignored.
	 *
	 * @param   array|object  $src     the data to bind before update
	 * @param   array         $ignore  An optional array properties to ignore while binding.
	 *
	 * @return  mixed  Record Key on success or null
	 */
	public function create($src, $ignore = array());

	/**
	 * Method to perform sanity checks on the src data to ensure
	 * they are safe to store in the database. Child classes should chain this
	 * method to make sure the data they are storing in the database is safe and
	 * as expected before storage.
	 *
	 * @param   array|object  $src     the data to bind before update
	 * @param   array         $ignore  An optional array properties to ignore while binding.
	 *
	 * @return array
	 */
	public function check($src, $ignore = array());


	/**
	 * Method to update a row in the database from the JTable instance properties.
	 * If primary key values are not set this method will call the create method
	 *
	 * @param   array|object  $src     the data to bind before update
	 * @param   array         $ignore  An optional array properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 */
	public function update($src, $ignore = array());


	/**
	 * Method to delete a record by primary key
	 *
	 * @param   int  $pk  The primary key of the record to delete,
	 *
	 * @throws ErrorException
	 * @throws InvalidArgumentException
	 *
	 * @return  boolean  True on success.
	 */
	public function delete($pk);

	/**
	 * Method to load a row from the database by primary key
	 *
	 * @param   int    $pk      The primary key of the record to load,
	 * @param   array  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @throws ErrorException if no record is found
	 * @throws InvalidArgumentException if $pk is empty
	 *
	 * @return  object The row if found.
	 */
	public function load($pk, $ignore = array());

	/**
	 * Method to get a base select query pre-populated with table field names
	 *
	 * @param   JDatabaseQuery $query  Query object
	 * @param   string  $alias   Optional table alias to use in the query
	 * @param   array   $ignore  An optional array of fields to remove from the result.
	 *
	 * @return JDatabaseQuery
	 */
	public function getSelectQuery($query = null, $alias = null,  $ignore = array());
}
