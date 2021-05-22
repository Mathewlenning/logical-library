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

use Joomla\CMS\Date\Date;
use Logical\Registry\Registry;

use UnexpectedValueException;
use InvalidArgumentException;
use ErrorException;
use RuntimeException;
use JDatabaseDriver;
use JDatabaseQuery;
use JFactory;
use JText;

/**
 * Class Table
 *
 * @package  Logical\Table
 * @since    0.0.1
 */
abstract class Table implements TableInterface
{
	/**
	 * @var string
	 */
	protected $option;

	/**
	 * Name of the database table.
	 *
	 * @var    string
	 */
	protected $table;

	/**
	 * Name of the primary key field.
	 *
	 * @var    string
	 */
	protected $primaryKey;

	/**
	 * Values that can be set on creation but not changed on update
	 * @var array
	 */
	protected $immutable = array();

	/**
	 * Name of the alias field
	 *
	 * @var string
	 */
	protected $aliasField = null;

	/**
	 * Name of the asset id field
	 *
	 * @var string
	 */
	protected $assetField = null;

	/**
	 * Name of this asset  Format : component.table.id
	 *
	 * @var string
	 */
	protected $assetName = null;

	/**
	 * Name of the checkout field used for record locking
	 *
	 * var string
	 */
	protected $checkedOutField = null;

	/**
	 * Name of the state field
	 *
	 * @var string
	 */
	protected $stateField = null;

	/**
	 * Name of the ordering field
	 *
	 * @var string
	 */
	protected $orderingField = null;

	/**
	 * Name of the owner field
	 *
	 * @var string
	 */
	protected $ownerField = null;

	/**
	 * Name of the field used to track who created the record
	 *
	 * @var string
	 */
	protected $createdByField = null;

	/**
	 * Name of the field used to track when the record was created
	 *
	 * @var string
	 */
	protected $createdOnField = null;

	/**
	 * Name of the field used to track who last modified the record
	 *
	 * @var string
	 */
	protected $modifiedByField = null;

	/**
	 * Name of the field used to track when the record was last modified
	 *
	 * @var string
	 */
	protected $modifiedOnField = null;

	/**
	 * JDatabaseDriver object.
	 *
	 * @var    JDatabaseDriver
	 */
	protected $dbo;

	/**
	 * Current state of table locking
	 *
	 * @var bool
	 */
	protected $locked = false;

	/**
	 * Constructor
	 *
	 * @param   Registry  $config  configuration
	 */
	public function __construct(Registry $config)
	{
		$this->option = $config->get('option');

		$prefix  = '#__' . substr($this->option, 4);
		$postfix = '_' . $config->get('resource');

		// Set properties
		$this->table = $config->get('table.name', strtolower($prefix . $postfix));
		$this->primaryKey = $config->get('table.key', $config->get('resource') . '_id');
		$this->dbo = $config->get('dbo', JFactory::getDbo());

		// Set common fields
		$this->assetField = $config->get('table.asset', null);
		$this->aliasField = $config->get('table.alias', null);
		$this->checkedOutField = $config->get('table.checked_out', null);
		$this->stateField = $config->get('table.state', null);
		$this->orderingField = $config->get('table.ordering', null);

		$this->assetName = $config->get('option') . '.' . $config->get('resource');

		// Set immutable fields
		$this->immutable = $config->get('table.immutable', array());

		if (!in_array($this->primaryKey, $this->immutable))
		{
			$this->immutable[] = $this->primaryKey;
		}
	}

	/**
	 * Method to get the DBO
	 *
	 * @return JDatabaseDriver
	 */
	public function getDbo()
	{
		if (!($this->dbo instanceof JDatabaseDriver))
		{
			$this->dbo = JFactory::getDbo();
		}

		return $this->dbo;
	}

    /**
     * Method to get the primary key field name for the table.
     *
     * @param bool $raw should we return the array for compound keys
     *
     * @return  string  The name of the primary key for the table.
     */
	public function getKeyName($raw = false)
	{
		if (empty($this->primaryKey))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_EMPTY_PRIMARY_KEY');
			throw new InvalidArgumentException($msg, 500);
		}


		if (is_array($this->primaryKey) && !$raw)
		{
			return implode('-', $this->primaryKey);
		}

