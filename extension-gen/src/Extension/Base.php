<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Extension;

abstract class Base
{
	/**
	 * @var string
	 */
	protected $baseDir;

	/**
	 * @var string
	 */
	protected $tmplDir;

	/**
	 * @var \ExtensionGen\Manifest\Base
	 */
	protected $manifest;

	/**
	 * @var Custom
	 */
	protected $generator = null;

	/**
	 * Extension Base constructor.
	 *
	 * @param   string  $baseDir        base dir to the extension root directory
	 * @param   string  $tmplDir        Template directory
	 *
	 * @throws \ErrorException
	 */
	public function __construct($baseDir, $tmplDir)
	{
		$this->preparePath($baseDir);
		$this->baseDir = $baseDir;
		$this->tmplDir = $tmplDir;

		if(!file_exists($this->tmplDir))
		{
			throw new \ErrorException('Could not find template directory :' . $this->tmplDir);
		}
	}

	/**
	 * @return string
	 */
	public function getBaseDir()
	{
		return $this->baseDir;
	}

	/**
	 * @return string
	 */
	public function getTmplDir()
	{
		return $this->tmplDir;
	}

	/**
	 * @return \ExtensionGen\Manifest\Base
	 */
	public function getManifest()
	{
		return $this->manifest;
	}

	/**
	 * Method to check if there is a custom generator in the template folder
	 *
	 * @return bool
	 */
	protected function hasGenerator()
	{
		return (file_exists($this->tmplDir.'/Generator.php'));
	}

	/**
	 * Method to load the generator from the template folder
	 *
	 * @return string|null the class name of the generator
	 */
	protected function loadGenerator()
	{
		static $className = null;

		require_once $this->tmplDir.'/Generator.php';

		return $className;
	}

	/**
	 * Method to execute a custom generator script if it exists
	 *
	 * @param $methodName
	 * @param array $arguments
	 * @return bool
	 */
	protected function executeGenerator($methodName, $arguments = array())
	{
		if(!$this->hasGenerator())
		{
			return false;
		}

		$className = $this->loadGenerator();

		if(is_null($className))
		{
			return false;
		}

		/** @var \ExtensionGen\Extension\Custom $generator */
		$this->generator = new $className($this->baseDir, $this->tmplDir, $this);

		if(!is_callable(array($this->generator, $methodName)))
		{
			return false;
		}

		if(!call_user_func_array(array($this->generator,$methodName),$arguments))
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to load a template file and return a search/replace version
	 *
	 * @param   string  $templateFile  the name of the template file relative to the template dir
	 * @param   array   $search        keys to search for
	 * @param   array   $replace       values to replace with
	 *
	 * @return mixed
	 */
	protected function generateTemplate($templateFile, $search, $replace)
	{
		$fullPath = $this->tmplDir. '/' .$templateFile;

		if(!file_exists($fullPath))
		{
			$fullPath = $this->tmplDir . '/../default/' . $templateFile;
		}

		$template = file_get_contents($fullPath);
		return str_replace($search, $replace, $template);
	}

	/**
	 * Utility method to capitalize words after underscores
	 *
	 * @param $string
	 * @return string
	 */
	protected function capitalizeOnUnderscore($string, $removeUnderscore = false)
	{
		if(strpos($string, '_') === false)
		{
			return ucfirst($string);
		}
		$parts = explode('_', $string);

		foreach ($parts AS &$part)
		{
			$part = ucfirst($part);
		}

		if(!$removeUnderscore)
		{
			return implode('_', $parts);
		}

		return implode(' ', $parts);
	}

	/**
	 * Method to create a path recursively if it does not exist.
	 *
	 * @param $path
	 */
	protected function preparePath($path)
	{
		if(!file_exists($path))
		{
			mkdir($path, '0777', true);
		}
	}

	/**
	 * Method to append a string to language file
	 *
	 * @param   string  $fileName  Language file name
	 * @param   string  $string    string to add to the file
	 * @param   string  $path      path of the language file
	 *
	 * @return boolean
	 */
	protected function appendToLanguage($fileName, $string, $path)
	{
		return (bool) file_put_contents($path . '/' . $fileName, $string, FILE_APPEND);
	}

	/**
	 * Method to get search/replace for templates
	 *
	 * @return array
	 */
	abstract public function getSearchReplace();

	/**
	 * Method to get an extension prefix
	 *
	 * @return string
	 */
	abstract public function getExtensionPrefix();
}
