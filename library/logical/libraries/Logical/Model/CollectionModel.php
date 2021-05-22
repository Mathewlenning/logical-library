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

use Logical\Model\Filter\FilterInterface;
use Logical\Registry\Registry;
use Logical\Html\Pagination;
use Logical\Table\Table;
use Logical\Table\TableInterface;

use JComponentHelper;
use JPagination;
use JDatabaseQuery;
use JFactory;


/**
 * Class CollectionModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.1
 */
abstract class CollectionModel extends DataModel
{
	/**
	 * A state object
	 *
	 * @var    Registry
	 * @since  0.0.1
	 */
	protected $state;

	/**
	 * Indicates if the internal state has been set
	 *
	 * @var    boolean
	 * @since  12.2
	 */
	protected $stateIsSet = false;

	/**
	 * Flag if the internal state should be updated
	 * from request
	 *
	 * @var boolean
	 */
	protected $ignoreRequest;

	/**
	 * Array of filter objects
	 *
	 * @var array
	 */
	protected $filters = array();

	/**
	 * Pagination class
	 * @var JPagination
	 */
	protected $pagination = null;

    /**
     * @var bool Should the query use pagination?
     */
	protected $usePagination = true;

	/**
	 * Constructor sets the state object, creating it if it doesn't exist in the configuration
	 *
	 * @param   Registry  $config  Configuration
	 */
	public function __construct(Registry $config = null)
	{
		parent::__construct($config);

		$this->state = $this->config->get('state', new Registry);

		if((!$this->state instanceof  Registry))
		{
			$this->state = new Registry($this->state);
		}

		$this->ignoreRequest = $this->config->get('ignore_request', false);
	}

	/**
	 * Method to override ignore request
	 *
	 * @param bool $ignoreRequest
	 */
	public function setIgnoreRequest($ignoreRequest)
	{
		$this->ignoreRequest = ($ignoreRequest == true);
	}

    /**
     * Method to set wheter or not to use pagination
     *
     * @param $value
     */
	public function setUsePagination($value)
    {
        $this->usePagination = ($value === true);
    }

    /**
     * Check to see if we should use pagination
     *
     * @return bool
     */
    public function shouldUsePagination()
    {
        return $this->usePagination;
    }

	/**
	 * Method to get model state variables
	 *
	 * @param   string $property Optional parameter name
	 * @param   mixed  $default  Optional default value
	 *
	 * @return  Registry  The property where specified, the state object where omitted
	 *
	 * @since   0.0.1
	 * @throws \Exception
	 */
	public function getState($property = null, $default = null)
	{
		if (!$this->ignoreRequest && !$this->stateIsSet)
		{

			// Set the model state set flag to true.
			$this->stateIsSet = true;

			// Protected method to auto-populate the model state.
			$this->populateState();

		}

        $config = $this->getConfig();

        if($config->get('isHmvc') == true)
        {
            $this->setFilters($config->get('filter', array()));
        }

        $returnProperty = $this->state;

		if ($property !== null)
		{
			$returnProperty = $this->state->get($property, $default);
		}

		return $returnProperty;
	}

