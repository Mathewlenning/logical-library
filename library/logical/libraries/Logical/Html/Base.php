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

abstract class Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface  $widgetRenderer  widget render
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer)
	{
		$this->widgetRenderer = $widgetRenderer;
	}

	/**
	 * Magic method to render the class as a string.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return (string) $this->render();
	}

	/**
	 * Method to render the element
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	abstract public function render();
}
