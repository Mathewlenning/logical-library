<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Registry;

// No direct access
defined('_JEXEC') or die;

/**
 * Class Registry
 *
 * @package  Logical\Registry
 * @since    0.0.11
 */
class Registry extends \Joomla\Registry\Registry
{
	/**
	 * Overridden to use
	 *
	 * \Joomla\Registry\Registry::get method
	 *
	 * @param   string  $name  of the property to get
	 *
	 * @return mixed
	 */
	public function __get($name)
	{
		return $this->get($name);
	}

	/**
	 * Overridden to use
	 *
	 * \Joomla\Registry\Registry::set method
	 *
	 * @param   string  $name   to store the value under
	 * @param   mixed   $value  the value
	 *
	 * @return mixed
	 */
	public function __set($name, $value)
	{
		$this->set($name, $value);
	}

	/**
	 * Overridden to check
	 *
	 * \Joomla\Registry\Registry::data for the property
	 *
	 * @param   string  $name  to store the value under
	 *
	 * @return bool
	 */
	public function __isset($name)
	{
		return isset($this->data);
	}

	/**
	 * Method to recursively bind data to a parent object.
	 * Overridden to make sure widget elements get converted to a string
	 *
	 * @param   object   $parent     The parent object on which to attach the data values.
	 * @param   mixed    $data       An array or object of data to bind to the parent object.
	 * @param   boolean  $recursive  True to support recursive bindData.
	 * @param   boolean  $allowNull  True to allow null values.
	 *
	 * @return  void
	 *
	 * @since   1.0
	 */
	protected function bindData($parent, $data, $recursive = true, $allowNull = true)
	{
		if (is_array($data))
		{
			foreach ($data AS $k => $v)
			{
				if (($v instanceof \Logical\Widget\WidgetElement))
				{
					$data[$k] = (string) $v;
				}
			}
		}

		parent::bindData($parent, $data, $recursive, $allowNull);
	}

	/**
	 * Merge a Registry object into this one
	 *
	 * @param   Registry  $source     Source Registry object to merge.
	 * @param   boolean   $recursive  True to support recursive merge the children values.
	 * @param   boolean   $allowNull  True to allow empty values to be merged
	 *
	 * @return  Registry|false  Return this object to support chaining or false if $source is not an instance of Registry.
	 */
	public function fullMerge($source, $recursive = false, $allowNull = true)
	{
		if (!$source instanceof Registry)
		{
			return false;
		}

		$this->bindData($this->data, $source->toArray(), $recursive, $allowNull);

		return $this;
	}

    /**
     * Method to recursively convert data to array to bind with JForm.
     *
     * @param   string        $separator  The key separator.
     * @param   array|object  $data       Data source of this scope.
     * @param   array         $array      The result array, it is passed by reference.
     * @param   string        $prefix     Last level key prefix.
     *
     * @return  array
     *
     * @since   1.3.0
     */
    public function toJForm($separator = null, $data = null, &$array = array(), $prefix = '')
    {
        $data = (array) $data;

        if (empty($separator))
        {
            $separator = $this->separator;
        }

        if (empty($data))
        {
            $data = $this->data;
        }

        foreach ($data as $k => $v)
        {
            if ($v instanceof \Joomla\CMS\User\User)
            {
                continue;
            }

            $key = $prefix ? $prefix . $separator . $k : $k;

            if (is_object($v))
            {
                $v = (array) $v;
            }

            if (is_array($v))
            {
                $vKeys = array_keys($v);

                if (empty($vKeys))
                {
                    $array[$key] = $v;
                    continue;
                }

                if (!is_numeric($vKeys[0]))
                {
                    $this->toJForm($separator, $v, $array, $key);
                    continue;
                }

                foreach ($v AS $index => $item)
                {
                    if ($item instanceof Registry)
                    {
                        $v[$index] = $item->toArray();
                    }
                }
            }

            $array[$key] = $v;
        }

        return $array;
    }
}
