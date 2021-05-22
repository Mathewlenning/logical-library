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
 * Interface WidgetFactoryInterface
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
interface WidgetFactoryInterface
{
	/**
	 * Method to add a control handler to the controls array
	 *
	 * @param   string  $tagName  Name of the xml tag to render with this control
	 * @param   string  $handler  Name of the Control class
	 *
	 * @return $this to allow for chaining
	 */
	public function addControl($tagName, $handler);

	/**
	 * Method to get the ACL object
	 *
	 * @return AccessInterface
	 */
	public function getACL();

	/**
	 * Method to convert an SimpleXmlElement into a HtmlElement
	 *
	 * @param   SimpleXMLElement  $xmlTemplate  XML layout definition
	 * @param   AccessInterface   $acl          Used for checking ACL
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function convertToElement(SimpleXMLElement $xmlTemplate, AccessInterface $acl, $displayData = array());

	/**
	 * Method to build a HtmlElement
	 *
	 * @param   SimpleXMLElement  $element      XML definition
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return \Logical\Widget\HtmlElement
	 */
	public function build(SimpleXMLElement $element, $displayData = array());

	/**
	 * Method to get the attributes of an SimpleXmlElement
	 * And replace "@variableName" with values from the displayData
	 *
	 * @param   SimpleXMLElement  $element      Element definition
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function getAttributes(SimpleXMLElement $element, $displayData = array());

	/**
	 * Method to get a variable
	 *
	 * @param   string  $name         Name of the variable to get from the displayData prefixed with @ symbol I.E. @name = $displayData[name]
	 * @param   array   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return mixed
	 */
	public function getVariable($name, $displayData = array());
}
