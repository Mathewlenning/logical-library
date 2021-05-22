<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension;

use ExtensionGen\Extension\Utility\Language;

class Module extends Base
{
	/**
	 * @var string
	 */
	protected $modName;

	/**
	 * Module constructor.
	 *
	 * @param   string  $baseDir  root of the module package
	 * @param   string  $tmplDir  Template directory
	 * @param   string  $modName  Name of the module
	 */
	public function __construct($baseDir, $tmplDir, $modName)
	{
		parent::__construct($baseDir, $tmplDir);

		$this->modName = strtolower($modName);
		$this->manifest = new \ExtensionGen\Manifest\Module($baseDir, $modName);
	}

	/**
	 * Method to generate a new module
	 *
	 * @return bool
	 */
	public function generate()
	{
		$searchReplace = $this->getSearchReplace();

		$entry = $this->generateTemplate('entry.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseDir . '/mod_' . $this->modName . '.php', $entry);

		$helper = $this->generateTemplate('helper.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseDir . '/helper.php', $helper);

		$this->preparePath($this->baseDir . '/tmpl');
		$tmplDefault =  $this->generateTemplate('tmplDefault.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseDir . '/tmpl/default.php', $tmplDefault);

		new Language($this->baseDir, $this->tmplDir, $this, 'mod_' . $this->modName);
		new Language($this->baseDir, $this->tmplDir, $this, 'mod_' . $this->modName . '.sys');

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
				'%ModName%',
				'%modName%',
				'%MOD_NAME%'
			);

		$searchReplace['replace'] = array(
			$this->capitalizeOnUnderscore($this->modName),
			$this->modName,
			strtoupper($this->modName),

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
		return 'mod_'.$this->modName;
	}
}
