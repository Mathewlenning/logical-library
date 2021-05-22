<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Manifest;

use DateTime;

class Module extends Base
{
	/**
	 * @var string
	 */
	protected $modName;

	/**
	 * constructor.
	 *
	 * @param   string  $baseDir   Root of the module package
	 * @param   string  $modName   Name of the module
	 */
	public function __construct($baseDir, $modName)
	{
		parent::__construct($baseDir);

		$this->modName = strtolower($modName);
	}

	/**
	 * Method to create a new manifest file
	 *
	 * @param \ExtensionGen\Extension\Custom $generator
	 *
	 * @return string
	 */
	public function create($generator = null)
	{
		$xml = $this->getSimpleXmlElement('extension',array(
			'type' => 'module',
			'version' => '3.0',
			'client' => 'site',
			'method' => 'upgrade'
		));

		// Update the creation date
		$name = $this->modName;
		$xml->addChild('name', strtoupper($name));
		$xml->addChild('description', strtoupper('MOD_' . $name .'_DESC'));
		$xml->addChild('version', '0.0.1');
		$this->addCopyright($xml);

		$now = new DateTime;
		$xml->addChild('creationDate', $now->format('Y-m-d'));

		$filesXml = $xml->addChild('files');
		$this->addFiles($filesXml, $this->baseDir, false);

		$entryFileXml = $xml->xpath('//filename[text() = "mod_' . $name . '.php"]');
		$entryFileXml[0]->addAttribute('module', 'mod_' . $name);

		$fields = $xml->addChild('config')->addChild('fields');
		$fields->addAttribute('name', 'params');
		$fields->addChild('fieldset')->addAttribute('name', 'basic');

		$advanced = $fields->addChild('fieldset');
		$advanced->addAttribute('name', 'advanced');

		$this->addChild(
			$advanced,
			'field',
			array(
				'name' => 'layout',
				'type' => 'modulelayout',
				'lable' => 'JFIELD_ALT_LAYOUT_LABEL',
				'description' => 'JFIELD_ALT_MODULE_LAYOUT_DESC'
			)
		);

		$this->addChild(
			$advanced,
			'field',
			array(
				'name' => 'moduleclass_sfx',
				'type' => 'textarea',
				'row'  => '3',
				'lable' => 'COM_MODULES_FIELD_MODULECLASS_SFX_LABEL',
				'description' => 'COM_MODULES_FIELD_MODULECLASS_SFX_DESC'
			)
		);

		if (!empty($generator))
		{
			$generator->onBeforeCreateManifest($xml);
		}

		$this->save($xml, $this->baseDir . '/mod_' .$this->modName . '.xml');

		return 'Success';
	}

	/**
	 * Method to update a module manifest file/folder list and bump version
	 *
	 * @param   string  $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return string
	 */
	public function update($releaseType)
	{
		$xmlLocation = $this->baseDir . '/mod_' .$this->modName . '.xml';

		$xml = simplexml_load_file($xmlLocation);

		$this->updateVersion($xml, $releaseType);

		// Update the creation date
		$now = new DateTime;
		$xml->creationDate = $now->format('Y-m-d');

		$this->addFiles($xml->files, $this->baseDir);
		$entryFileXml = $xml->xpath('//filename[text() = "mod_' . $this ->modName. '.php"]');
		$entryFileXml[0]->addAttribute('module', 'mod_' . $this->modName);

		$this->save($xml, $xmlLocation);

		return (string) $xml->version;
	}
}
