<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension;

abstract class Custom extends Base
{
	/**
	 * @var Base
	 */
	protected $baseGenerator;

	/**
	 * ComponentBase constructor.
	 *
	 * @param   string  $baseDir        base dir to the component root directory
	 * @param   string  $tmplDir        Template directory
	 * @param   string  $baseGenerator  primary extension generator
	 *
	 * @throws \ErrorException
	 */
	public function __construct($baseDir, $tmplDir, $baseGenerator)
	{
		parent::__construct($baseDir, $tmplDir);

		$this->baseGenerator = $baseGenerator;
	}

	/**
	 * Method to generate a new component
	 *
	 * @param $manifest
	 * @param $searchReplace
	 *
	 * @return bool
	 */
	abstract public function generate($manifest, $searchReplace);

	/**
	 * Method to prepare the manifest before saving it for the first time.
	 *
	 * @param \SimpleXMLElement $xml
	 */
	public function onBeforeCreateManifest($xml)
	{
		return;
	}

	/**
	 * Method to get search/replace for templates
	 *
	 * @return array
	 */
	public function getSearchReplace()
	{
		return $this->baseGenerator->getSearchReplace();
	}

	/**
	 * Method to get an extension prefix
	 *
	 * @return string
	 */
	public function getExtensionPrefix()
	{
		return $this->baseGenerator->getExtensionPrefix();
	}
}