	/**
	 * Method to pre-populate the model state
	 *
	 * This method should only be called once per instantiation and is designed
	 * to be called on the first call to the getState() method unless the model
	 * configuration flag to ignore the request is set.
	 *
	 * @param   string $ordering  Field name to order by
	 * @param   string $direction Direction to order by (ASC, DESC)
	 *
	 * @todo    Figure out a way to have stateless models, but maintain filtering/pagination
	 * @return void
	 *
	 * @throws \Exception
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		$app = JFactory::getApplication();
		$params = (array) $this->config->params;
        $context = $this->getContext();
        $paramsContext = (!empty($params['context']))? $params['context']: null;
		$filters = array();
        $ordering = array();
        $direction = array();

		// Add filters from the menu item if they exist
		foreach ($params AS $key => $value)
		{
		    // if this isn't our context we don't use it.
		    if ($paramsContext != $context)
            {
                break;
            }

			if (!is_array($value))
			{
				$value = array(trim($value));
			}

            if ($key == 'list_ordering' && !empty($value))
            {
                $ordering = $value;

                continue;
            }

            if ($key == 'list_direction' && !empty($value))
            {
                //directions has to be a comma separated list
                $values = explode(',', $value[0]);

                // just in case there wasn't a comma
                if (!is_array($values))
                {
                   $values = array($values);
                }

                $direction = $values;
                continue;
            }

            if ($key == 'list_use_pagination' && !empty($value))
            {
                if ($value[0] === 'NO')
                {
                   $this->setUsePagination(false);
                }
            }

            if ($key == 'filter_from' && !empty($value))
            {
                $now = new \JDate();
                $now->modify($value[0]);

                $filters['from'] = $now->format('Y-m-d') .' 00:00:01';

                continue;
            }

            $parts = explode('_', $key);
            $prefix = array_shift($parts);

            if ($prefix != 'filter' || count($parts) < 2 || empty($value))
            {
                continue;
            }

            $alias = array_shift($parts);
            $filterKey = $alias .'.'. implode('_', $parts);

			$filters[$filterKey] = $value;
		}


        if (!empty($params['page_title']))
        {
            $context .= '.' . $params['page_title'];
        }

		$requestFilters = $app->getUserStateFromRequest($context . '.filter', 'filter', array(), 'array');

		$requestFilters += $filters;
        $this->setFilters($requestFilters);

		$limit = $app->getUserStateFromRequest($context . 'list.limit', 'limit', $app->get('list_limit'), 'uint');
		$this->setState('list.limit', $limit);

		// Ordering
        $orderColName = $app->getUserStateFromRequest($context . '.ordercol', 'filter_order', $ordering, 'array');

        foreach ($orderColName AS $key => $value)
        {
            if(empty($value))
            {
                unset($orderColName[$key]);
            }
        }

        if(empty($orderColName) && !empty($ordering))
        {
            $orderColName = $ordering;
        }

		$this->setState('list.ordering', $orderColName);

		// Check if the ordering direction is valid, otherwise use the incoming value.
		$orderDirections = $app->getUserStateFromRequest($context . '.orderdirn', 'filter_order_Dir', $direction, 'array');

        if (empty($orderDirections))
        {
            $orderDirections = $direction;
        }

		if(!is_array($orderDirections))
        {
            $orderDirections = array($orderDirections);
        }

		foreach ($orderDirections AS $index => $orderDir)
		{
            if (!in_array(strtoupper($orderDir), array('ASC', 'DESC')))
            {
                $orderDirections[$index] = 'ASC';
            }
        }

		$this->setState('list.direction', $orderDirections);

		$limitStartValue = $app->getUserStateFromRequest($context . '.limitstart', 'limitstart', 0, 'int');

		if ($limit != 0)
		{
			$limitStart = (floor($limitStartValue / $limit) * $limit);
		}
		else
		{
			$limitStart = 0;
		}

		$this->setState('list.start', $limitStart);

		$config = $this->config;
		$params = JComponentHelper::getParams($config['option']);
		$this->setState('params', $params);
	}

    /**
     * Method to set filter values
     *
     * @param array $filters
     */
    protected function setFilters($filters)
    {
        foreach ($filters AS $name => $value)
        {
            if(!is_array($value))
            {
                $value = trim($value);
            }

            $this->setState('filter.' . $name, $value);
        }
    }

	/**
	 * Method to set model state variables
	 *
	 * @param   string  $property  The name of the property.
	 * @param   mixed   $value     The value of the property to set or null.
	 *
	 * @return  mixed  The previous value of the property or null if not set.
	 *
	 * @since   0.0.1
	 */
	public function setState($property, $value = null)
	{
		return $this->state->set($property, $value);
	}

	/**
	 * Method to add a filter to the model
	 *
	 * @param   FilterInterface  $filter  the filter to add
	 *
	 * @return void
	 */
	public function addFilter(FilterInterface $filter)
	{
		$className = get_class($filter);

		if (!array_key_exists($className, $this->filters))
		{
			$this->filters[$className] = $filter;
		}
	}

	/**
	 * Method to remove a filter from the model
	 *
	 * @param   string  $className  name of the filter to remove
	 */
	public function removeFilter($className)
	{
		if (empty($this->filters))
		{
			$this->prepareFilters();
		}

		if (!array_key_exists($className, $this->filters))
		{
			return;
		}

		unset($this->filters[$className]);
	}

	/**
	 * Method to get filter objects by class name
	 * leaving this null will return the entire filters array
	 *
	 * @param   string  $className  class name of the filter you want
	 *
	 * @return array|object|null
	 */
	public function getFilters($className = null)
	{
		if (empty($this->filters))
		{
			$this->prepareFilters();
		}

		if(empty($className))
		{
			return $this->filters;
		}

		if(!isset($this->filters[$className]))
		{
			return null;
		}

		return $this->filters[$className];
	}

	/**
	 * Method to attach filter object to the model
	 * This is method is intended to be overridden in extending classes.
	 * NOTE: Calling getFilters or removeFilters before populating $this->filters will result in infinite loop
	 */
	public function prepareFilters()
	{
		return;
	}

