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

/**
 * Class Sort
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Sort implements FilterInterface
{
    /**
     * @var array default fields to order by
     */
    protected $defaultOrderBy;

    /**
     * @var array default directions
     */
    protected $defaultDirection;

    /**
     * @var array|mixed list of acceptable fields to order by
     */
    protected $acceptableFields = array();

    /**
     * @var bool trigger whether or not to use the default values.
     */
    protected $useDefaults = false;

    /**
     * Constructor
     *
     * @param   string|array  $orderBy    field alias
     * @param   string|array  $direction  order direction (ASC, DESC)
     */
    public function __construct($orderBy, $direction = 'ASC', $acceptFields = array())
    {
        if(!is_array($orderBy))
        {
            $orderBy = array($orderBy);
        }

        if(!is_array($direction))
        {
            $direction = array($direction);
        }

        $this->defaultOrderBy = $orderBy;
        $this->defaultDirection = $direction;
        $this->acceptableFields = $acceptFields;
    }

    /**
     * This method appends ORDER BY list.ordering list.direction to the query
     *
     * @param   Registry         $state  to search for the value
     * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
     * @param   JDatabaseQuery   $query  the query to add the filter to
     *
     * @return JDatabaseQuery
     */
    public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
    {
        $orderBy = $this->getOrderByState($state);
        $direction = $state->get('list.direction', array());

        if (empty($orderBy))
        {
            $orderBy = $this->defaultOrderBy;
        }

        if (empty($direction) || $this->useDefaults)
        {
            $direction = $this->defaultDirection;
        }

        $orderByQuery = $this->getFormatedOrderby($orderBy, $direction, $dbo);

        if (empty($orderByQuery))
        {
            return $query;
        }

        $query->order($orderByQuery);

        return $query;
    }

    protected function getOrderByState($state)
    {
        $orderBy = (array) $state->get('list.ordering', array());

        if (empty($orderBy[0]))
        {
            $this->useDefaults = true;
            return $this->defaultOrderBy;
        }


        if(empty($this->acceptableFields))
        {
            return $orderBy;
        }

        foreach($orderBy AS $index => $field)
        {
            if (!in_array($field, $this->acceptableFields))
            {
                unset($orderBy[$index]);
            }
        }

        if (empty($orderBy))
        {
            $this->useDefaults = true;
            return $this->defaultOrderBy;
        }

        return $orderBy;
    }

    /**
     * Method to the the default sort direction
     * @return string
     */
    public function getDefaultDirection()
    {
        return $this->defaultDirection;
    }

    /**
     * Method to get the default order by value
     * @return string
     */
    public function getDefaultOrderBy()
    {
        return $this->defaultOrderBy;
    }

    /**
     * @param $orderBy
     * @param $direction
     * @param $dbo
     *
     * @return string|null
     */
    protected function getFormatedOrderby($orderBy, $direction, $dbo)
    {
        if (!is_array($orderBy))
        {
            return $dbo->escape($orderBy . ' ' . $direction);
        }

        if (!is_array($direction))
        {
            $direction = array($direction);
        }

        $complexOrderBy = array();

        foreach ($orderBy AS $index => $fieldAlias)
        {
            $dirIndex = 0;

            if(!empty($direction[$index]))
            {
                $dirIndex = $index;
            }

            $complexOrderBy[] = $fieldAlias . ' ' . $direction[$dirIndex];
        }

        if(empty($complexOrderBy))
        {
            return null;
        }

        return $dbo->escape(implode(', ',$complexOrderBy));
    }
}
