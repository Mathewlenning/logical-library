<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Manifest;

use DateTime;

class Plugin extends Base
{
	/**
	 * @var string
	 */
	protected $plgName;

	/**
	 * @var string
	 */
	protected $plgType;

	/**
	 * constructor.
	 *
	 * @param   string  $baseDir  root of the plugin package
	 * @param   string  $plgName  Name of the plugin
	 * @param   string  $plgType  Type of the plugin
	 */
	public function __construct($baseDir, $plgName, $plgType)
	{
		parent::__construct($baseDir);

		$this->plgName = strtolower($plgName);
		$this->plgType = strtolower($plgType);
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
		$plgName = $this->plgName;
		$plgType = $this->plgType;
		$xml = $this->getSimpleXmlElement('extension',
			array(
				'type' => 'plugin',
				'group' => $plgType,
				'version' => '3.0',
				'method' => 'upgrade'
			)
		);

		$xml->addChild('name', strtoupper('plg_' . $plgType . '_' . $plgName));
		$xml->addChild('description',  strtoupper('plg_' . $plgType . '_' . $plgName .'_DESC'));
		$xml->addChild('version', '0.0.1');
		$this->addCopyright($xml);

		// Update the creation date
		$now = new DateTime;
		$xml->addChild('creationDate', $now->format('Y-m-d'));

		$filesXml = $xml->addChild('files');
		$this->addFiles($filesXml, $this->baseDir, false);

		$entryFileXml = $xml->xpath('//filename[text() = "' . $plgName . '.php"]');
		$entryFileXml[0]->addAttribute('plugin', $plgName);

		if (!empty($generator))
		{
			$generator->onBeforeCreateManifest($xml);
		}

		$this->save($xml, $this->baseDir . '/' . strtolower($plgName) . '.xml');

		return 'Success';
	}

	/**
	 * Method to update a plugin manifest file/folder list and bump version
	 *
	 * @param   string  $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return string
	 */
	public function update($releaseType)
	{
		$xmlLocation = $this->baseDir . '/' . strtolower($this->plgName) . '.xml';
		$xml = simplexml_load_file($xmlLocation);

		$this->updateVersion($xml, $releaseType);

		// Update the creation date
		$now = new DateTime;
		$xml->creationDate = $now->format('Y-m-d');

		$this->addFiles($xml->files, $this->baseDir);

		$entryFileXml = $xml->xpath('//filename[text() = "' . $this->plgName . '.php"]');
		$entryFileXml[0]->addAttribute('plugin', $this->plgName);

		$this->save($xml, $xmlLocation);

		return (string) $xml->version;
	}
}
