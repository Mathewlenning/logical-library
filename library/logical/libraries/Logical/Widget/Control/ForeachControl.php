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
use Logical\Registry\Registry;

use \SimpleXMLElement;

/**
 * Class ForeachControl
 *
 * @package  Logical\Widget\Control
 * @since    0.0.25
 */
class ForeachControl implements WidgetControlInterface
{
	/**
	 * Method to handle a foreach xml control element
	 *
	 * @param   WidgetFactoryInterface  $factory      Calling factory
	 * @param   SimpleXMLElement        $element      <foreach data-source="@variableName"> ... </foreach>
	 * @param   array                   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return WidgetElement
	 */
	public function execute(WidgetFactoryInterface  $factory, SimpleXMLElement $element, $displayData = array())
	{
		$dataSource = $factory->getVariable($element['data-source'], $displayData);

		$innerHtml = new WidgetElement('widget');

		foreach ($dataSource AS $index => $data)
		{
			foreach ($element AS $child)
			{
				if (!($data instanceof Registry) && !is_array($data))
				{
					$data = new Registry($data);
					$data->loopIndex = $index;
				}

				$innerHtml->addInnerHtml($factory->build($child, $data));
			}
		}

		return $innerHtml;
	}
}
