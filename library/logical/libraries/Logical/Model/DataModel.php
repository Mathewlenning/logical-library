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

use Logical\Table\Table;
use Logical\Table\TableInterface;
use Logical\Registry\Registry;

use ErrorException;
use InvalidArgumentException;
use JDatabaseDriver;
use JDatabaseQuery;
use JFactory, JText, JDate;
use stdClass;

/**
 * Class DataModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.1
 */
abstract class DataModel extends BaseModel
{
	/**
	 * Method to get the database driver object
	 *
	 * @return  JDatabaseDriver
	 */
	public function getDbo()
	{
		return JFactory::getDbo();
	}

	/**
	 * Method to get the name of the primary key from table
	 *
	 * @param   string $tablePrefix Prefix being used
	 * @param   string $tableName   Name of the table
	 * @param   array  $config      Table configuration
	 *
	 * @return string
	 * @throws ErrorException
	 */
	public function getKeyName($tablePrefix = null, $tableName = null, $config = array(), $raw = false)
	{
		$table = $this->getTable($tablePrefix, $tableName, $config);

		return $table->getKeyName($raw);
	}

	/**
	 * Method to get the name of the state field
	 *
	 * @param   string $tablePrefix Prefix being used
	 * @param   string $tableName   Name of the table
	 * @param   array  $config      Table configuration
	 *
	 * @return string
	 * @throws ErrorException
	 */
	public function getStateField($tablePrefix = null, $tableName = null, $config = array())
	{
		$table = $this->getTable($tablePrefix, $tableName, $config);

		return $table->getStateField();
	}

	/**
	 * Method to lock a record for editing
	 *
	 * @param   int  $pk  primary key of record
	 *
	 * @throws InvalidArgumentException
	 * @throws ErrorException
	 *
	 * @return bool
	 */
	public function checkout($pk)
	{
		$table = $this->getTable();

		if (!$table->supportsCheckout())
		{
			return true;
		}

		$record = $table->load($pk);

		if ($this->isLocked($table, $record))
		{
			$msg = JText::_('LOGICAL_MODEL_ERROR_CHECKED_OUT_USER_MISMATCH');
			throw new ErrorException($msg);
		}

		$userId = JFactory::getUser()->id;
		$now = new JDate;
		$key = $table->getKeyName();
		$checkOutField = $table->getCheckedOutField();

		$table->update(array($key => (int) $pk, $checkOutField => $userId, $checkOutField . '_time' => $now->toSql()));

		return true;
	}

	/**
	 * Method to check if a record is locked
	 *
	 * @param   TableInterface  $table   the table instance to check
	 * @param   stdClass        $record  the table instance to check
	 *
	 * @return boolean
	 */
	protected function isLocked($table, $record)
	{
		$checkOutField = $table->getCheckedOutField();
		$checkedOut = $record->{$checkOutField};
		$notLocked = ($checkedOut < 0);

		if (!$notLocked)
		{
			return false;
		}

		$user = JFactory::getUser();
		$isCurrentEditor = ($checkedOut == $user->get('id'));

		if ($isCurrentEditor)
		{
			return false;
		}

		return $this->allowAction('core.admin');
	}

	/**
	 * Method to unlock a record
	 *
	 * @param   int $pk primary key
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function checkin($pk)
	{
		if (empty($pk))
		{
			return true;
		}

		// Get an instance of the row to checkout.
		$table = $this->getTable();

		if (!$table->supportsCheckout())
		{
			return true;
		}

		$record = $table->load($pk);

		if (!$this->isLocked($table, $record))
		{
			$userId = 'NULL';
			$nullDate = $table->getDbo()->getNullDate();
			$key = $table->getKeyName();
			$checkOutField = $table->getCheckedOutField();

			$table->update(array($key => (int) $pk, $checkOutField => $userId, $checkOutField . '_time' => $nullDate));
		}

		return true;
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
	 * @throws ErrorException
	 */
	public function getTable($prefix = null, $name = null, $config = array())
	{
		// Make sure we have all the configuration vars
		$modelConfig = $this->config->toArray();

		$config += $modelConfig;

		if (empty($prefix))
		{
			$prefix = ucfirst(substr($config['option'], 4));
		}

		if (empty($name))
		{
			$name = ucfirst($config['resource']);
		}

		// Create it if it does not already exist
		return $this->createTable($prefix, $name, new Registry($config));
	}

