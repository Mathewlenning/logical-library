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
 */
class Tabs extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var array of tab definitions
	 */
	protected $tabs = array();

	/**
	 * Method to add a tab to the tab
	 *
	 * @param   string  $targetId  Tab-panel target
	 * @param   string  $label     Label used in the tab toggle
	 * @param   string  $content   Content of the tab
	 * @param   bool    $active    Is this tab the active tab?
	 *
	 * @return $this
	 */
	public function addTab($targetId, $label, $content, $active = false)
	{
		if (!empty($this->tabs[$targetId]))
		{
			throw new \InvalidArgumentException(\JText::_('LOGICAL_HTML_ERROR_TAB_ID_MUST_BE_UNIQUE'));
		}

		$tab = array('id' => $targetId, 'href' => '#' . $targetId , 'label' => $label, 'content' => $content);

		if ($active)
		{
			$tab['class'] = 'active';
		}

		$this->tabs[$targetId] = $tab;

		return $this;
	}

	/**
	 * Method to render the tab set
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if (count($this->tabs) <= 0)
		{
			return null;
		}

		$content = new \Logical\Widget\WidgetElement('widget');

		$content->addInnerHtml($this->widgetRenderer->render(
			'logical.html.tabs.controls',
			array(
				'tabs' => $this->tabs
			))
		);

		$content->addInnerHtml($this->widgetRenderer->render(
			'logical.html.tabs.panes',
			array(
				'tabs' => $this->tabs
			))
		);

		return $this->widgetRenderer->render('logical.html.tabs', array('content' => $content));
	}
}
