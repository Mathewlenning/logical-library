<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\View;

// No direct access
use Logical\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Class XmlView
 *
 * @package  Logical\View
 * @since    0.0.188
 */
class XmlView extends RecordView
{
	/**
	 * @var array of db field values that should be rendered as CDATA
	 */
	protected $cDataTypes = array();

	/**
	 * Constructor
	 * prepares the base form URL from the given config
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
	{

		parent::__construct($config);

		$this->layout = 'xml_' . $this->layout;
		$this->cDataTypes = array('varchar','text', 'mediumtext', 'longtext', 'blob', 'mediumblob', 'longblob');
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

	/**
	 * Method to build s simpleXMLElement wit ha root node
	 *
	 * @param   string  $name  name of the root node
	 *
	 * @return \SimpleXMLElement
	 */
	protected function getXmlElement($name)
	{
		return simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?><'. $name .'></'. $name .'>');
	}

	/**
	 * Method to check if a record property should be converted to CDATA
	 *
	 * @param   string  $name  name of the table field
	 *
	 * @return bool
	 */
	protected function isCDATA($name)
	{
		$fieldsData = $this->loadFieldData();

		if (empty($fieldsData[$name]))
		{
			return true;
		}

		$type = $fieldsData[$name]->Type;

		return in_array(strtolower($type), $this->cDataTypes);
	}
}
