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

use Logical\Widget\WidgetControlInterface;
use Logical\Widget\WidgetFactoryInterface;
use \SimpleXMLElement;
use JText;

/**
 * Class VarControl
 *
 * @package  Logical\Widget\Control
 * @since    0.0.25
 */
class VarControl implements WidgetControlInterface
{
	/**
	 * Method to execute an inline var control
	 *
	 * @param   WidgetFactoryInterface  $factory      Calling factory
	 * @param   SimpleXMLElement        $element      <var data-source="@variableName"/>
	 * @param   array                   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function execute(WidgetFactoryInterface $factory, SimpleXMLElement $element, $displayData = array())
	{
		$var = $factory->getVariable($element['data-source'], $displayData);

		if (isset($element['translate']))
		{
			$var = JText::_($var);
		}

		if (isset($element['raw']))
		{
			return $var;
		}

		return htmlentities($var, ENT_COMPAT, 'UTF-8', false);
	}
}
