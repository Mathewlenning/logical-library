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

use Logical\Registry\Registry;
use Logical\Model\Filter\Fields;
use JDatabaseQuery, \ErrorException;
use \Throwable;

/**
 * Class RecordModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.1
 */
abstract class RecordIntersectModel extends RecordModel
{
    protected $defaultSort = null;

    protected $defaultSortDir = 'ASC';

	/**
	 * Method to get the models for the entity being intersected
	 *
	 * @param string $context  optional context to help which of the intersect models to return
	 *
	 * @return array of RecordModels
	 */
	abstract public function getIntersectModels($context = null);


	/**
	 * Method to attach filter object to the model
	 */
	public function prepareFilters()
	{
		$config = $this->getConfig();
		$intersectModels = $this->getIntersectModels('prepareFilters');

		$filterFields = array();
		$allowedFields = $this->getFields(array(), false,null, 'a');

		$defaultSort = array();
		/** @var RecordModel $intersectModel */
		foreach ($intersectModels AS $intersectModel)
		{
            list($prefix, $postfix) = explode('.', $intersectModel->getContext());
            $allowedFields += $intersectModel->getFields(array(), false,null, $postfix);
            $defaultSort[] = 'a.'.$intersectModel->getKeyName();

			$filterFields[] = array('fieldAlias' => 'a.'.$intersectModel->getKeyName(), 'options' => false);
		}

		if (!empty($this->defaultSort))
        {
           $defaultSort = $this->defaultSort;
        }

        $this->addFilter(new \Logical\Model\Filter\Sort($defaultSort, $this->defaultSortDir, $allowedFields));

		$this->addFilter(new Fields($filterFields, strtoupper($config['option'])));


		return;
	}

	/**
	 * Method to get a JDatabaseQuery object for retrieving the data set from a database.
	 *
	 * If you don't send a $query it will return a $query object with:
	 *
	 * $query->select('a.*');
	 * $query->from($tableName.' AS a');
	 *
	 * @param   JDatabaseQuery $query  Query object
	 * @param   string         $alias  Table alias to use in the query
	 * @param   array          $ignore An optional array of fields to remove from the result.
	 *
	 * @return JDatabaseQuery   A JDatabaseQuery object to retrieve the data set.
	 *
	 * @throws ErrorException
	 */
	protected function getListQuery(JDatabaseQuery $query = null, $alias = 'a', $ignore = array())
	{
		$query = parent::getListQuery($query, $alias, $ignore);

		$intersectModels = $this->getIntersectModels('getListQuery');

		$dbo = $this->getDbo();

		/** @var RecordModel $intersectModel */
		foreach ($intersectModels AS $intersectModel)
		{
			list($prefix, $postfix) = explode('.', $intersectModel->getContext());
			$tableName = $intersectModel->getTableName();
			$keyName = $intersectModel->getKeyName();
			$query->join('LEFT', $dbo->qn($tableName, $postfix).' ON '. $dbo->qn($postfix) .'.'. $dbo->qn($keyName) .' = '. $alias .'. ' . $dbo->qn($keyName));

			$table = $intersectModel->getTable();
			$query = $table->getSelectQuery($query, $postfix);
		}

		return $query;
	}

	/**
	 * Method to validate data and insert into db
	 *
	 * @param   array $src    Form data to be inserted
	 * @param   array $ignore An optional array properties to ignore while binding.
	 *
	 * @return int primary key of the created record
	 *
	 * @since 0.0.1
	 * @throws ErrorException
	 */
	public function create($src, $ignore = array())
	{
		$intersectModels = $this->getIntersectModels('create');

		/** @var RecordModel $intersectModel */
		foreach ($intersectModels AS $intersectModel)
		{
			$keyName = $intersectModel->getKeyName();

			if (empty($src[$keyName]))
			{
				$src[$keyName] = $intersectModel->create($src);
			}
		}

		return parent::create($src);
	}

	/**
	 * Method to validate data and update into db
	 *
	 * Be careful that you don't have any duplicate field names in your intersects
	 * I.E. table_one::name & table_two::name
	 *
	 * This method overwrite table_two::name
	 *
	 * @param   array  $src     Data to be used to update the record
	 * @param   array  $ignore  An optional array properties to ignore while binding.
	 *
	 * @throws ErrorException
	 *
	 * @return Registry
	 *
	 * @since 0.0.1
	 */
	public function update($src, $ignore = array())
	{
		$intersectModels = $this->getIntersectModels('update');
		$keyName = $this->getKeyName();

		// if the key has a - in it then it is most likely a compound primary key and not supported by update yet.
		//@todo make table support update of compound primary keys
		if (empty($intersectModels) && strpos($keyName, '-') === false)
		{
			return parent::update($src, $ignore);
		}

		$result = new Registry();
		/** @var RecordModel $intersectModel */
		foreach ($intersectModels AS $intersectModel)
		{
			$result->merge($intersectModel->update($src));
		}

		return $result;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   int  $id  record primary keys.
	 *
	 * @throws ErrorException
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since    0.0.1
	 */
	public function delete($id)
	{
		$pk = (int) $id;
		$table = $this->getTable();
		$record = $table->load($pk);

		$this->observers->update('onBeforeDelete', array($this, $pk, $record));

		$intersectModels = $this->getIntersectModels('delete');

		/** @var RecordModel $intersectModel */
		foreach ($intersectModels AS $intersectModel)
		{
			$keyName = $intersectModel->getKeyName();

			if(empty($record->{$keyName}))
			{
				$msg = JText::_('LOGICAL_INTERSECT_MODEL_ERROR_MISSING_INTERSECT_ID');
				throw new ErrorException($msg);
			}

			$intersectModel->delete($record->{$keyName});
		}

		$table->delete($pk);

		$this->observers->update('onAfterDelete', array($this, $id, $record));

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to delete a record using a compound key
	 *
	 * @param array $src form data
	 *
	 * @return boolean
	 *
	 * @throws \Throwable
	 */
	public function deleteCompoundPk($src)
	{
		$dbo = $this->getDbo();
		$intersectModels = $this->getIntersectModels('deleteCompoundPk');

		$dbo->transactionStart(true);

		try
		{
			/** @var RecordModel $intersectModel */
			foreach ($intersectModels AS $intersectModel)
			{
				$keyName = $intersectModel->getKeyName();

				if(empty($src[$keyName]))
				{
					$msg = JText::_('LOGICAL_INTERSECT_MODEL_ERROR_MISSING_INTERSECT_ID');
					throw new ErrorException($msg);
				}

				$intersectModel->delete($src[$keyName]);
			}

			$result = parent::deleteCompoundPk($src);

			$dbo->transactionCommit(true);
		}
		catch (Throwable $e)
		{
			$dbo->transactionRollback(true);
			throw $e;
		}

		return $result;
	}

	public function getItem($pk = null, $class = 'Logical\Registry\Registry')
	{
		if (empty($pk))
		{
			$context = $this->getContext();
			$pk = (int) $this->getState($context . '.id');
		}

		$dbo = $this->getDbo();
		$query = $this->getListQuery();

		$this->observers->update('onBeforeGetItem', array($this, $query, $pk, $class));

		$keys = explode('-', $this->getKeyName());

		$where = array();
		//@todo this is terrible. I need to figure out how to properly identify pk for get item

		foreach ($keys AS $key)
		{
			$where[] = 'a.' . $key . ' = ' . $pk;
		}

		$query->where('(' . implode(' OR ', $where) . ')');
		$dbo->setQuery($query);

		$item = new $class($dbo->loadObject());

		$this->observers->update('onAfterGetItem', array($this, $query, $item));

		return $item;
	}
}
