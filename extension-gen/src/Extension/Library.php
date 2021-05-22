<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension;

use ExtensionGen\Extension\Utility\Language;

class Library extends Base
{
	/**
	 * @var string
	 */
	protected $libName;

	/**
	 * Library constructor.
	 *
	 * @param   string $baseDir base dir to the component root directory
	 * @param   string $tmplDir Template directory
	 * @param   string $libName Library Name
	 */
	public function __construct($baseDir, $tmplDir, $libName)
	{
		parent::__construct($baseDir, $tmplDir);

		$this->libName = strtolower($libName);
		$this->manifest = new \ExtensionGen\Manifest\Library($baseDir, $libName);
	}

	/**
	 * Method to generate a new component
	 *
	 * @return bool
	 */
	public function generate()
	{
		$this->preparePath($this->baseDir.'/libraries/'. ucfirst($this->libName));
		new Language($this->baseDir, $this->tmplDir, $this, strtolower($this->libName));

		$searchReplace = $this->getSearchReplace();
		$this->executeGenerator('generate', array($this->manifest, $searchReplace));
		$this->manifest->create($this->generator);

		return true;
	}

	/**
	 * Method to get search/replace for templates
	 *
	 * @return array
	 */
	public function getSearchReplace()
	{
		$searchReplace = array();
		$searchReplace['search'] =
			array(
				'%LibName%',
				'%libName%',
				'%LIB_NAME%'
			);

		$searchReplace['replace'] = array(
			ucfirst($this->libName),
			$this->libName,
			strtoupper($this->libName),
		);
		return $searchReplace;
	}

	/**
	 * Method to get an extension prefix
	 *
	 * @return string
	 */
	public function getExtensionPrefix()
	{
		return $this->libName;
	}
}
