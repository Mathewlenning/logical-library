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
class Search implements FilterInterface
{
    /**
     * Name of state variable to get the search value from
     * @var string
     */
    private $searchKey = 'filter.search';

    /**
     * Array of prefixed field names to search
     * @var array
     */
    private $searchFields = array();

    /**
     *
     * @var array ['exact' => string, 'general' => array]
     */
    private $cleanSearch = array();

    /**
     * Constructor
     *
     * @param   array  $fieldNames  aliased field names and search type array ['fieldname'=> prefix.name,'type'=> exact|fulltext|general|prefix|suffix|int]
     */
    public function __construct($fieldNames = array())
    {
	    if (!is_array($fieldNames))
	    {
		    $fieldNames = array($fieldNames);

		    if(strpos($fieldNames[0], ',') !== false)
		    {
			    $fieldNames = explode(',', $fieldNames[0]);
		    }
	    }

	    foreach ($fieldNames AS &$fieldName)
	    {
		    if(!is_array($fieldName))
		    {
			    $fieldName = array('fieldname' => trim($fieldName));
		    }

		    if (empty($fieldName['type']))
		    {
			    $fieldName['type'] = 'general';
		    }
	    }

	    $this->searchFields = $fieldNames;
    }

    /**
     * Method to add a Field to the search fields
     *
     * @param   string  $fieldName  I.E. a.field_name
     *
     * @return void
     */
    public function addSearchField($fieldName, $type = 'general')
    {
        $this->searchFields[] = array('fieldname' => $fieldName, 'type' => $type);
    }

    /**
     * This method appends WHERE $fieldName LIKE % $search % to the query for each search field
     * If the search contains a quoted value then it appends WHERE $fieldName = $search
     * for each search field.
     *
     * @param   Registry         $state  to search for the value
     * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
     * @param   JDatabaseQuery   $query  the query to add the filter to
     *
     * @return JDatabaseQuery
     */
    public function addFilter(Registry $state, JDatabaseDriver $dbo, \JDatabaseQuery $query)
    {
        $search = $state->get($this->searchKey);

        if (empty($this->searchFields) || empty($search))
        {
            // No Search
            return $query;
        }

        $glue = ' LIKE ';

        $cleanSearch = $this->getCleanSearch($search, $dbo);

        if (empty($cleanSearch['general']) && empty($cleanSearch['exact']))
        {
            // nothing to search for
            return $query;
        }

        $where = array();

        foreach ($this->searchFields as $fieldName)
        {
            switch ($fieldName['type'])
            {
                case 'exact':
                    if (empty($cleanSearch['exact']))
                    {
                        continue;
                    }

                    $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .') = LOWER(' .$dbo->quote($cleanSearch['exact']) . ')';

                    break;
                case 'fulltext':
                    if (empty($cleanSearch['exact']))
                    {
                        continue;
                    }

                    $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .')'. $glue . 'LOWER(' .$dbo->quote('%'.$cleanSearch['exact'].'%') . ')';

                    break;
                case 'general':

                    if (empty($cleanSearch['general']))
                    {
                        continue;
                    }

                    foreach ($cleanSearch['general'] AS $searchValue)
                    {
                        $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .')'. $glue . 'LOWER(' .$dbo->quote('%'.$searchValue) . ')';
                        $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .')'. $glue . 'LOWER(' .$dbo->quote($searchValue.'%') . ')';
                    }

                    break;
                case 'prefix':

                    if (empty($cleanSearch['general']))
                    {
                        continue;
                    }

                    foreach ($cleanSearch['general'] AS $searchValue)
                    {
                        $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .')'. $glue . 'LOWER(' .$dbo->quote($searchValue.'%') . ')';
                    }

                    break;
                case 'suffix':

                    if (empty($cleanSearch['general']))
                    {
                        continue;
                    }

                    foreach ($cleanSearch['general'] AS $searchValue)
                    {
                        $where[] = 'LOWER(' . $dbo->quoteName($fieldName['fieldname']) .')'. $glue . 'LOWER(' .$dbo->quote('%'.$searchValue) . ')';
                    }

                    break;
                case 'int':
                    if (empty($cleanSearch['exact']) || !is_numeric($cleanSearch['exact']))
                    {
                        continue;
                    }

                    $where[] = $dbo->quoteName($fieldName['fieldname']) .' = ' . (int) $cleanSearch['exact'];
                    break;
            }
        }

        if(empty($where))
        {
            return $query;
        }

        $query->where('(' . implode(' OR ', $where) . ')');

        return $query;
    }


    /**
     * @param   string           $searchString
     * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
     * @param   string           $type   general or exact
     * @return array|string[]
     */
    protected function getCleanSearch($searchString, $dbo, $type = 'general')
    {
        if(!empty($this->cleanSearch))
        {
            return $this->cleanSearch;
        }

        $this->cleanSearch = array('exact'=> $this->getCleanValue($searchString, $dbo), 'general' => array());

        $rawSearch = str_replace(' ', ',', $searchString);

        $rawSearch = explode(',', $rawSearch);

        if (!is_array($rawSearch))
        {
            $cleanRawSearch = $this->getCleanValue($rawSearch, $dbo);
            $this->cleanSearch['general'][] = $cleanRawSearch;


            return $this->cleanSearch;
        }

        foreach ($rawSearch AS $value)
        {
            if (empty($value))
            {
                continue;
            }

            $cleanValue = $this->getCleanValue($value, $dbo);
            $this->cleanSearch['general'][] = $cleanValue;
        }

        return $this->cleanSearch;
    }

    protected function getCleanValue($value, $dbo)
    {
        return $dbo->escape(trim($value), true);
    }
}
