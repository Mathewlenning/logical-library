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
 * @todo add modal footer construction methods using Toolbar
 */
class Modal extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string modal DOM id
	 */
	protected $modalId;

	/**
	 * @var string/widget modal header element
	 */
	protected $modalHeader;

	/**
	 * @var string/widget modal body element
	 */
	protected $modalBody;

	/**
	 * @var string/widget modal footer element
	 */
	protected $modalFooter;

	/**
	 * @var modal wrapper class
	 */
	protected $class;

    /**
     * @var string options to use for the modal
     */
	protected $options;

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
	public function __construct(WidgetRendererInterface $widgetRenderer, $id, $header = '', $body = '', $footer ='', $class = '', $options = '')
	{
		parent::__construct($widgetRenderer);

		$this->modalId = $id;
		$this->modalHeader = $header;
		$this->modalBody = $body;
		$this->modalFooter = $footer;
		$this->class = $class;
		$this->options = $options;
	}

	/**
	 * Method to set the header content
	 *
	 * @param    \Logical\Widget\WidgetElement|string  $content  Header content
	 * @return $this
	 */
	public function setHeader($content = null)
	{
		$this->modalHeader = $content;

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
		$this->modalBody = $content;

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
		$this->modalFooter = $content;

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
		if (empty($this->modalBody))
		{
			return null;
		}

		$content = new \Logical\Widget\WidgetElement('widget');

		$content->addInnerHtml($this->widgetRenderer->render(
			'logical.modal.body',
			array(
				'modal-body' => $this->modalBody
			))
		);

		if (!empty($this->modalHeader))
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.modal.header',
					array(
						'modal-header' => $this->modalHeader,
					)
				),
				true
			);
		}

		if(!empty($this->modalFooter))
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.modal.footer',
					array(
						'modal-footer' => $this->modalFooter
					)
				)
			);
		}


		$wrapperSettings =
		$modalWrapper = $this->widgetRenderer->render(
			'logical.modal.wrapper',
			array(
				'class' => $this->class,
				'modal-id' => $this->modalId,
				'content' => $content,
                'options' => $this->options
			)
		);

		return $modalWrapper;
	}

	/**
	 * Method to render the modal control
	 *
	 * @param   string  $text     control text
	 * @param   string  $class    CSS class to add to the control
	 * @param   string  $onclick  JS method
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function renderModalControl($text, $class ='', $onclick = '')
	{
		return $this->widgetRenderer->render(
			'logical.modal.control',
			array(
				'class' => $class,
				'modal-id' => '#'. $this->modalId,
				'text' => $text,
				'onclick' => $onclick
			)
		);
	}
}
