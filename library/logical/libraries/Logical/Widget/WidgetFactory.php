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

use Logical\Registry\Registry;
use Logical\Access\AccessInterface;

use SimpleXMLElement;
use JText;

/**
 * Class WidgetFactory
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
class WidgetFactory implements WidgetFactoryInterface
{
	/**
	 * Rendering Controls
	 * @var array
	 */
	protected $controls = array();

	/**
	 * Data to be used to render the widget
	 *
	 * @var Registry
	 */
	protected $displayData;

	/**
	 * Object to check ACL permissions
	 *
	 * @var AccessInterface
	 */
	protected $acl;

	/**
	 * Convenience method to bulk load controls
	 *
	 * @param   array  $controls  associative array of controls Format: array(tagName => handlerClassName)
	 *
	 * @return $this
	 */
	public function setControls($controls = array())
	{
		foreach ($controls AS $tagName => $handler)
		{
			$this->addControl($tagName, $handler);
		}

		return $this;
	}

	/**
	 * Method to add a control handler to the controls array
	 *
	 * @param   string  $tagName  Name of the xml tag to render with this control
	 * @param   string  $handler  Name of the Control class
	 *
	 * @return $this to allow for chaining
	 */
	public function addControl($tagName, $handler)
	{
		if (!class_exists($handler))
		{
			throw new \InvalidArgumentException(JText::sprintf('LOGICAL_WIDGET_FACTORY_ERROR_CONTROL_NOT_FOUND', $handler));
		}

		$handlerInstance = new $handler;

		if (!($handlerInstance instanceof WidgetControlInterface))
		{
			throw new \InvalidArgumentException(JText::sprintf('LOGICAL_WIDGET_FACTORY_ERROR_CONTROL_MUST_IMPLEMENT_WIDGET_CONTROL_INTERFACE', $handler));
		}

		$this->controls[$tagName] = $handlerInstance;

		return $this;
	}

	/**
	 * Method to get the ACL object
	 *
	 * @return AccessInterface
	 */
	public function getACL()
	{
		return $this->acl;
	}

	/**
	 * Method to convert an SimpleXmlElement into a HtmlElement
	 *
	 * @param   SimpleXMLElement  $xmlTemplate  XML layout definition
	 * @param   AccessInterface   $acl          Used for checking ACL
	 * @param   array|Registry    $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function convertToElement(SimpleXMLElement $xmlTemplate, AccessInterface $acl, $displayData = array())
	{
		if (empty($displayData))
		{
			$displayData = $this->displayData;
		}

		$this->acl = $acl;

		$widget = new WidgetElement('widget');

		foreach ($xmlTemplate as $element)
		{
			$widget->addInnerHtml($this->build($element, $displayData));
		}

		return $widget;
	}

	/**
	 * Method to build a HtmlElement
	 *
	 * @param   SimpleXMLElement  $element      XML definition
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return HtmlElement
	 */
	public function build(SimpleXMLElement $element, $displayData = array())
	{
		$tagName = $element->getName();

		if (!$this->hasPermission($element, $displayData))
		{
			return '';
		}

		if (array_key_exists($tagName, $this->controls))
		{
			return $this->executeControl($tagName, $element, $displayData);
		}

		$attributes = $this->getAttributes($element, $displayData);
		$htmlElement = new WidgetElement($tagName, $attributes);

		if ($element->count() === 0)
		{
			$htmlElement->addInnerHtml(trim((string) $element));

			return $htmlElement;
		}

		foreach ($element AS $child)
		{
			$htmlElement->addInnerHtml($this->build($child, $displayData));
		}

		return $htmlElement;
	}

	/**
	 * Method to check if the current user has permission to see the element
	 *
	 * @param   SimpleXMLElement  $element      XML definition
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return bool
	 */
	private function hasPermission(SimpleXMLElement $element, $displayData = array())
	{
		if (!isset($element['data-action']))
		{
			return true;
		}

		$action = $this->getVariable((string) $element['data-action'], $displayData);

		$assetName = null;

		if (isset($element['data-asset']))
		{
			$assetName = $this->getVariable((string) $element['data-asset'], $displayData);
		}

		return $this->acl->allowAction($action, $assetName, $displayData);
	}

	/**
	 * Method to execute a control method
	 *
	 * @param   string            $tagName      the name of the XML tag referenced by the SimpleXMLElement object
	 * @param   SimpleXMLElement  $element      xml object
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return mixed result of the control
	 */
	private function executeControl($tagName, SimpleXMLElement $element, $displayData = array())
	{
		/** @var WidgetControlInterface $control */
		$control = $this->controls[$tagName];

		return $control->execute($this, $element, $displayData);
	}

	/**
	 * Method to get the attributes of an SimpleXmlElement
	 * And replace "@variableName" with values from the displayData
	 *
	 * @param   SimpleXMLElement  $element      Element definition
	 * @param   array             $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function getAttributes(SimpleXMLElement $element, $displayData = array())
	{
		$attributes = array();

		$cellAttributes = $element->attributes();

		if ($cellAttributes->count() != 0)
		{
			$attrArray = (array) $cellAttributes;

			// Bad form, but it saved an iteration cycle
			$attributes = $attrArray['@attributes'];
		}

		foreach ($attributes AS $key => $attribute)
		{
			if ($key === 'class')
			{
				$attributes[$key] = $this->getClassData($attribute, $displayData);
				continue;
			}

			if ($key == 'data-action' || $key == 'data-asset')
			{
				unset($attributes[$key]);
				continue;
			}

			$attributes[$key] = $this->getVariable($attribute, $displayData);

			if ($key == 'title' || $key == 'placeholder')
			{
				$attributes[$key] = JText::_($attribute);
			}
		}

		return $attributes;
	}

	/**
	 * Method to handle the class attribute because it is a special case
	 *
	 * @param   string  $classes      Space delimited string containing element class values
	 * @param   array   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return string
	 */
	private function getClassData($classes, $displayData = array())
	{
		if (strpos($classes, '@') === false)
		{
			return $classes;
		}

		$classes = explode(' ', $classes);

		foreach ($classes AS &$class)
		{
			$class = $this->getVariable($class, $displayData);
		}

		return implode(' ', $classes);
	}

	/**
	 * Method to get a variable
	 *
	 * @param   string        $name         Name of the variable to get from the displayData prefixed with @ symbol I.E. @name = $displayData[name]
	 * @param   array|object  $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return mixed
	 */
	public function getVariable($name, $displayData = array())
	{
		if (preg_match('/(^|[\W\s\b])@.*/',$name) == false)
		{
			return $name;
		}

		$key = substr($name, 1);

		if (is_object($displayData))
		{
			return $displayData->{$key};
		}

		if (isset($displayData[$key]))
		{
			return $displayData[$key];
		}

		return '';
	}
}
