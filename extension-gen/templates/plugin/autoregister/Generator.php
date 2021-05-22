<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use ExtensionGen\Extension\Custom, ExtensionGen\Utility\Inflector;

class AutoregisterPlugin extends Custom
{
	/**
	 * @var ExtensionGen\Extension\Plugin
	 */
	protected $baseGenerator;

	/**
	 * Method to generate a new plugin
	 * @param   ExtensionGen\Manifest\Base  $manifest
	 * @param   array                       $searchReplace
	 * @return bool
	 */
	public function generate($manifest, $searchReplace)
	{
		$form = $this->generateTemplate('form.xml.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseGenerator->getBaseDir() . '/form.xml', $form);
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

		$this->addField($basicParams, 'from_name', 'text', 'From Name');
		$this->addField($basicParams, 'from_email', 'email', 'From Email');
		$this->addField($basicParams, 'subject', 'text', 'Subject');
		$this->addField($basicParams, 'body', 'editor', 'Body');

		// Add translations for params
		$pluginLanguage = $this->baseGenerator->getPluginLanguage();
		$subjectDefault = $pluginLanguage->getTranslationKey('default_subject');
		$pluginLanguage->addTranslation($subjectDefault, $subjectDefault);

		$bodyDefault = $pluginLanguage->getTranslationKey('default_body');
		$pluginLanguage->addTranslation($bodyDefault, $bodyDefault);

		//add translations for form
		$referrerNameHint = $pluginLanguage->getTranslationKey('referrer_name');
		$referrerEmailHint = $pluginLanguage->getTranslationKey('referrer_email');
		$pluginLanguage->addTranslation($referrerNameHint, 'your name');
		$pluginLanguage->addTranslation($referrerEmailHint, 'your email');

		//plugin specific
		$userExists = $pluginLanguage->getTranslationKey('user_already_exists');
		$pluginLanguage->addTranslation($userExists, 'User already exists. Please login to continue.');

		$pluginLanguage->save();
	}

	/**
	 * Method to add a field definition to the xml
	 *
	 * @param \SimpleXMLElement  $xml
	 * @param string             $name
	 * @param string             $type
	 * @param string             $translation
	 */
	protected function addField($xml, $name, $type, $translation)
	{
		$field = $xml->addChild('field');
		$field->addAttribute('name', $name);
		$field->addAttribute('type', $type);

		$systemLanguage = $this->baseGenerator->getSystemLanguage();
		$label = $systemLanguage->getTranslationKey($name);

		$systemLanguage->addTranslation($label, $translation, 'PARAMS');

		$field->addAttribute('label', $label);

		if ($type == 'editor')
		{
			$field->addAttribute('filter', 'JComponentHelper::filterText');
		}
	}
}

$className = 'AutoregisterPlugin';
