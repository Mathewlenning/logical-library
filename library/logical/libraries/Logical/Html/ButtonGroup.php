<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Html;

// No direct access
defined('_JEXEC') or die;

/**
 * Class ButtonGroup
 *
 * @package  Logical\Html
 * @since    0.0.20
 */
class ButtonGroup
{
	public $class;

	public $buttons = array();

	/**
	 * Method to set the button group class
	 *
	 * @param   string  $class  CSS class for the button group
	 *
	 * @return $this
	 */
	public function setClass($class)
	{
		$this->class = (string) $class;

		return $this;
	}

	/**
	 * Method to add a button to the button group
	 *
	 * @param   string      $widgetId        the widget id to use
	 * @param   string      $buttonClass     the button class
	 * @param   string      $iconClass       the icon class
	 * @param   string      $buttonText      the text to use in the button
	 * @param   array       $additionalVars  other variables to add to the button definition
	 * @param   bool|false  $before          Should we place the button before the others?
	 *
	 * @return $this
	 */
	public function addButton($widgetId, $buttonClass, $iconClass, $buttonText, $additionalVars = array(), $before = false)
	{
		$btn = array(
			'widgetId' => $widgetId,
			'class' => $buttonClass,
			'icon-class' => $iconClass,
			'button-text' => $buttonText
		);

		foreach ($additionalVars as $key => $value)
		{
			if ($key == 'class')
			{
				$btn[$key] .= ' ' . $value;

				continue;
			}

			if($key == 'icon-class')
			{
				$btn[$key] .= ' ' . $value;

				if($value === false)
				{
					$btn[$key] = '';
				}

				continue;
			}

			$btn[$key] = $value;
		}

		if ($before)
		{
			array_unshift($this->buttons, $btn);

			return $this;
		}

		if($btn['widgetId'] == 'logical.form.button.link' && empty($btn['href']))
		{
			$btn['href'] = 'javascript:void(0)';
		}

		$this->buttons[] = $btn;

		return $this;
	}

	/**
	 * Method to remove a button by attribute
	 *
	 * @param string  $attrName   Attribute name to look for
	 * @param string  $attrValue  (optional) Value to look for
	 *
	 * @return $this
	 */
	public function removeButtonsByAttribute($attrName, $attrValue = null)
	{
		foreach ($this->buttons AS $key => $button)
		{
			$keyExists = array_key_exists($attrName, $button);

			if (!$keyExists)
			{
				continue;
			}

			if(is_null($attrValue))
			{
				unset($this->buttons[$key]);
				continue;
			}

			if ($button[$attrName] == $attrValue)
			{
				unset($this->buttons[$key]);
				continue;
			}
		}

		return $this;
	}
}
