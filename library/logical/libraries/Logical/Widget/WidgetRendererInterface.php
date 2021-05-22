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

use Logical\Access\AccessInterface;
use SimpleXMLElement;


/**
 * Interface WidgetInterface
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
interface WidgetRendererInterface
{
	/**
	 * Method to set the paths to search for templates
	 *
	 * @param   mixed  $paths  a string or an array of strings containing base paths to look for widget templates
	 *
	 * @return void
	 */
	public static function setSearchPaths($paths);

	/**
	 * Method to get the search paths
	 *
	 * @return array
	 */
	public function getSearchPaths();

	/**
	 * Method to convert a widget template into an HtmlElement.
	 *
	 * @param   string           $widgetId     Dot separated path to the widget template, relative to base path
	 * @param   array            $displayData  Object which properties are used inside the widget template
	 * @param   AccessInterface  $acl          Used for checking ACL
	 *
	 * @return  WidgetElement The converted layout object.
	 */
	public function render($widgetId, $displayData = array(), AccessInterface $acl = null);

	/**
	 * Method to convert an SimpleXml Element into an HtmlElement
	 *
	 * @param   SimpleXMLElement  $element      xml template instance
	 * @param   array             $displayData  Object which properties are used inside the widget template
	 * @param   AccessInterface   $acl          Used for checking ACL
	 *
	 * @return array
	 */
	public function renderElement(SimpleXMLElement $element, $displayData, AccessInterface $acl);
}