	/**
	 * Method to get an array of data items.
	 *
	 * @param   string $key           the name of a field on which to key the result array.
	 * @param   string $class         the class name of the return item default is Logical\Registry\Registry
	 * @param   bool   $appendFilters if set to false, will only return the main query.
	 * @param   array  $ignore        An optional array of fields to remove from the result.
	 *
	 * @return  mixed  An array of data items on success, false on failure.
	 * @throws \ErrorException
	 */
	public function getList($key = null, $class = 'Logical\Registry\Registry', $appendFilters = true, $ignore = array())
	{
		$query = $this->getListQuery(null, 'a', $ignore);

		$this->observers->update('onBeforeGetList', array($this, $query, $key, $class));

		if ($appendFilters)
		{
			$this->appendFilters($query);
		}

		$limit = (int) $this->getState('list.limit', 0);
		$total = $this->getTotal($query);
		$start = $this->getStart($limit, $total);

		$dbo = $this->getDbo();

		if (!$this->usePagination)
		{
          $limit = 0;
          $start = 0;
        }

        $dbo->setQuery($query, $start, $limit);

		$items = array();
		$records = $dbo->loadObjectList($key);

		foreach ($records AS $index => $record)
		{
			$items[$index] = new $class($record);
		}

		$this->observers->update('onAfterGetList', array($this, $query, $items));

		return $items;
	}

