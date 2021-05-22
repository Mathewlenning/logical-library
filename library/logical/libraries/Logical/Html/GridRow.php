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

class GridRow extends GridBase
{
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

		return $this->widgetRenderer->render('logical.html.grid.row', array('items' => $this->items, 'class' => $this->class));
	}
}
