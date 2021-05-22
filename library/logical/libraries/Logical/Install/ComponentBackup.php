<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Install;

// No direct access
defined('_JEXEC') or die;
use Logical\Version\VersionInterface;

use JFolder;
use JFactory;
use JInstallerAdapter;

/**
 * Class ComponentBackup
 *
 * @package  Logical\Install
 * @since    0.0.111
 */
class ComponentBackup
{
	/**
	 * @var VersionInterface
	 */
	protected $extensionVersion;

	/**
	 * @var array
	 */
	protected $backupUrl = array();

	/**
	 * ComponentBackup constructor.
	 *
	 * @param   VersionInterface  $extensionVersion  the version to create a backup for
	 */
	public function __construct(VersionInterface $extensionVersion)
	{
		$this->extensionVersion = $extensionVersion;
	}

	/**
	 * Method to create a backup of a component before install
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function createBackup($parent)
	{
		$adminUrl = (string) $parent->get('oldAdminFiles')['folder'];
		$siteUrl = (string) $parent->get('oldFiles')['folder'];

		$this->backupUrl['admin'] = JPATH_SITE . '/tmp/' . $this->extensionVersion->getVersion() . '/' . $adminUrl;
		$this->backupUrl['site'] = JPATH_SITE . '/tmp/' . $this->extensionVersion->getVersion() . '/' . $siteUrl;

		JFolder::copy(JPATH_SITE . '/' . $adminUrl, $this->backupUrl['admin']);
		JFolder::copy(JPATH_SITE . '/' . $siteUrl, $this->backupUrl['site']);
	}

	/**
	 * Method to revert an update when an install fails
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function revert($parent)
	{
		$adminUrl = (string) $parent->get('oldAdminFiles')['folder'];
		$siteUrl = (string) $parent->get('oldFiles')['folder'];

		// Revert the files
		JFolder::delete(JPATH_SITE . '/' . $adminUrl);
		JFolder::delete(JPATH_SITE . '/' . $siteUrl);
		JFolder::copy($this->backupUrl['admin'], JPATH_SITE . '/' . $adminUrl);
		JFolder::copy($this->backupUrl['site'], JPATH_SITE . '/' . $siteUrl);

		// Revert extension record
		$manifest = $this->extensionVersion->getManifest();
		$extension_id = $manifest->extension_id;

		unset($manifest->extension_id);
		$manifestCache = json_encode($manifest);

		$dbo = JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->update('#__extensions');
		$query->set('manifest_cache = ' . $dbo->q($manifestCache))
			->where('extension_id = ' . (int) $extension_id);
		$dbo->setQuery($query)->execute();

		$this->cleanUp();
	}

	/**
	 * Method to clean up the backup files
	 *
	 * @return void
	 */
	public function cleanUp()
	{
		JFolder::delete(JPATH_SITE . '/tmp/' . $this->extensionVersion->getVersion());
	}
}
