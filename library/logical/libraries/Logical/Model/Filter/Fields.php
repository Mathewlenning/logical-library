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
 * Class Fields
 *
 * @package  Logical\Model\Filter
 * @since    0.0.1
 */
class Fields implements FilterInterface
{
	/**
	 * Associative array of field names by dataKey
	 * Fields are alias prefixed  I.E. a.field_name
	 *
	 * @var array
	 */
	protected $fields = array();

	/**
	 * Optional JText Prefix
	 * @var string
	 */
	protected $prefix;

	/**
	 * An array of formatted display field objects
	 *
	 * This is used for rendering select boxes in the views
	 *
	 * @var array
	 */
	protected $displayFields;

	/**
	 * Constructor
	 *
	 * @param   array   $fields  prefixed field names I.E. a.myfield
	 * @param   string  $prefix  JText Language string prefix I.E. COM_MYCOMPONENT
	 */
	public function __construct($fields = array(), $prefix = 'LOGICAL')
	{
		foreach ($fields AS $field)
		{
			$this->fields[$field['fieldAlias']] = $field;
		}

		$this->prefix = strtoupper($prefix);
	}

	/**
	 * Add a field to filter by
	 *
	 * @param   string      $fieldAlias  The prefixed alias of the field name I.E. a.field_name
	 * @param   array|bool  $options     Valid options for this field. Format array($value => $text)
	 *
	 * @return void
	 */
	public function addField($fieldAlias, $options)
	{
		$this->fields[$fieldAlias] = array('fieldAlias' => $fieldAlias, 'options' => $options);
	}

	/**
	 * Method to remove a filter
	 * This is useful if you're limiting filter capability via ACL
	 *
	 * @param   string  $fieldAlias  The prefixed alias of the field name I.E. a.field_name
	 *
	 * @return void
	 */
	public function removeFilter($fieldAlias)
	{
		unset($this->fields[$fieldAlias]);
	}

	/**
	 * This method appends WHERE  $dataKey = $StateValue to the query
	 * for every field in the fields list that is active
	 *
	 * @param   Registry         $state  to search for the value
	 * @param   JDatabaseDriver  $dbo    database object to use for quoting/escaping
	 * @param   JDatabaseQuery   $query  the query to add the filter to
	 *
	 * @return JDatabaseQuery
	 */
	public function addFilter(Registry $state, JDatabaseDriver $dbo, JDatabaseQuery $query)
	{
		$active = $this->getActiveFilters($state);

		foreach ($active as $dataKey => $value)
		{
			if (!is_array($value))
			{
				$query->where($dbo->quoteName($dataKey) . ' = ' . $dbo->quote($value));

				continue;
			}

			foreach ($value AS $index => $userInput)
			{

				if (empty($userInput))
				{
					unset($value[$index]);

					continue;
				}

				$value[$index] = $dbo->quote($userInput);
			}

			if (empty($value))
			{
				continue;
			}

			$query->where($dbo->quoteName($dataKey) . ' IN( ' . implode(',', $value) .')');
		}

		return $query;
	}

	/**
	 * Method to get an array of all active filters.
	 * An active filter is a filter which has a value in the state and has been added to the fields list
	 *
	 * @param   Registry  $state  to search for the value
	 *
	 * @return array
	 */
	public function getActiveFilters(Registry $state)
	{
		if (count($this->fields) == 0)
		{
			return array();
		}

		$active = array();

		foreach ($this->fields AS $field)
		{

			$filterName = 'filter.' . $field['fieldAlias'];

			$filterValue = $state->get($filterName, null);

			if (empty($filterValue) && !is_numeric($filterValue))
			{
				continue;
			}

			$active[$field['fieldAlias']] = $filterValue;
		}

		return $active;
	}

	/**
	 * Method to get an array of filter fields
	 *
	 * @param   Registry  $state  to search for the value
	 *
	 * @return array
	 */
	public function getFilterFields(Registry $state)
	{
		if (!is_null($this->displayFields))
		{
			return $this->displayFields;
		}

		$filters = array();

		foreach ($this->fields AS $filter)
		{
			if ($filter['options'] === false)
			{
				continue;
			}

			$field = new \stdClass;

			$field->name = 'filter[' . $filter['fieldAlias'] . ']';

			$field->selected = $state->get('filter.' . $filter['fieldAlias']);

			$field->default_option = $this->prefix . '_SELECT_DEFAULT_' . strtoupper(str_replace('.', '_', $filter['fieldAlias']));
			$field->options = array();

			foreach ($filter['options'] AS $title => $value)
			{
				$option = new \stdClass;
				$option->value = trim($value);

				if (is_numeric($title))
				{
					$title = $value;
				}

				if (empty($filter['raw']))
				{
					$title = $this->prefix . '_SELECT_OPTION_' . strtoupper($title);
				}

				$option->text = $title;
				$field->options[] = $option;
			}

			$filters[] = $field;
		}

		$this->displayFields = $filters;

		return $filters;
	}
}
