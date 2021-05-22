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

class Accordion extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string the accordion id
	 */
	protected $id;

	/**
	 * @var array Items to be rendered
	 */
	protected $items = array();

	/**
	 * @var string toggle between collapse states (close, first, open)
	 */
	protected $collapse = 'first';

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface  $widgetRenderer  widget render
	 * @param   string                   $id              accordion id
	 * @param   string                   $collapse         collapes behavior state
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer, $id, $collapse = 'first')
	{
		parent::__construct($widgetRenderer);

		$this->id = (string) $id;
		$this->collapse = (string) $collapse;
	}

	/**
	 * Method to add an accordion item
	 *
	 * @param   string $id      HTML element ID
	 * @param   string $label   Link Label
	 * @param   string $content Content of the accordion
	 * @param   string $class class to add to the item
	 *
	 * @return  $this to allow for chaining
	 */
	public function addItem($id, $label, $content, $class = '')
	{
		$this->items[] = array(
			'content' => $content,
			'id'=> $id,
			'parentId' => '#' . $this->id,
			'href' => '#' . $id,
			'label' => $label,
			'class' => $class);

		return $this;
	}

	/**
	 * Method to render the grid
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if($this->collapse == 'first')
		{
			$this->items[0]['class'] .= ' in';
		}

		return $this->widgetRenderer->render('logical.html.accordion', array('items' => $this->items, 'id' => $this->id));
	}
}
