<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Xml;

use SimpleXMLElement;

class Base
{
	/**
	 * Method to get a new xml element with attributes
	 *
	 * @param   string  $rootElementName  name of the root element
	 * @param   array   $attributes       attributes to add to the extension element
	 *
	 * @return SimpleXMLElement
	 */
	protected function getSimpleXmlElement($rootElementName, $attributes)
	{
		$xml = new SimpleXMLElement('<' . $rootElementName . '></' .  $rootElementName . '>');

		foreach ($attributes AS $name => $value)
		{
			$xml->addAttribute($name, $value);
		}

		return $xml;
	}

	/**
	 * Method to add a child node with attributes
	 *
	 * @param   SimpleXMLElement  $xml         parent node
	 * @param   string            $tagName     name of the new node
	 * @param   array             $attributes  array of attributes to add to new node
	 * @param   string            $innerValue  optional inner value for the node
	 *
	 * @return SimpleXMLElement
	 */
	protected function addChild($xml, $tagName ,$attributes, $innerValue = null)
	{
		$child = $xml->addChild($tagName, $innerValue);

		foreach ($attributes AS $name => $value)
		{
			if(is_array($value))
			{
				$this->addChildren($child, $name, $value);

				continue;
			}

			$child->addAttribute($name, $value);
		}

		return $child;
	}

	/**
	 * Method to add multiple child nodes to parent node
	 *
	 * @param   SimpleXMLElement  $xml        parent node
	 * @param   string            $tagName     name of the new node
	 * @param   array             $attributes  array of attributes to set for each child I.E. array(array(value=>"1",label=>"something"))
	 */
	protected functIon addChildren($xml, $tagName, $attributes = array())
	{
		foreach ($attributes AS $options)
		{
			$this->addChild($xml, $tagName, (array) $options);
		}
	}

	/**
	 * Method to save an xml element to file and remove whitespace
	 *
	 * @param   SimpleXMLElement  $xml       the node
	 * @param   string            $fileName  the file name
	 *
	 * @return void
	 */
	protected function save(SimpleXMLElement $xml, $fileName)
	{
		$dom = new \DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$dom->save($fileName);
	}

	/**
	 * Method to add CDATA to the xml object.
	 *
	 * @param   \SimpleXMLElement  $parent  the parent node
	 * @param   string             $name    the name of the child node
	 * @param   string             $value   the value of the child node
	 *
	 * @return void
	 */
	protected function createCdataSection($parent, $name, $value)
	{
		$child = $parent->addChild($name);
		$childNode = dom_import_simplexml($child);
		$childOwner = $childNode->ownerDocument;
		$childNode->appendChild($childOwner->createCDATASection($value));
	}
}
