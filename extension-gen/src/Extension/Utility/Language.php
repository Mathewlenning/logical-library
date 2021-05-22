<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension\Utility;

use ExtensionGen\Extension\Base, ExtensionGen\Utility\Inflector;

class Language extends Base
{
	/**
	 * @var Base parent extension class
	 */
	protected $baseGenerator;

	/**
	 * @var string
	 */
	protected $langFile;

	/**
	 * @var string
	 */
	protected $lang;

	/**
	 * @var string
	 */
	protected $fullPath;

	/**
	 * @var array
	 */
	protected $translations = array();

	/**
	 * Language utility constructor.
	 *
	 * @param   string  $baseDir        base dir to the extension root directory
	 * @param   string  $tmplDir        Template directory
	 * @param   Base    $baseGenerator  primary extension generator
	 * @param   string  $langFileName   Name of the file w/o language prefix or file suffix
	 * @param   string  $lang           Language Code
	 */
	public function __construct($baseDir, $tmplDir, $baseGenerator, $langFileName, $lang = 'en-GB')
	{
		parent::__construct($baseDir .'/language/' . $lang, $tmplDir);

		$this->baseGenerator = $baseGenerator;

		$this->langFile = $lang . '.' . $langFileName .'.ini';
		$this->lang = $lang;

		$this->fullPath = $this->baseDir . '/' . $this->langFile;

		if(!file_exists($this->fullPath))
		{
			$searchReplace = $this->getSearchReplace();

			$langInit = $this->generateTemplate('lang.ini.txt', $searchReplace['search'], $searchReplace['replace']);
			file_put_contents($this->fullPath, $langInit);
		}

		$this->translations = parse_ini_file($this->fullPath, true);
	}

	/**
	 * Proxy to the baseGenerator search/replace method
	 *
	 * @return array
	 */
	public function getSearchReplace()
	{
		return $this->baseGenerator->getSearchReplace();
	}

	/**
	 * Method to add a translation string
	 *
	 * @param   string  $key     translation key
	 * @param   string  $value   translation value
	 * @param   string  $section  section to put it in
	 */
	public function addTranslation($key, $value, $section = 'Common')
	{
		if(!array_key_exists($section, $this->translations))
		{
			$this->translations[$section] = array();
		}

		$this->translations[$section][$key] = $value;
	}

	/**
	 * Method to save the language file
	 *
	 * @return bool
	 */
	public function save()
	{
		$search = $this->getSearchReplace();
		$content = $this->generateTemplate('lang.ini.txt', $search['search'], $search['replace']) ."\n";
		$content .= $this->translationsToString($this->translations);

		file_put_contents($this->fullPath, $content);
		return true;
	}

	/**
	 * Method to convert the translations array into a string
	 *
	 * @param   array  $translations  associative array of translation strings
	 *
	 * @return string
	 */
	protected function translationsToString($translations)
	{
		$content = '';

		ksort($translations);

		foreach ($translations AS $key => $value)
		{
			if(is_array($value))
			{
				$content .= '['.$key."]\n";
				$content .= $this->translationsToString($value) . "\n";

				continue;
			}

			$content .= strtoupper($key) . '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8', false) .'"' . "\n";
		}

		return $content;
	}

	public function getTranslationKey($suffix)
	{
		return strtoupper($this->baseGenerator->getExtensionPrefix() . '_' .$suffix);
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

	/**
	 * Proxy to inflector method by the same name.
	 *
	 * @param   string  $word  English noun to pluralize
	 *
	 * @return string Plural noun
	 *
	 * @see \ExtensionGen\Utility\Inflector::toPlural()
	 */
	public function toPlural($word)
	{
		return Inflector::toPlural($word);
	}

	/**
	 * Proxy to inflector method by the same name.
	 *
	 * @param  string  $word  English noun to singular form
	 *
	 * @return string Singular noun.
	 *
	 * @see \ExtensionGen\Utility\Inflector::toSingular()
	 */
	public function toSingular($word)
	{
		return Inflector::toSingular($word);
	}
}
