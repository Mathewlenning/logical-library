<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Form;

use ExtensionGen\Extension\Utility\Language;

class Base extends \ExtensionGen\Xml\Base
{
	/**
	 * @var string
	 */
	protected $formDir;

	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var string
	 */
	protected $fullPath;

	/**
	 * @var \SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Manifest Base constructor.
	 *
	 * @param   string  $formDir  dir to the form root directory
	 * @param   string  $fileName Name of the xml file
	 */
	public function __construct($formDir, $fileName)
	{
		$this->formDir = $formDir;

		if(!file_exists($formDir))
		{
			mkdir($formDir, '0777', true);
		}

		$this->fileName = $fileName;
		$this->fullPath = $formDir . '/' . $fileName .'.xml';

		if (!file_exists($this->fullPath))
		{
			$xml = new \SimpleXMLElement('<form></form>');
			$this->save($xml, $this->fullPath);
		}

		$this->xml = simplexml_load_file($this->fullPath);
	}

	/**
	 * Method to get the form XML element
	 *
	 * @return \SimpleXMLElement
	 */
	public function getFormXml()
	{
		return $this->xml;
	}

	/**
	 * Method to add form fields to a fieldset
	 *
	 * @param   string    $name  name of the fieldset
	 * @param   Language  $lang  language class
	 * @param   array    $fields array of fields to add
	 *
	 * @return void
	 */
	public function addFieldset($name, Language $lang, $fields = array())
	{
		$fieldSet = $this->xml->xpath("./fieldset[@name='" . $name . "']");
		$langSection = strtoupper($this->fileName) . ' FORM ' . strtoupper($name);

		if(empty($fieldSet))
		{
			$fieldSet = $this->addChild(
				$this->xml,
				'fieldset',
				array(
					'name' => $name
				)
			);
		}

		if(is_array($fieldSet))
		{
			$fieldSet = $fieldSet[0];
		}

		$fields = $this->normalizeFields($fields);

		foreach ($fields AS $field)
		{
			$fieldXml =  $fieldSet->xpath("./field[@name='" . $field['name'] . "']");

			if (!empty($fieldXml))
			{
				continue;
			}

			$label = $lang->getTranslationKey($field['name']);
			$formFieldType = $this->getFormFieldType($field['name'],$field['type']);

			$lang->addTranslation($label, $label, $langSection);

			$fieldXml = $this->addChild(
				$fieldSet,
				'field',
				array(
					'name' => $field['name'],
					'type' => $formFieldType,
					'label' => $label
				)
			);


			if (!empty($field['options']))
			{
				foreach ($field['options'] AS $option)
				{
					$optionTranslation = $lang->getTranslationKey($option);
					$lang->addTranslation($optionTranslation, $optionTranslation, $langSection);

					$this->addChild(
						$fieldXml,
						'option',
						array(
							'value' => $option
						),
						$optionTranslation
					);
				}
			}


			if ($formFieldType == 'editor')
			{
				$fieldXml->addAttribute('filter', 'JComponentHelper::filterText');
			}
		}

		$lang->save();
		$this->save($this->xml, $this->fullPath);
	}

	/**
	 * Method to normalize the fields data structure
	 *
	 * @param   array  $fields  array of field definitions
	 *
	 * @return array   of structured arrays
	 */
	protected function normalizeFields($fields)
	{
		$normalFields = array();

		foreach ($fields AS $field)
		{
			if(is_array($field))
			{
				$normalFields[] = $field;

				continue;
			}

			$normalFields[] = array('name' => $field, 'type' => 'varchar');
		}

		return $normalFields;
	}

	/**
	 * Method to guess the JFormField type by field type and name
	 *
	 * @param   string  $name  name of the field
	 * @param   string  $type  sql field type
	 *
	 * @return string
	 */
	protected function getFormFieldType($name, $type)
	{
		switch ($type)
		{
			case 'enum':
				$formFieldType = 'radio';
				break;
			case 'datetime':
				$formFieldType = 'calendar';
				break;
			case 'text':
			case 'mediumtext':
			case 'longtext':
				$formFieldType = 'editor';

				if(strpos($name, 'note') !== false)
				{
					$formFieldType = 'textarea';
				}
				break;
			case 'int':
				$formFieldType = 'number';

				if ($name == 'user_id')
				{
					$formFieldType = 'user';
				}
				elseif(strpos($name, '_id') !== false)
				{
					$formFieldType = 'hidden';
				}
				break;
			default:
				$formFieldType = 'text';

				if (strpos($name, 'email') !== false)
				{
					$formFieldType = 'email';
				}
		}

		return $formFieldType;
	}
}