	/**
	 * Method to load and return a model object.
	 *
	 * @param   string    $prefix  The class prefix. Optional.
	 * @param   string    $name    The name of the view
	 * @param   Registry  $config  Configuration settings to pass to JTable::getInstance
	 *
	 * @throws ErrorException
	 * @return  TableInterface   A table object
	 */
	protected function createTable($prefix, $name, $config = null)
	{
		// Clean the model name
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);

		$dbo = $config->get('dbo');

		// Make sure we are returning a DBO object
		if (empty($dbo))
		{
			$config->set('dbo', $this->getDbo());
		}

		$tableName = $config->get('table.name');

		if (empty($tableName))
		{
			$config->set('table.name', $this->getTableName($config->toArray()));
		}

		$className = $prefix . 'Table' . $name;

		if (!class_exists($className))
		{
			$msg = JText::_('LOGICAL_MODEL_ERROR_TABLE_NAME_NOT_SUPPORTED');
			throw new ErrorException($msg . ': ' . $className);
		}

		return new $className($config);
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
		// Make sure we have all the configuration vars
		$modelConfig = $this->config->toArray();

		$config += $modelConfig;

		$prefix = '#__' . substr($config['option'], 4);

		$postfix = '_' . $config['resource'];

		return strtolower($prefix . $postfix);
	}

    /**
     * Method to get a filtered list of fields from the table
     *
     * @param   array  $ignore   an array of field names to ignore
     * @param   bool   $fullData should we send the full data about the field?
     * @param   string $typeOf   field type to filter for e.g. enum, text, varchar, int
     *
     * @return array
     * @throws ErrorException
     */
    public function getFields($ignore = array(), $fullData = false, $typeOf = null, $prefix = '')
    {
        $table = $this->getTable();
        $fields = $table->getFields();

        $returnFields = array();

        foreach ($fields as $name => $value)
        {
            if (in_array($name, $ignore) || !$this->isTypeOf($value, $typeOf))
            {
                continue;
            }

            if(!empty($prefix))
            {
                $name = $prefix .'.'. $name;
            }

            if ($fullData)
            {
                $returnFields[$name] = $value;

                continue;
            }

            $returnFields[] = $name;
        }

        return $returnFields;
    }

	/**
	 * Method to check if a field is the type specified in the params
	 *
	 * @param   stdClass   $fieldData  database field definition
	 * @param   string     $typeOf     type to check for e.g. enum, text, varchar, int
	 * @return bool
	 */
	protected function isTypeOf($fieldData, $typeOf = null)
	{
		if (empty($typeOf) || strpos($fieldData->Type, $typeOf) !== false)
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to add a left joint to the users table for the record owners name
	 *
	 * @param   JDatabaseQuery $query      Query object
	 * @param   string         $onField    The prefixed field name to join on
	 * @param string           $fieldAlias Alias to use for DB and return name value
	 *
	 * @deprecated use Logical\Table\Table::joinUserName instead
	 *
	 * @return \JDatabaseQuery
	 * @throws ErrorException
	 */
	protected function joinUserName(JDatabaseQuery $query, $onField = 'a.user_id', $fieldAlias = "user_name")
	{
		/** @var Table $table */
		$table = $this->getTable();
		return $table->joinUserName($query, $onField, $fieldAlias);
	}

	/**
	 * Method to add a left join to the viewlevels table for the assess title
	 *
	 * @param   JDatabaseQuery  $query    Query object
	 * @param   string          $onField  The prefixed field name to join on
	 *
	 * @return JDatabaseQuery
	 */
	protected function addAccessTitle(JDatabaseQuery $query, $onField = 'a.access')
	{
		$query->select('access.title AS access_title');
		$query->join('LEFT', '#__viewlevels AS access ON access.id =' . $onField);

		return $query;
	}
}
