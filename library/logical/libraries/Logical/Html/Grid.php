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

class Grid extends GridBase
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
	 * Method to add a row to the grid
	 *
	 * @param   string  $class   class to append to the row element
	 * @param   bool    $before  Should it be placed in front or back of other items
	 *
	 * @return GridRow
	 */
	public function addRow($width = 12, $class = '', $before = false)
	{
		$row = new GridRow($this->widgetRenderer, $class);
		$this->addColumn($row, $width,'', $before);

		return $row;
	}

	/**
	 * Method to render the grid
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		foreach ($this->items AS &$item)
		{
			if ($item['content'] instanceof GridBase)
			{
				$item['content'] = $item['content']->render();
			}
		}

		return $this->widgetRenderer->render('logical.html.grid', array('items' => $this->items, 'class' => $this->class));
	}
}
