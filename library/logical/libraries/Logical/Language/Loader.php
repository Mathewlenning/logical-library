<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Language;

// No direct access
defined('_JEXEC') or die;

use JApplicationCms, JLanguage;

/**
 * Class Loader
 *
 * @package  Logical\Language
 * @since    0.0.140
 */
class Loader
{
	protected $paths = array();

	/**
	 * Method to set the default component language file paths
	 *
	 * @param   JApplicationCms  $app        The application
	 * @param   string           $extension  name of the extension to load
	 *
	 * @return void
	 */
	protected function addDefaultPaths(JApplicationCms $app, $extension)
	{
		$this->paths[] = JPATH_THEMES . '/' . $app->getTemplate();

		if ($app->isClient('site'))
		{
			$this->paths[] = JPATH_SITE;
		}

		$this->paths[] = JPATH_ADMINISTRATOR;
		$this->paths[] = JPATH_ADMINISTRATOR . '/components/' . $extension;
	}

	/**
	 * Method to load an extension language
	 *
	 * @param   JApplicationCms  $app        The application instance
	 * @param   JLanguage        $lang       The JLanguage instance
	 * @param   string           $extension  name of the extension to load
	 * @param   array            $paths      array of search paths
	 *
	 * @return void
	 */
	public function load(JApplicationCms $app, JLanguage $lang, $extension, $paths = array())
	{
		$this->paths = $paths;
		$this->addDefaultPaths($app, $extension);

		foreach ($this->paths AS $basePath)
		{
			if ($lang->load($extension, $basePath, null, false, true))
			{
				return;
			}
		}
	}
}
