<?php
/**
 * @author     Mathew Lenning
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

 // No direct access
defined('_JEXEC') or die;

class PlgSystemLogicalInstallerScript
{
	/**
	 * method to run after an install/update/uninstall method
	 *
	 * @param string $type is the type of change (install, update or discover_install)
	 * @param object $parent is the class calling this method
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
		if($type != 'install')
		{
			return;
		}

		$dbo = JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->update('#__extensions')->set('enabled = 1')
			->where('type = '.$dbo->quote('plugin'))
			->where('element = '.$dbo->quote('logical'))
			->where('folder = '.$dbo->quote('system'));

		$dbo->setQuery($query)
			->execute();
	}
}
