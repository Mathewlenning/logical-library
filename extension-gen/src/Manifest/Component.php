<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Manifest;

use \DateTime;
/**
 * Class ExtensionGen\Manifest\Component
 *
 * @since  0.0.1
 */
class Component extends Base
{

	/**
	 * @var string
	 */
	protected $adminDir;

	/**
	 * @var string
	 */
	protected $siteDir;

	/**
	 * @var string
	 */
	protected $componentName;

	/**
	 * constructor.
	 *
	 * @param   string  $baseDir        root of the component package
	 * @param   string  $componentName  component name
	 * @param   string  $adminDir       components administrator source directory
	 * @param   string  $siteDir        components front-end source directory
	 */
	public function __construct($baseDir, $componentName, $adminDir, $siteDir)
	{
		parent::__construct($baseDir);

		$this->componentName = strtolower($componentName);

		$this->adminDir = $adminDir;
		$this->siteDir = $siteDir;
	}

	/**
	 * @param string $adminDir
	 */
	public function setAdminDir($adminDir)
	{
		$this->adminDir = $adminDir;
	}

	/**
	 * @param string $siteDir
	 */
	public function setSiteDir($siteDir)
	{
		$this->siteDir = $siteDir;
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
		$componentName = $this->componentName;

		$xml = $this->getSimpleXmlElement('extension',
			array(
				'type' => 'component',
				'version' => '3.0',
				'method' => 'upgrade'
			)
		);

		$xml->addChild('name', strtoupper('com_' . $componentName));
		$xml->addChild('description', '');
		$xml->addChild('version', '0.0.1');
		$this->addCopyright($xml);

		$now = new DateTime;
		$xml->addChild('creationDate', $now->format('Y-m-d'));

		$xml->addChild('scriptfile', 'installscript.php');

		$sqlUninstall = $xml->addChild('uninstall')->addChild('sql')->addChild('file', 'sql/uninstall.mysql.utf8.sql');
		$sqlUninstall->addAttribute('driver', 'mysql');
		$sqlUninstall->addAttribute('charset', 'utf8');

		$adminSection = $xml->addChild('administration');
		$adminSection->addChild('menu', strtoupper('com_' . $componentName));

		$adminXml = $adminSection->addChild('files');
		$adminXml->addAttribute('folder', 'administrator/components/com_' . $componentName);

		$siteXml = $xml->addChild('files');
		$siteXml->addAttribute('folder', 'components/com_' . $componentName);

		$this->addFiles($adminXml, $this->adminDir, false);
		$this->addFiles($siteXml, $this->siteDir, false);

		if (!empty($generator))
		{
			$generator->onBeforeCreateManifest($xml);
		}

		$this->save($xml, $this->baseDir . '/' . strtolower($componentName) . '.xml');

		return 'Success';
	}

	/**
	 * Method to update a components manifest file/folder list and bump version
	 *
	 * @param   string  $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return string
	 */
	public function update($releaseType)
	{
		$xmlLocation = $this->baseDir . '/' . strtolower($this->componentName) . '.xml';
		$xml = simplexml_load_file($xmlLocation);

		$this->updateVersion($xml, $releaseType);

		// Update the creation date
		$now = new DateTime;
		$xml->creationDate = $now->format('Y-m-d');

		$this->addFiles($xml->administration->files, $this->adminDir);

		if (file_exists($this->siteDir))
		{
			$this->addFiles($xml->files, $this->siteDir);
		}

		unset($xml->media);

		if (file_exists($this->baseDir . '/media'))
		{
			$mediaXml = $xml->addChild('media');
			$mediaXml->addAttribute('destination', 'com_' .$this->componentName);
			$mediaXml->addAttribute('folder', 'media/com_'.$this->componentName);

			$this->addFiles($mediaXml, $this->baseDir . '/media/com_' . $this->componentName);
		}

		$this->save($xml, $xmlLocation);

		return (string) $xml->version;
	}
}
