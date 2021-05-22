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

use Joomla\Event\Event;
use Logical\Version\Version;

use Exception;
use ErrorException;
use JVersion;
use JInstallerAdapter;
use JFolder;
use JFactory;

/**
 * Class Script install script
 *
 * @package  Logical\Install
 * @since    0.0.110
 */
abstract class ComponentScript
{
	/**
	 * @var string
	 */
	protected $extensionType;

	/**
	 * @var string
	 */
	protected $currentVersion = '0.0.0';

	/**
	 * @var ComponentBackup
	 */
	protected $componentBackup;

	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param   string             $type    is the type of change (install, update or discover_install)
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @throws ErrorException
	 * @return void
	 */
	public function preflight($type, $parent)
	{
		if ($type == 'uninstall')
		{
			// Nothing to do on uninstall
			return;
		}

		$version = new JVersion;

		if (!$version->isCompatible(3.0))
		{
			throw new ErrorException('Logical extensions only supports Joomla version 3 or greater. Please update your system and try the installing again.');
		}

		$manifest = $parent->getManifest();

		$this->extensionType = (string) $manifest['type'];

		if ($this->extensionType != 'component')
		{
			return;
		}

		$dependencies = $manifest->xpath('/extension/dependencies');

		if(!empty($dependencies[0]))
		{
			$dependencies = $dependencies[0];
			$dependenciesFolder = $parent->getParent()->getPath('source') . '/' .(string) $dependencies['folder'];

			$this->installDependencies($dependencies, $dependenciesFolder, $type);
		}

		if ($type != 'update')
		{
			return;
		}

		$extensionVersion = $this->getCurrentVersion($parent);

		if (is_null($extensionVersion))
		{
			return;
		}

		$newVersion = (string) $parent->getManifest()->version;

		if (version_compare($extensionVersion->getVersion(), (string) $newVersion, '>'))
		{
			throw new ErrorException('Error: Attempting to update to older version. Currently Installed Version: '
				. $extensionVersion->getVersion());
		}

		if (version_compare($extensionVersion->getVersion(), (string) $newVersion, '=='))
		{
			throw new ErrorException('Error: Release version '. $newVersion .' already installed.');
		}

		$this->currentVersion = $extensionVersion->getVersion();

		$this->componentBackup = new ComponentBackup($extensionVersion);
		$this->componentBackup->createBackup($parent);
	}

	protected function installDependencies(\SimpleXMLElement $xml, $packageFolder, $type)
	{
		$results = array();

		foreach ($xml->xpath('file') AS $child)
		{
			$file = $packageFolder .'/' . (string) $child;

			if(!file_exists($file))
			{
				JFactory::getApplication()->enqueueMessage('Missing Dependency: '. (string) $child);

				continue;
			}

			if(is_dir($file))
			{
				$package = array('dir' => $file, 'type' => \JInstallerHelper::detectType($file));
			}
			else
			{
				$package = \JInstallerHelper::unpack($file);
			}

			$tmpInstaller  = new \JInstaller;
			$installResult = $tmpInstaller->{$type}($package['dir']);

			if (!$installResult)
			{
				throw new \RuntimeException(
					\JText::sprintf(
						'JLIB_INSTALLER_ABORT_PACK_INSTALL_ERROR_EXTENSION',
						\JText::_('JLIB_INSTALLER_' . strtoupper($type)),
						basename($file)
					)
				);
			}

			$results[] = array(
				'name' => (string) $tmpInstaller->manifest->name,
				'result' => $installResult,
			);
		}

		return $results;
	}

	/**
	 * Method to get the current version
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @throws ErrorException
	 *
	 * @return \Logical\Version\Version
	 */
	protected function getCurrentVersion($parent)
	{
		$extensionVersion = new Version($parent->getElement(), $this->extensionType, null);

		return $extensionVersion;
	}

	/**
	 * Method to install the component
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function install($parent)
	{
	}

	/**
	 * Method to uninstall the component
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function uninstall($parent)
	{
	}

	/**
	 *  Method to update the component
	 *
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function update($parent)
	{
	}

	/**
	 * Method to run after an install/update/uninstall method
	 *
	 * @param   string             $type    is the type of change (install, update or discover_install)
	 * @param   JInstallerAdapter  $parent  is the class calling this method
	 *
	 * @return void
	 */
	public function postflight($type, $parent)
	{
		if ($type == 'uninstall' || $this->extensionType != 'component')
		{
			return;
		}

		$adminDir = $parent->getParent()->getPath('extension_administrator');
		$sqlDir = $adminDir . '/sql/updates/mysql';

		if (!JFolder::exists($sqlDir))
		{
			return;
		}

		$dispatcher = new \Logical\Event\Dispatcher(\JFactory::getApplication());
		$event = new Event('onBeforeLogicalPostFlight', array($type, $parent));
		$dispatcher->dispatch('onBeforeLogicalPostFlight', $event);

		$files = JFolder::files($sqlDir,'.', false, false, array('.svn', 'CVS', '.DS_Store', '__MACOSX'), array('^\..*', '.*~'), $naturalSort = true);
		$dbo = JFactory::getDbo();

		try
		{
			$dbo->transactionStart(true);

			foreach ($files AS $file)
			{
				$parts = explode('.', $file);

				$type = strtolower(array_pop($parts));

				if ($type != 'sql')
				{
					continue;
				}

				$version = implode('.', $parts);

				if (version_compare($version, $this->currentVersion, '<='))
				{
					continue;
				}

				$this->updateSql($sqlDir . '/' . $file);
			}

			$dbo->transactionCommit(true);

			$tablePrefix = substr(strtolower((string) $parent->getManifest()->name),4);

			$dbo->setQuery($this->getSchemaQuery($dbo, $tablePrefix));
			$dbo->execute();

			$dbo->setQuery('TRUNCATE ' . $dbo->qn('#__'. $tablePrefix .'_schemas'));
			$dbo->execute();

			if (!is_null($this->componentBackup))
			{
				$this->componentBackup->cleanUp();
			}

			$event = new Event('onAfterLogicalPostFlight', array($type, $parent));

			$dispatcher->dispatch('onAfterLogicalPostFlight', $event);
		}
		catch (Exception $e)
		{
			$dbo->transactionRollback(true);

			if (!is_null($this->componentBackup))
			{
				$this->componentBackup->revert($parent);
			}

			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
		}
	}

	/**
	 * Method to update the database from an .sql file
	 *
	 * @param   string  $sqlFile  full path/name to sql file
	 *
	 * @return void;
	 */
	protected function updateSql($sqlFile)
	{
		$contents = file_get_contents($sqlFile);
		$dbo = JFactory::getDbo();
		$queries = $dbo->splitSql($contents);

		foreach ($queries AS $query)
		{
			$query = trim($query);

			if (empty($query))
			{
				continue;
			}

			$dbo->setQuery($query);
			$dbo->execute();
		}
	}

	protected function getSchemaQuery($dbo, $tablePrefix)
	{
		$tableName = $dbo->qn('#__'. $tablePrefix .'_schemas');

		return 'CREATE TABLE IF NOT EXISTS ' . $tableName . ' (`asset_id` varchar(255) NOT NULL, `fields` text NOT NULL,`cached_on` datetime NOT NULL, PRIMARY KEY (`asset_id`)) ENGINE=InnoDB DEFAULT CHARSET=utf8;';

	}
}
