<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension;

use ExtensionGen\Extension\Utility\Language;

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
	 * @var Language
	 */
	protected $systemLanguage = null;

	/**
	 * @var Language
	 */
	protected $pluginLanguage = null;

	/**
	 * @return string
	 */
	public function getPlgName()
	{
		return $this->plgName;
	}

	/**
	 * @return string
	 */
	public function getPlgType()
	{
		return $this->plgType;
	}

	/**
	 * @return Language
	 */
	public function getSystemLanguage()
	{
		return $this->systemLanguage;
	}

	/**
	 * @return Language
	 */
	public function getPluginLanguage()
	{
		return $this->pluginLanguage;
	}

	/**
	 * Plugin constructor.
	 *
	 * @param   string $baseDir root of the plugin package
	 * @param   string $tmplDir Template directory
	 * @param   string $plgName Name of the plugin
	 * @param   string $plgType Type of the plugin
	 *
	 * @throws \ErrorException
	 */
	public function __construct($baseDir, $tmplDir, $plgName, $plgType)
	{
		parent::__construct($baseDir, $tmplDir);

		$this->plgName = strtolower($plgName);
		$this->plgType = strtolower($plgType);

		$this->manifest = new \ExtensionGen\Manifest\Plugin($baseDir, $plgName, $plgType);
	}

	/**
	 * Method to generate a new plugin
	 *
	 * @return bool
	 *
	 * @throws \ErrorException
	 */
	public function generate()
	{
		if(file_exists($this->baseDir . '/' . $this->plgName . '.php'))
		{
			throw new \ErrorException('Plugin manifest already exists');
		}

		$searchReplace = $this->getSearchReplace();

		$entry = $this->generateTemplate('entry.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseDir . '/' . $this->plgName . '.php', $entry);

		$this->pluginLanguage = new Language($this->baseDir, $this->tmplDir, $this,'plg_' . $this->plgType . '_' . $this->plgName);
		$this->systemLanguage= new Language($this->baseDir, $this->tmplDir, $this,'plg_' . $this->plgType . '_' . $this->plgName . '.sys');

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
				'%PlgName%',
				'%plgName%',
				'%PlgType%',
				'%plgType%',
				'%PLG_NAME%',
				'%PLG_TYPE%'
			);

		$searchReplace['replace'] = array(
			$this->capitalizeOnUnderscore($this->plgName),
			$this->plgName,
			$this->capitalizeOnUnderscore($this->plgType),
			$this->plgType,
			strtoupper($this->plgName),
			strtoupper($this->plgType)
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
		return 'plg_' . $this->plgType . '_' . $this->plgName;
	}
}