	/**
	 * Method to add filters to a query
	 *
	 * @param   JDatabaseQuery  $query  the query to append filters
	 *
	 * @return JDatabaseQuery
	 */
	protected function appendFilters(JDatabaseQuery $query)
	{
		if (empty($this->filters))
		{
			$this->prepareFilters();
		}

		$dbo = $this->getDbo();
		$state = $this->getState();

		foreach ($this->filters as $filter)
		{
			if (!($filter instanceof FilterInterface))
			{
				continue;
			}

			$filter->addFilter($state, $dbo, $query);
		}

		return $query;
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
	 * @throws \ErrorException
	 */
	protected function getListQuery(JDatabaseQuery $query = null, $alias = 'a', $ignore = array())
	{
		/** @var Table $table */
		$table = $this->getTable();
		$query = $table->getSelectQuery($query, $alias, $ignore);

		return $query;
	}

	/**
	 * Method to add a left join to the user table for record editor.
	 *
	 * @param   JDatabaseQuery $query      Query object
	 * @param   string         $tableAlias Table alias prefix to use
	 *
	 * @deprecated use Logical\Table\Table::joinUserName instead
	 *
	 * @return JDatabaseQuery
	 *
	 * @throws \ErrorException
	 */
	protected function addEditorQuery(JDatabaseQuery $query, $tableAlias = 'a')
	{
		$this->joinUserName($query, $tableAlias . '.checked_out', 'editor');

		return $query;
	}

	/**
	 * Method to add a left joint to the users table for the record owners name
	 *
	 * @param   JDatabaseQuery  $query    Query object
	 * @param   string          $onField  The prefixed field name to join on
	 *
	 * @deprecated use Logical\Table\Table::joinUserName instead
	 * @return \JDatabaseQuery
	 */
	protected function addOwnerName(JDatabaseQuery $query, $onField = 'a.owner')
	{
		$this->joinUserName($query, $onField, 'owner_name');

		return $query;
	}

	/**
	 * Method to get the starting number of items for the data set.
	 *
	 * @param   int $limit the current list limit
	 * @param   int $total number of items in the data set
	 *
	 * @return  integer  The starting number of items available in the data set.
	 *
	 * @since   0.0.1
	 * @throws \Exception
	 */
	protected function getStart($limit, $total)
	{
		$start = $this->getState('list.start');

		if ($start > $total - $limit)
		{
			$start = max(0, (int) (ceil($total / $limit) - 1) * $limit);
			$this->setState('list.start', $start);
		}

		return $start;
	}

	/**
	 * Method to get the total number of items for the data set.
	 *
	 * @param   JDatabaseQuery  $query  for the data set
	 *
	 * @return  integer  The total number of items available in the data set.
	 *
	 * @since   0.0.1
	 */
	protected function getTotal(JDatabaseQuery $query)
	{
		$total = $this->getState('list.total', null);

		if ($total == null)
		{
			$total = (int) $this->_getListCount($query);
			$this->setState('list.total', $total);
		}

		return $total;
	}

	/**
	 * Returns a record count for the query.
	 *
	 * @param   JDatabaseQuery  $query  The query.
	 *
	 * @return  integer  Number of rows for query.
	 *
	 * @since   0.0.1
	 */
	protected function _getListCount(JDatabaseQuery $query)
	{
		$dbo = $this->getDbo();

		/* if this is a select and there are no GROUP BY or HAVING clause
		 * Use COUNT(*) method to improve performance.
		 */

		$isSelect = ($query->type == 'select');
		$hasGroupClause = ($query->group !== null);
		$hasHaveClause = ($query->having !== null);

		if ($isSelect && !$hasGroupClause && !$hasHaveClause)
		{
			$query = clone $query;
			$query->clear('select')->clear('order')->select('COUNT(*)');

			$dbo->setQuery($query);

			return (int) $dbo->loadResult();
		}

		// Else use brute-force and count all returned results.
		$dbo->setQuery($query);
		$dbo->execute();

		return (int) $dbo->getNumRows();
	}

	/**
	 * Method to get a JPagination object for the data set.
	 *
	 * @return   \Logical\Html\Pagination A Pagination object for the data set.
	 *
	 * @since   0.0.1
	 */
	public function getPagination()
	{
		if (is_null($this->pagination))
		{
			$total = (int) $this->getState('list.total');

			$this->pagination = new Pagination($total, $this->getState());
		}

		return $this->pagination;
	}

	/**
	 * Method to get an array of the filter field class from the FilterFields class if it exists.
	 *
	 * @return array
	 */
	public function getFilterFields()
	{
		if (!isset($this->filters['Logical\Model\Filter\Fields']))
		{
			return array();
		}

		return $this->filters['Logical\Model\Filter\Fields']->getFilterFields($this->getState());
	}

	/**
	 * Method to get an associative array of state types.
	 * This allows extensions to add additional states to their records by overloading this function.
	 *
	 * @return array $stateChangeTypes
	 *
	 * @since 0.0.1
	 * @throws \ErrorException
	 */
	public function getAvailableStates()
	{
		$table = $this->getTable();

		$states = $table->getAvailableStates();

		$assocStates = array();

		foreach ($states AS $state)
		{
			$assocStates[$state] = $state;
		}

		return $assocStates;
	}

	/**
	 * Method to convert an enum defintion into an arrray of option values
	 *
	 * @param   string  $fieldTypeDefinition  enum definition I.E. enum('something','somethingelse')
	 *
	 * @return array   format array(value=>value)
	 */
	protected function getEnumFilterOptions($fieldTypeDefinition)
	{
		$values = (array) explode(',', str_replace('\'', '', substr($fieldTypeDefinition, 5, -1)));

		$returnValues = array();

		foreach ($values AS $value)
		{
			$returnValues[$value] = $value;
		}

		return $returnValues;
	}

	/**
	 * Method to save the ordering of an array of ids
	 *
	 * @param   array  $cids of ids
	 * @param   array  $src from the form
	 *
	 * @return bool
	 *
	 * @throws \ErrorException
	 * @todo this needs to be refactored to consider the tables natural ordering methods
	 *       and changes to the getReorderConditions signature
	 */
	public function saveOrder($cids, $src = array())
	{
		$table = $this->getTable();

		if (!$table->supportsOrdering())
		{
			return false;
		}

		$reorderConditions = array();
		$pks = (array) $cids;

		// Update ordering values
		foreach ($pks as $i => $pk)
		{
			$newOrder = $i + 1;
			$keyName = $table->getKeyName();
			$orderField = $table->getOrderingField();

			$src[$keyName] = $pk;
			$src[$orderField] = $newOrder;
			$table->update($src);

			// Remember to reorder within position and client_id
			$tempCondition = $table->getReorderConditions($src);

			if (!in_array($tempCondition, $reorderConditions))
			{
				$reorderConditions[] = $tempCondition;
			}
		}

		$this->reorder($table, $reorderConditions);

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to reorder records according to conditions.
	 *
	 * @param   TableInterface  $table       Table object
	 * @param   array           $conditions  Reordering conditions
	 *
	 * @return bool
	 */
	protected function reorder($table, $conditions)
	{
		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$primaryKey = $table->getKeyName();
		$orderField = $table->getOrderingField();

		$query->select(array($primaryKey, $orderField));
		$query->from($this->getTableName());
		$query->order($orderField);

		foreach ($conditions AS $where)
		{
			$query->clear('where');
			$query->where($orderField . ' >= 0');

			if (!empty($where))
			{
				$query->where($where);
			}

			$dbo->setQuery($query);
			$rows = $dbo->loadObjectList();

			foreach ($rows AS $i => $row)
			{
				$isPositiveInt = ($row->{$orderField} >= 0);
				$shouldAdjust = ($row->{$orderField} != ($i + 1));

				if ($isPositiveInt && $shouldAdjust)
				{
					$newOrder = ($i + 1);
					$table->update(array($primaryKey => $row->{$primaryKey}, $orderField => $newOrder), array('checked_out', 'checked_out_time'));
				}
			}
		}

		return true;
	}

	/**
	 * Convenience method to create a fake model state config
	 *
	 * @return array [state=>[],filter=>{}, list=> {}]
	 */
	public function getForgedStateConfiguration()
	{
		$filter = new \stdClass();
		$filter->a = new \stdClass();
		$config = array(
			'state' => array(
			'filter' => $filter,
			'list' => new \stdClass(),
			)
		);

		return $config;
	}
}