		return $this->primaryKey;
	}

	/**
	 * Get the columns from database table.
	 *
	 * @return  array  An array of the field names.
	 *
	 * @throws  UnexpectedValueException
	 */
	public function getFields()
	{
		static $cache = null;

		if ($cache !== null)
		{
			return $cache;
		}

		$prefix  = '#__' . substr($this->option, 4);

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->select('*');
		$query->from($prefix .'_schemas');

		$assetName = $this->getAssetName();
		$query->where('asset_id = ' . $dbo->q($assetName));
		$result = $dbo->setQuery($query)->loadAssoc();

		if (is_null($result))
		{
			$result = $this->createSchema($assetName, $prefix);
		}

		$cachedOn = new \JDate($result['cached_on']);
		$now = new \JDate;

		if ($now->toUnix() > ($cachedOn->toUnix() + 86400))
		{
			$this->updateSchema($assetName, $now, $prefix);
		}

		// Decode the fields
		$fields = (array) json_decode($result['fields']);

		if (empty($fields))
		{
			$msg = JText::sprintf('LOGICAL_TABLE_ERROR_NO_COLUMNS_FOUND', $this->table);

			throw new UnexpectedValueException($msg, 500);
		}

		$cache = $fields;

		return $cache;
	}

	/**
	 * Method to cache the table schema in the logical schemas table
	 *
	 * @param   string  $assetName  the asset name of this table. standard format is "com_componentName.TableName"
	 * @param   string  $prefix     component schemas table prefix
	 *
	 * @return array
	 */
	private function createSchema($assetName, $prefix)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->insert($prefix .'_schemas');
		$query->set('asset_id = ' . $dbo->q($assetName));

		$fields = json_encode($dbo->getTableColumns($this->table, false));
		$query->set('fields = ' . $dbo->q($fields));

		$now = new \JDate;
		$query->set('cached_on = ' . $dbo->q($now->toSql()));

		$dbo->setQuery($query)->execute();

		return array('asset_id' => $assetName, 'fields' => $fields, 'cached_on' => $now->toSql());
	}

	/**
	 * Method to update the table schema in the logical schemas table
	 *
	 * @param   string  $assetName  the asset name of this table. standard format is "com_componentName.TableName"
	 * @param   \JDate  $now        the current time
	 * @param   string  $prefix     component schemas table prefix
	 *
	 * @return array
	 */
	private function updateSchema($assetName, \JDate $now, $prefix)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->update($prefix . '_schemas');

		$fields = json_encode($dbo->getTableColumns($this->table, false));
		$query->set('fields = ' . $dbo->q($fields));
		$query->set('cached_on = ' . $dbo->q($now->toSql()));

		$query->where('asset_id = ' . $dbo->q($assetName));

		$dbo->setQuery($query)->execute();

		return array('asset_id' => $assetName, 'fields' => $fields, 'cached_on' => $now->toSql());
	}

	/**
	 * Method to get the name of the table alias field
	 *
	 * @return string
	 */
	public function getAliasField()
	{
		return $this->aliasField;
	}

	/**
	 * Method to get the name of the field to store the asset id
	 *
	 * @return string
	 */
	public function getAssetField()
	{
		return $this->assetField;
	}

	/**
	 * Method to return the title to use for the asset table.
	 * By default the asset name is used.
	 * A title is kept for each asset so that in the future there is some
	 * context available in a unified access manager.
	 *
	 * @return  string  The string to use as the title in the asset table.
	 */
	public function getAssetTitle()
	{
		return $this->getAssetName();
	}

	/**
	 * Method to compute the default name of the asset.
	 * The default name is in the form option.subject.primaryKeyValue
	 *
	 * @param   Registry  $src  The record data
	 *
	 * @return string
	 */
	public function getAssetName(Registry $src = null)
	{
		$assetName = $this->assetName;

		if (is_null($src))
		{
			return $assetName;
		}

		$pk = $src->get($this->primaryKey);

		if (!empty($pk))
		{
			$assetName .= '.' . (int) $pk;
		}

		return $assetName;
	}

	/**
	 * Method to get the name of the check out field
	 *
	 * @return mixed  string or null if the value isn't set
	 */
	public function getCheckedOutField()
	{
		return $this->checkedOutField;
	}

	/**
	 * Method to get the name of the table field storing published state information
	 *
	 * @return string
	 */
	public function getStateField()
	{
		return $this->stateField;
	}

	/**
	 * Method to get the name of the table field storing ordering information
	 *
	 * @return string
	 */
	public function getOrderingField()
	{
		return $this->orderingField;
	}

	/**
	 * Method to get the name of the table field storing owner user ID
	 *
	 * @return string
	 */
	public function getOwnerField()
	{
		return $this->ownerField;
	}

	/**
	 * Method to check if this table supports record locking
	 *
	 * @return bool
	 */
	public function supportsCheckout()
	{
		return !is_null($this->checkedOutField);
	}

	public function getCreatedByField()
	{
		return $this->createdByField;
	}

	public function getCreatedOnField()
	{
		return $this->createdOnField;
	}

	public function getModifiedByField()
	{
		return $this->modifiedByField;
	}

	public function getModifiedOnField()
	{
		return $this->modifiedOnField;
	}

	/**
	 * Method to check if the current table supports the ordering field
	 *
	 * @return bool
	 */
	public function supportsOrdering()
	{
		if (!is_null($this->orderingField))
		{
			return true;
		}

		return false;
	}

	/**
	 * Method to create a row in the database from the JTable instance properties.
	 * If primary key values are set they will be ignored.
	 *
	 * @param   array|Registry  $src     The record data
	 * @param   array           $ignore  An optional array properties to ignore while binding.
	 *
	 * @throws ErrorException
	 *
	 * @return  mixed  Record Key on success or null
	 */
	public function create($src, $ignore = array())
	{
		$src = $this->check($this->prepareForCreate($src), $ignore);

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->insert($this->table);

		foreach ($src AS $fieldName => $value )
		{
			$this->setField($query, $fieldName, $value);
		}

		$dbo->setQuery($query);

		if (!$dbo->execute())
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_CREATE_FAILED');
			throw new ErrorException($msg, 500);
		}

		// Set the primary key
		return $dbo->insertid();
	}

	/**
	 * Method to convert the $src  to a registry object
	 *
	 * @param   mixed  $src  The record data
	 *
	 * @return Registry
	 */
	protected function normalizeSrc($src)
	{
		if (!($src instanceof Registry))
		{
			$src = new Registry($src);
		}

		return $src;
	}

	/**
	 * Method to prepare the src data for creation of a new record
	 *
	 * @param   Registry  $src  The record data
	 *
	 * @return array sanitised array
	 */
	protected function prepareForCreate($src)
	{
		if (($src instanceof Registry))
		{
			$src = $src->toArray();
		}

		if (!is_array($src))
		{
			$src = (array) $src;
		}

		if(!is_array($this->primaryKey))
		{
			// Make sure there is no primary key set
			$src[$this->primaryKey] = null;
		}

		$currentUser = JFactory::getUser()->id;
		$createdBy = $this->getCreatedByField();

		if (!is_null($createdBy))
		{
			$src[$createdBy] = $currentUser;
		}

		$now = new Date();
		$createdOn = $this->getCreatedOnField();

		if (!empty($createdOn))
		{

			$src[$createdOn] = $now->toSql();
		}

		return $src;
	}

	/**
	 * Method to insure the src data conforms to the extensions requirements.
	 * This method is intended to be overridden by inheriting classes.
	 * Part of this process is filtering out data fields that the table does not support.
	 *
	 * @param   Registry|array $src    Associative array of values to be stored.
	 * @param   array          $ignore List of fields to ignore
	 *
	 * @return Registry
	 *
	 * @throws ErrorException
	 */
	public function check($src, $ignore = array())
	{
		$fields = $this->getFields();
		$output = array();

		foreach ($src AS $fieldName => $value)
		{
			if (array_key_exists($fieldName, $fields) && !in_array($fieldName, $ignore))
			{
				if (is_array($value))
				{
					$value = json_encode($value);
				}

				if ($fields[$fieldName]->Type == 'datetime')
				{
					$date = new \JDate($value);
					$value = $date->toSql();
				}

				$output[$fieldName] = $value;
			}
		}

		$src = $this->normalizeSrc($output);
		$this->adjustOrdering($src);

		$currentUser = JFactory::getUser()->id;
		$now = new Date();

		$modifiedBy = $this->getModifiedByField();

		if(!empty($modifiedBy))
		{
			$src[$modifiedBy] = $currentUser;
		}

		$modifiedOn = $this->getModifiedOnField();

		if(!empty($modifiedOn))
		{
			$src[$modifiedOn] = $now->toSql();
		}

		$availableStates = $this->getAvailableStates();

		if (empty($availableStates) || !isset($output[$this->stateField]))
		{
			return $src;
		}

		if (!in_array($output[$this->stateField], $availableStates))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_INVALID_STATE');
			throw new InvalidArgumentException($msg, 400);
		}

		return  $src;
	}

	/**
	 * Method to adjust the ordering for records
	 *
	 * @param   Registry  $src  The record data
	 *
	 * @return mixed
	 */
	private function adjustOrdering(Registry $src)
	{
		if (!$this->supportsOrdering())
		{
			return $src;
		}

		$pk = $src->get($this->primaryKey);
		$orderField = $this->getOrderingField();
		$ordering = $src->get($orderField);
		$max = $this->getMaxOrdering($src);

		$newWithoutOrdering = (empty($pk) && empty($ordering));

		if ($newWithoutOrdering || $ordering > $max)
		{
			$src->set($orderField, $max + 1);

			return $src;
		}

		if (!$this->orderChanged($pk, $ordering))
		{
			return $src;
		}

		// Then we need to make a space for it
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->update($this->table)
			->set($orderField . ' = ' . $orderField . ' + 1')
			->where($orderField . ' >= ' . $ordering);

		$this->getReorderConditions($src, $query);

		$dbo->setQuery($query)->execute();

		return $src;
	}

	/**
	 * Method to get the max ordering value
	 *
	 * @param   Registry  $src  The record data
	 *
	 * @return mixed
	 */
	private function getMaxOrdering(Registry $src)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$query->select('MAX(' . $this->orderingField . ')')
			->from($dbo->quoteName($this->table));

		$this->getReorderConditions($src, $query);

		return $dbo->setQuery($query)->loadResult();
	}

	/**
	 * Method to get an SQL WHERE clause to order by
	 * This method is intended to be overridden by children classes if needed
	 *
	 * @param   array|Registry  $src    The record data
	 * @param   JDatabaseQuery  $query  to add the condition to
	 *
	 * @return string
	 */
	public function getReorderConditions($src = array(), $query = null)
	{
		return '';
	}

	/**
	 * Method to check if the ordering has change for a record
	 *
	 * @param   int  $pk        Record primary key
	 * @param   int  $ordering  Record current ordering
	 *
	 * @return bool
	 */
	private function orderChanged($pk, $ordering)
	{
		// It is a new record, so of course it has changed
		if (empty($pk))
		{
			return true;
		}

		// If the ordering isn't set, then there has been no change.
		if (empty($ordering))
		{
			return false;
		}

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$query->select($this->orderingField)
			->from($dbo->quoteName($this->table))
			->where($this->primaryKey . ' = ' . (int) $pk)
			->where($this->orderingField . ' = ' . (int) $ordering);
		$dbo->setQuery($query);

		$result = $dbo->loadResult();

		return is_null($result);
	}

	/**
	 * Method to get a list of available record states from the table
	 *
	 * @return array
	 *
	 * @throws ErrorException
	 */
	public function getAvailableStates()
	{
		$stateField = $this->getStateField();

		if (empty($stateField))
		{
			return array();
		}

		$fields = $this->getFields();

		if (!array_key_exists($stateField, $fields))
		{
			throw new ErrorException(JText::_('LOGICAL_TABLE_ERROR_STATE_FIELD_DOES_NOT_EXIST'), 500);
		}

		if (strpos($fields[$stateField]->Type, 'enum') === false)
		{
			throw new ErrorException(JText::_('LOGICAL_TABLE_ERROR_STATE_FIELD_MUST_BE_AN_ENUM_FIELD_TYPE'), 500);
		}

		return $this->getEnumAsArray($fields[$stateField]->Type);
	}

	/**
	 * Method to convert an enum field definition into an array of possible values
	 *
	 * @param   string  $fieldType  a field type definition
	 *
	 * @return array
	 */
	public function getEnumAsArray($fieldType)
	{
		if (strpos($fieldType, 'enum') === false)
		{
			throw new InvalidArgumentException(JText::_('LOGICAL_TABLE_ERROR_FIELD_TYPE_MUST_BE_AN_ENUM_FIELD_TYPE'), 500);
		}

		return (array) explode(',', str_replace('\'', '', substr($fieldType, 5, -1)));
	}

	/**
	 * Method to set the fields to the query.
	 *
	 * @param   JDatabaseQuery  $query      current query
	 * @param   string          $fieldName  name of the field
	 * @param   mixed           $value      to set
	 *
	 * @return $this
	 */
	protected function setField($query, $fieldName, $value)
	{
		$dbo = $this->getDbo();

		//make sure we clean up array values before saving
		if (is_array($value))
		{
			$value = json_encode($value);
		}

		$query->set($dbo->qn($fieldName) . ' = ' . $dbo->q($value));

		return $this;
	}

	/**
	 * Method to update a row in the database from the JTable instance properties.
	 * If primary key values are not set this method will call the create method
	 *
	 * @param   array|object  $src     the data to bind before update
	 * @param   array         $ignore  An optional array properties to ignore while binding.
	 *
	 * @throws ErrorException
	 *
	 * @return  boolean  True on success.
	 */
	public function update($src, $ignore = array())
	{
		$src = $this->check($src, $ignore);
		$key = $this->getKeyName();

		if (is_null($src->get($key, null)))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_INVALID_PRIMARY_KEY');
			throw new ErrorException($msg, 400);
		}

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);
		$query->update($this->table);

		$empty = true;

		foreach ($src AS $fieldName => $value)
		{
			if (in_array($fieldName, $this->immutable))
			{
				continue;
			}

			$this->setField($query, $fieldName, $value);
			$empty = false;
		}

		if ($empty)
		{
			return false;
		}

		$pk = $src->get($key);
		$query->where($dbo->qn($key) . ' = ' . (int) $pk);

		if (!$dbo->setQuery($query)->execute())
		{
			$msg = JText::sprintf('LOGICAL_TABLE_ERROR_UPDATE_FAILED', $src->get($key));
			throw new ErrorException($msg, 500);
		}

		return true;
	}

	/**
	 * Method to delete a record by primary key
	 *
	 * @param   int $pk The primary key of the record to delete,
	 *
	 * @return   bool
	 * @throws \Exception
	 */
	public function delete($pk)
	{
		if (empty($pk))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_INVALID_PRIMARY_KEY');
			throw new InvalidArgumentException($msg, 400);
		}

		$dbo = $this->getDbo();
		$dbo->transactionStart(true);

		try
		{
			$this->deleteDependents($pk);

			$this->removeOrdering($pk);

			$query = $dbo->getQuery(true);
			$query->delete($this->table);
			$query->where($dbo->qn($this->primaryKey) . ' = ' . $dbo->q($pk));

			$dbo->setQuery($query);

			if (!$dbo->execute())
			{
				$msg = JText::sprintf('LOGICAL_TABLE_ERROR_DELETE_FAILED', $pk);
				throw new ErrorException($msg, 500);
			}

			$dbo->transactionCommit(true);
		}
		catch (\Exception $e)
		{
			$dbo->transactionRollback(true);
			throw $e;
		}

		return true;
	}

	/**
	 * Method to adjust ordering when deleting a record
	 *
	 * @param   int $pk The primary key of the record to delete,
	 *
	 * @return void
	 *
	 * @throws ErrorException
	 */
	protected function removeOrdering($pk)
	{
		if (empty($this->orderingField))
		{
			return;
		}

		$src = $this->normalizeSrc($this->load($pk));
		$ordering = $src->get($this->orderingField);

		// Then we need to make a space for it
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->update($this->table)
			->set($dbo->qn($this->orderingField) . ' = ' . $this->orderingField . ' - 1')
			->where($dbo->qn($this->orderingField) . ' > ' . $ordering);

		$this->getReorderConditions($src, $query);

		$dbo->setQuery($query)->execute();
	}

	/**
	 * Method to delete dependent records
	 * This method is intended to be overridden by inheriting classes
	 *
	 * @param   int  $parentId  primary key of hte parent record
	 *
	 * @return bool
	 */
	public function deleteDependents($parentId)
	{
		return true;
	}

	/**
	 * Method to load and return a table object.
	 *
	 * @param   string    $option  The component option that the table belongs to
	 * @param   string    $name    The name of the view
	 * @param   string    $prefix  The class prefix
	 *
	 * @throws ErrorException
	 * @return  TableInterface   A table object
	 *
	 * @since   0.0.8
	 */
	protected function getTable($option, $name, $prefix = null)
	{
		// Clean the model name
		$prefix = preg_replace('/[^A-Z0-9_]/i', '', $prefix);
		$name = preg_replace('/[^A-Z0-9_]/i', '', $name);

		$config = new Registry();
		$config->set('dbo', $this->getDbo());
		$config->set('resource', $name);
		$config->set('option', $option);

		if(empty($prefix))
		{
			$prefix = substr($option, 4);
		}

		$className = ucfirst($prefix) . 'Table' . ucfirst($name);

		if (!class_exists($className))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_TABLE_NAME_NOT_SUPPORTED');
			throw new ErrorException($msg . ': ' . $className);
		}

		return new $className($config);
	}

	/**
	 * Method to load a row from the database by primary key and bind the fields
	 * to the JTable instance properties.
	 *
	 * @param   int    $pk      The primary key of the record to load,
	 * @param   array  $ignore  An optional array of fields to remove from the result.
	 *
	 * @throws InvalidArgumentException if $pk is empty
	 *
	 * @return  object The row if found.
	 */
	public function load($pk, $ignore = array())
	{
		if (empty($pk))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_INVALID_PRIMARY_KEY');
			throw new InvalidArgumentException($msg, 400);
		}

		$query = $this->getSelectQuery(null, null, $ignore);

		$dbo = $this->getDbo();
		$primaryKey = $this->getKeyName();
		//@todo make work with compound primary keys
		$query->where($dbo->qn($primaryKey) . ' = ' . $dbo->q($pk));

		$result = $dbo->setQuery($query)->loadObject();

		if (empty($result))
		{
			$msg = JText::_('LOGICAL_TABLE_ERROR_COULD_NOT_LOCATE_RECORD') . ': ' . $pk;
			throw new InvalidArgumentException($msg, 500);
		}

		return $result;
	}

	/**
	 * Method to get a base select query pre-populated with table field names
	 *
	 * @param   JDatabaseQuery $query  Query object
	 * @param   string  $alias   Optional table alias to use in the query
	 * @param   array   $ignore  An optional array of fields to remove from the result.
	 *
	 * @return JDatabaseQuery
	 */
	public function getSelectQuery($query = null, $alias = null,  $ignore = array())
	{
		$dbo = $this->getDbo();
		$prefix = '';

		if (!empty($alias))
		{
			$prefix = $alias . '.';
		}

		if (empty($query))
		{
			$query = $dbo->getQuery(true);
			$table = $this->table;
			$query->from($dbo->qn($table, $alias));
		}

		$fields = $this->getFields();

		$select = array();

		foreach ($fields AS $name => $value)
		{
			if (in_array($name, $ignore))
			{
				continue;
			}

			$select[] = $prefix .$dbo->qn($name);
		}

		$query->select($select);

		$createdByField = $this->getCreatedByField();

		if(!empty($createdByField))
		{
			$this->joinUserName($query, $prefix .$createdByField, $createdByField . '_name');
		}

		$modifiedByField = $this->getModifiedByField();

		if (!empty($modifiedByField))
		{
			$this->joinUserName($query, $prefix . $modifiedByField, $modifiedByField .'_name');
		}

		return $query;
	}

	/**
	 * Method to add a left joint to the users table for the record owners name
	 *
	 * @param   JDatabaseQuery $query      Query object
	 * @param   string         $onField    The prefixed field name to join on
	 * @param string           $fieldAlias Alias to use for DB and return name value
	 *
	 * @return \JDatabaseQuery
	 */
	public function joinUserName(JDatabaseQuery $query, $onField = 'a.user_id', $fieldAlias = "user_name")
	{
		$query->select('(SELECT ' .$fieldAlias .'.name FROM #__users AS ' . $fieldAlias .' WHERE '. $fieldAlias.'.id = ' . $onField.' LIMIT 1) AS ' . $fieldAlias);

		return $query;
	}

	/**
	 * Method to truncate the table and restore the root element.
	 *
	 * @return boolean
	 */
	public function truncate()
	{
		$dbo = $this->getDbo();
		$dbo->setQuery('TRUNCATE ' . $this->table);

		return ($dbo->execute() !== false);
	}

	/**
	 * Method to lock the database table for writing.
	 *
	 * @throws  RuntimeException
	 *
	 * @return  boolean  True on success.
	 */
	protected function lockTable()
	{
		$dbo = $this->getDbo();
		$dbo->lockTable($this->table);
		$this->locked = true;

		return $this->locked;
	}

	/**
	 * Method to unlock the database table for writing.
	 *
	 * @return  boolean  True on success.
	 */
	protected function unlockTable()
	{
		$dbo = $this->getDbo();
		$dbo->unlockTables();
		$this->locked = false;

		return true;
	}
}
