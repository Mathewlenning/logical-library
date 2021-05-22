<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Widget;

// No direct access
defined('_JEXEC') or die;

use SimpleXMLElement;

/**
 * Interface WidgetControlInterface
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
interface WidgetControlInterface
{
	/**
	 * Method to handel a layout control
	 *
	 * @param   WidgetFactoryInterface  $factory      Factory caller
	 * @param   SimpleXMLElement        $element      Xml Definition
	 * @param   array                   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return mixed
	 */
	public function execute(WidgetFactoryInterface $factory, SimpleXMLElement $element, $displayData = array());
}
