<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Manifest;

use DateTime;

/**
 * Class ExtensionGen\Manifest\Library
 *
 * @since  0.0.1
 */
class Library extends Base
{
	/**
	 * @var string
	 */
	protected $libName;

	/**
	 * constructor.
	 *
	 * @param   string  $baseDir   Root of the library package
	 * @param   string  $libName   Name of the library
	 */
	public function __construct($baseDir, $libName)
	{
		parent::__construct($baseDir);

		$this->libName = strtolower($libName);
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
		$libName = $this->libName;

		$xml = $this->getSimpleXmlElement('extension',
			array(
				'type' => 'library',
				'version' => '3.0',
				'method' => 'upgrade'
			)
		);

		$xml->addChild('name', strtoupper($libName));
		$xml->addChild('libraryname', ucfirst($libName));
		$xml->addChild('description', '');
		$xml->addChild('version', '0.0.1');
		$this->addCopyright($xml);

		$now = new DateTime;
		$xml->addChild('creationDate', $now->format('Y-m-d'));

		$filesXml = $xml->addChild('files');
		$filesXml->addAttribute('folder', 'libraries/' . ucfirst($libName));
		$this->addFiles($filesXml, $this->baseDir . '/libraries/' . ucfirst($libName), false);

		$languageXml = $xml->addChild('languages');
		$enLanguageXml = $languageXml->addChild('language', 'language/en-GB/en-GB.' .$libName . '.ini');
		$enLanguageXml->addAttribute('tag', 'en-GB');

		if (!empty($generator))
		{
			$generator->onBeforeCreateManifest($xml);
		}

		$this->save($xml, $this->baseDir . '/' . $libName . '.xml');

		return 'Success';
	}

	/**
	 * Method to update a library manifest file/folder list and bump version
	 *
	 * @param   string  $releaseType  the type of release  ma = major, mi = minor, b = bug/patch
	 *
	 * @return string
	 */
	public function update($releaseType)
	{
		$xmlLocation = $this->baseDir . '/' . $this->libName .'.xml';

		$xml = simplexml_load_file($xmlLocation);

		$this->updateVersion($xml, $releaseType);

		// Update the creation date
		$now = new DateTime;
		$xml->creationDate = $now->format('Y-m-d');

		$this->addFiles($xml->files, $this->baseDir.'/libraries/' . ucfirst($this->libName));

		if (file_exists($this->baseDir . '/media'))
		{
			$this->addFiles($xml->media, $this->baseDir . '/media/'. $this->libName);
		}

		$this->save($xml, $xmlLocation);

		return (string) $xml->version;
	}
}
