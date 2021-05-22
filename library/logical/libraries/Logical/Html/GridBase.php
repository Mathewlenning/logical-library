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

use Logical\Utility\Math;
use Logical\Widget\WidgetRendererInterface;

abstract class GridBase extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string
	 */
	protected $class = '';

	/**
	 * @var array Items to be rendered
	 */
	protected $items = array();

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface  $widgetRenderer widget render
	 * @param   string                   $class css class to add to the row
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer, $class = '')
	{
		parent::__construct($widgetRenderer);

		$this->class = $class;
	}

	/**
	 * Method to set the class. This overrides all previous classes set to the row
	 *
	 * @param   string  $class class to add to the row
	 *
	 * @return $this
	 */
	public function setClass($class)
	{
		$this->class = $class;

		return $this;
	}

	/**
	 * Method to add a class to the row. This appends to the previous class
	 *
	 * @param   string  $class class to add to the row
	 *
	 * @return $this
	 */
	public function addClass($class)
	{
		$this->class .= $class;

		return $this;
	}

	/**
	 * Method to add content to the grid
	 *
	 * @param   string  $content content for the grid item
	 * @param   int     $width   width of the item
	 * @param   string  $class   class to append to the item row element
	 * @param   bool    $before  Should it be placed in front or back of other items
	 *
	 * @return $this
	 */
	public function addColumn($content, $width = 12, $class = '', $before = false)
	{
		$item = array('widgetId' => 'logical.html.grid.' . $width, 'content' => $content, 'class' => $class);

		if ($before)
		{
			array_unshift($this->items, $item);

			return $this;
		}

		$this->items[] = $item;

		return $this;
	}

	/**
	 * Method to add a grid to the row
	 *
	 * @param   int     $width   width of the item
	 * @param   string  $class  class to append to the grid element
	 * @param   bool    $before Should it be placed in front or back of other items
	 *
	 * @return Grid
	 */
	public function addGrid($width = 12, $class = '', $before = false)
	{
		$grid = new Grid($this->widgetRenderer, $class);

		$this->addColumn($grid, $width, $class, $before);

		return $grid;
	}
}
