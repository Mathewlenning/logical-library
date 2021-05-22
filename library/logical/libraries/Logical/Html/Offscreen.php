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
 * Class Tabs
 *
 * @package  Logical\Html
 * @since    0.0.170
 *
 */
class Offscreen extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string container DOM id
	 */
	protected $id;

	/**
	 * @var string/widget header element
	 */
	protected $header;

	/**
	 * @var string/widget  body element
	 */
	protected $body;

	/**
	 * @var string/widget footer element
	 */
	protected $footer;

	/**
	 * @var modal wrapper class
	 */
	protected $class;

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface              $widgetRenderer widget render
	 * @param   string                               $id             DOM ID
	 * @param   \Logical\Widget\WidgetElement|string $header         Header content
	 * @param   \Logical\Widget\WidgetElement|string $body           Body content
	 * @param   \Logical\Widget\WidgetElement|string $footer         Footer content
	 * @param   string                               $class          wrapper class
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer, $id, $header = '', $body = '', $footer ='', $class = 'logical-offscreen-left logical-offscreen-hidden')
	{
		parent::__construct($widgetRenderer);

		$this->id = $id;
		$this->header = $header;
		$this->body = $body;
		$this->footer = $footer;
		$this->class = $class;
	}

	/**
	 * Method to set the header content
	 *
	 * @param    \Logical\Widget\WidgetElement|string  $content  Header content
	 * @return $this
	 */
	public function setHeader($content = null)
	{
		$this->header = $content;

		return $this;
	}

	/**
	 * Method to set the body content
	 *
	 * @param   \Logical\Widget\WidgetElement|string  $content  Body content
	 *
	 * @return $this
	 */
	public function setBody($content)
	{
		$this->body = $content;

		return $this;
	}

	/**
	 * Method to set the footer content
	 *
	 * @param   \Logical\Widget\WidgetElement|string  $content  Footer content
	 *
	 * @return $this
	 */
	public function setModalFooter($content = null)
	{
		$this->footer = $content;

		return $this;
	}

	public function setClasses($classes)
	{
		if (!is_array($classes))
		{
			$classes = array($classes);
		}

		$this->setClass(implode(' ', $classes));

		return $this;
	}

	public function setClass($class)
	{
		$this->class = $class;
	}

	/**
	 * Method to render the modal window
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if (empty($this->body))
		{
			return null;
		}

		$content = new \Logical\Widget\WidgetElement('widget');

		$content->addInnerHtml($this->widgetRenderer->render(
			'logical.offscreen.body',
			array(
				'body' => $this->body
			))
		);

		if (!empty($this->header))
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.offscreen.header',
					array(
						'header' => $this->header,
					)
				),
				true
			);
		}

		if(!empty($this->footer))
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.offscreen.footer',
					array(
						'footer' => $this->footer
					)
				)
			);
		}


		$wrapper = $this->widgetRenderer->render(
			'logical.offscreen.wrapper',
			array(
				'class' => $this->class,
				'id' => $this->id,
				'content' => $content
			)
		);

		return $wrapper;
	}
}
