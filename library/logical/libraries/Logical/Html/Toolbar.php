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

use Logical\Widget\WidgetRendererInterface;

/**
 * Class Toolbar
 *
 * @package  Logical\Html
 * @since    0.0.20
 */
class Toolbar extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var array of button groups
	 */
	protected $btnGroups = array();

	/**
	 * @var string
	 */
	protected $toolbarClass;

	/**
	 * Method to add a button group to the toolbar
	 *
	 * @param   bool|false  $before  Should we add this to the front of the button array?
	 *
	 * @return \Logical\Html\ButtonGroup
	 */
	public function addButtonGroup($before = false)
	{
		$btnGroup = new ButtonGroup;

		if ($before)
		{
			array_unshift($this->btnGroups, $btnGroup);

			return $btnGroup;
		}

		$this->btnGroups[] = $btnGroup;

		return $btnGroup;
	}

	/**
	 * Method to set the toolbar class
	 *
	 * @param   string  $class  space delimited CSS classes to add to the toolbar
	 *
	 * @return void
	 */
	public function setToolbarClass($class)
	{
		$this->toolbarClass = $class;
	}

	/**
	 * Method to render the toolbar
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if (count($this->btnGroups) <= 0)
		{
			return null;
		}

		return $this->widgetRenderer->render('logical.html.toolbar', array('btnGroups' => $this->btnGroups, 'class' => $this->toolbarClass));
	}

	/**
	 * Method to add a standard new button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addNewButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'add');

		$text = \JText::_('LOGICAL_NEW');

		if (isset($additionalVars['button_text']))
		{
			$text = $additionalVars['button_text'];

			unset($additionalVars['button_text']);
		}

		$buttonGroup->addButton('logical.form.button', 'btn btn-success', 'icon-new', $text, $additionalVars, $before);
	}

	/**
	 * Method to add a standard new button
	 *
	 * @param ButtonGroup $buttonGroup    the button group append/prepend
	 * @param string      $task           Default task
	 * @param array       $additionalVars other variables to add to the button definition
	 * @param bool|false  $before         Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addDefaultButton(ButtonGroup $buttonGroup, $task, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, $task);

		$text = \JText::_('LOGICAL_NEW');

		if (isset($additionalVars['button_text']))
		{
			$text = $additionalVars['button_text'];

			unset($additionalVars['button_text']);
		}

		$buttonGroup->addButton('logical.form.button', 'btn', '', $text, $additionalVars, $before);
	}

	protected function validateAdditionalVars($additionalVars, $defaultTask)
	{
		if (empty($additionalVars['button-task']))
		{
			$additionalVars['button-task'] = $defaultTask;
		}

		if (empty($additionalVars['onclick']))
		{
			$additionalVars['onclick'] = 'logical.form.submitButton(event);';
		}

		return $additionalVars;
	}

	/**
	 * Method to add a standard delete button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addDeleteButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'delete');

		$buttonGroup->addButton('logical.form.button', 'btn btn-danger', 'icon-delete', \JText::_('LOGICAL_DELETE'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard check in button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addCheckInButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'checkin');

		$buttonGroup->addButton('logical.form.button', 'btn', 'icon-checkin', \JText::_('LOGICAL_CHECK_IN'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard record save button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addSaveButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'create.edit');

		$buttonGroup->addButton('logical.form.button', 'btn btn-success', 'icon-apply icon-white', \JText::_('LOGICAL_APPLY'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard save and close button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addSaveCloseButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'create.cancel');

		$buttonGroup->addButton('logical.form.button', 'btn', 'icon-save', \JText::_('LOGICAL_SAVE_AND_CLOSE'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard save and new button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addSaveNewButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'create.add');

		$buttonGroup->addButton('logical.form.button', 'btn', 'icon-save-new', \JText::_('LOGICAL_SAVE_AND_NEW'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard cancel button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addCancelButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'cancel');

		$buttonGroup->addButton('logical.form.button', 'btn', 'icon-cancel', \JText::_('LOGICAL_CANCEL'), $additionalVars, $before);
	}

	/**
	 * Method to add a standard cancel button
	 *
	 * @param   ButtonGroup  $buttonGroup     the button group append/prepend
	 * @param   array        $additionalVars  other variables to add to the button definition
	 * @param   bool|false   $before          Should we place the button before the others?
	 *
	 * @return void
	 */
	public function addCopyButton(ButtonGroup $buttonGroup, $additionalVars = array(), $before = false)
	{
		$additionalVars = $this->validateAdditionalVars($additionalVars, 'create.edit');

		$buttonGroup->addButton('logical.form.button', 'btn', 'icon-save-copy', \JText::_('LOGICAL_SAVE_AS_COPY'), $additionalVars, $before);
	}

	/**
	 * Method to remove a button by attribute
	 * this is a proxy to button group method
	 *
	 * @param string  $attrName   Attribute name to look for
	 * @param string  $attrValue  (optional) Value to look for
	 *
	 * @return $this
	 */
	public function removeButtonsByAttribute($attrName, $attrValue = null)
	{
		foreach ($this->btnGroups AS $group)
		{
			$group->removeButtonsByAttribute($attrName, $attrValue);
		}
	}
}
