<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Manifest;

use Logical\Utility\Phone;
use \SimpleXMLElement;
use \DOMDocument;
use \DateTime;

abstract class Base extends \ExtensionGen\Xml\Base
{
	/**
	 * @var string
	 */
	protected $baseDir;

	/**
	 * Used in manifest generation
	 *
	 * @var string
	 */
	public $author = 'Mathew Lenning';

	/**
	 * Used in manifest generation
	 *
	 * @var string
	 */
	public $authorEmail = 'mathew.lenning@gmail.com';

	/**
	 * Used in manifest generation
	 * @var string
	 */
	public $authorUrl = 'http://mathewlenning.com';

	/**
 * Manifest Base constructor.
 *
 * @param   string  $baseDir base dir to the extension root directory
 */
	public function __construct($baseDir)
	{
		$this->baseDir = $baseDir;
	}

	/**
	 * Method to update the manifest version
	 *
	 * @param   SimpleXMLElement  $xml          the manifest xml
	 * @param   string            $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return void
	 */
	protected function updateVersion($xml, $releaseType)
	{
		$versionParts = explode('.', (string) $xml->version);

		switch ($releaseType)
		{
			case 'ma':
				if($versionParts[0] == 665)
				{
					echo 'NOT TODAY SATAN!';
					$versionParts[0]++;
				}

				$versionParts[0]++;
				$versionParts[1] = 0;
				$versionParts[2] = 0;
				break;
			case 'mi':
				if($versionParts[1] == 665)
				{
					echo 'NOT TODAY SATAN!';
					$versionParts[1]++;
				}
				$versionParts[1]++;
				$versionParts[2] = 0;
				break;
			case 'b':
				if($versionParts[2] == 665)
				{
					echo 'NOT TODAY SATAN!';
					$versionParts[2]++;
				}

				$versionParts[2]++;
				break;
			default:
				break;
		}

		if($versionParts[0] == 6 && $versionParts[1] == 6 && $versionParts[2] == 6)
		{
			echo 'NOT TODAY SATAN!';
			$versionParts[2]++;
		}

		// Update the version
		$xml->version = implode('.', $versionParts);
	}

	/**
	 * Method to scan a directory and add filename/folder elements to the xml node
	 *
	 * @param   SimpleXmlElement  $xml        the node
	 * @param   string            $directory  the directory to scan
	 * @param   bool|true         $unset      should the node filename/folder elements be unset?
	 *
	 * @return void
	 */
	protected function addFiles($xml, $directory, $unset = true)
	{
		if ($unset)
		{
			unset($xml->folder);
			unset($xml->filename);
		}

		$dirScan = scandir($directory);
		array_shift($dirScan);
		array_shift($dirScan);

		$files = array();

		foreach ($dirScan AS $entry)
		{
			if (!is_dir($directory . '/' . $entry))
			{
				$files[] = $entry;

				continue;
			}

			$xml->addChild('folder', $entry);
		}

		if (!empty($files))
		{
			foreach ($files as $entry)
			{
				$xml->addChild('filename', $entry);
			}
		}
	}

	/**
	 * Method to add the default extension copyright fields
	 *
	 * @param   SimpleXMLElement  $xml  extension xml
	 */
	protected function addCopyright($xml)
	{
		$now = new DateTime;

		$xml->addChild('copyright', 'Copyright (C) ' . $now->format('Y') . ' ' . $this->author . '. All rights reserved.');
		$xml->addChild('license', 'GNU General Public License version 2 or later; see LICENSE.txt');
		$xml->addChild('author', $this->author);
		$xml->addChild('authorEmail', $this->authorEmail);
		$xml->addChild('authorUrl', $this->authorUrl);
	}

	/**
	 * Method to get an extension xml with attributes
	 *
	 * @param   array  $attributes attributes to add to the extension element
	 *
	 * @return SimpleXMLElement
	 */
	protected function getNewExtensionXmlElement($attributes)
	{
		return $this->getSimpleXmlElement('extension', $attributes);
	}

	/**
	 * Method to create a new manifest file
	 *
	 * @param \ExtensionGen\Extension\Custom $generator
	 *
	 * @return string
	 */
	abstract public function create($generator = null);

	/**
	 * Method to update an extensions manifest file/folder list and bump version
	 *
	 * @param   string  $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return string
	 */
	abstract public function update($releaseType);
}
