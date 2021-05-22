<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use ExtensionGen\Extension\Custom, ExtensionGen\Utility\Inflector;

class EmailPlugin extends Custom
{
	/**
	 * @var ExtensionGen\Extension\Plugin
	 */
	protected $baseGenerator;

	/**
	 * Method to generate a new plugin
	 *
	 * @return bool
	 */
	public function generate($manifest, $searchReplace)
	{
		return true;
	}

	/**
	 * Method to prepare the manifest before saving it for the first time.
	 *
	 * @param \SimpleXMLElement $xml
	 */
	public function onBeforeCreateManifest($xml)
	{
		$params = $xml->addChild('config')->addChild('fields');
		$params->addAttribute('name', 'params');

		$basicParams = $params->addChild('fieldset');
		$basicParams->addAttribute('name', 'basic');

		$this->addField($basicParams, 'from_name', 'text', false);
		$this->addField($basicParams, 'from_email', 'email', false);
		$this->addField($basicParams, 'subject', 'text');
		$this->addField($basicParams, 'body', 'editor');

		$systemLanguage = $this->baseGenerator->getSystemLanguage();
		$subjectDefault = $systemLanguage->getTranslationKey('default_subject');
		$systemLanguage->addTranslation($subjectDefault, $subjectDefault, 'EXTENSION');

		$bodyDefault = $systemLanguage->getTranslationKey('default_body');
		$systemLanguage->addTranslation($bodyDefault, $bodyDefault, 'EXTENSION');

		$systemLanguage->save();
	}

	/**
	 * Method to add a field definition to the xml
	 *
	 * @param   \SimpleXMLElement  $xml
	 * @param   string             $name
	 * @param   string             $type
	 * @param   bool               $addDesc
	 */
	protected function addField($xml, $name, $type, $addDesc = true)
	{
		$field = $xml->addChild('field');
		$field->addAttribute('name', $name);
		$field->addAttribute('type', $type);

		$systemLanguage = $this->baseGenerator->getSystemLanguage();
		$label = $systemLanguage->getTranslationKey($name);
		$systemLanguage->addTranslation($label, $label, 'PARAMS');
		$field->addAttribute('label', $label);

		if ($addDesc)
		{
			$desc = $systemLanguage->getTranslationKey('desc_' . $name);
			$systemLanguage->addTranslation($desc, $desc, 'PARAMS');
			$field->addAttribute('description', $desc);
		}

		if ($type == 'editor')
		{
			$field->addAttribute('filter', 'JComponentHelper::filterText');
		}
	}

}

$className = 'EmailPlugin';
