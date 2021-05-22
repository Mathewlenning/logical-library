<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Widget\Control;

// No direct access
defined('_JEXEC') or die;

use Logical\Widget\WidgetElement;
use Logical\Widget\WidgetControlInterface;
use Logical\Widget\WidgetFactoryInterface;
use Logical\Widget\WidgetRenderer;

use \SimpleXMLElement;

/**
 * Class WidgetControl
 *
 * @package  Logical\Widget\Control
 * @since    0.0.76
 */
class SelectControl implements WidgetControlInterface
{
	/**
	 * Method to execute the xml layout control
	 *
	 * @param   WidgetFactoryInterface  $factory      Calling factory
	 * @param   SimpleXMLElement        $element      <layout data-source="widgetId"/>
	 * @param   array                   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function execute(WidgetFactoryInterface $factory, SimpleXMLElement $element, $displayData = array())
	{
		$attributes = $factory->getAttributes($element, $displayData);
		$select = new WidgetElement('select', $attributes);
		$widgetRenderer = new WidgetRenderer($factory, true, $factory->getAcl());
		$select->addInnerHtml($widgetRenderer->renderElement($element, $displayData,  $factory->getAcl()));

		if (isset($displayData['selected'])
			&& $selectedOption = $select->getChildByAttribute('value', $displayData['selected']))
		{
			$selectedOption->addAttribute('selected', 'true');
		}

		return $select;
	}
}
