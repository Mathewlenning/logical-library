<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Version;

// No direct access
defined('_JEXEC') or die;

use JFactory;
use JText;

/**
 * Class Version
 *
 * @package  Logical\Version
 * @since    0.0.1
 */
class Version implements VersionInterface
{
	protected $element;

	protected $type;

	protected $folder;

	protected $manifest;

	protected $copyright = '&copy; 2014 ~ 2015 Mathew Lenning. All rights reserved.';

	/**
	 * Constructor
	 *
	 * @param   string  $element  component option or extension element
	 * @param   string  $type     type of extension
	 * @param   string  $folder   folder for plugins only
	 */
	public function __construct($element, $type = 'component', $folder = null)
	{
		$this->element = $element;
		$this->type = $type;
		$this->folder = $folder;

		$this->manifest = $this->getSystemRecord();
	}

	/**
	 * Method to get the extension manifest from the extensions database
	 *
	 * @return object
	 */
	protected function getSystemRecord()
	{
		$dbo = JFactory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('extension_id, manifest_cache');
		$query->from('#__extensions');

		$query->where('element = ' . $dbo->quote($this->element));
		$query->where('type = ' . $dbo->quote($this->type));

		if (!is_null($this->folder))
		{
			$query->where('folder = ' . $dbo->quote($this->folder));
		}

		$dbo->setQuery($query);
		$extensionRecord = $dbo->loadObject();

		if (!is_null($extensionRecord))
		{
			$manifestData = json_decode($extensionRecord->manifest_cache);
			$manifestData->extension_id = $extensionRecord->extension_id;
			$extensionRecord = $manifestData;
		}

		return $extensionRecord;
	}

	/**
	 * Method to get the extension manifest
	 *
	 * @return object
	 */
	public function getManifest()
	{
		return $this->manifest;
	}

	/**
	 * Method to get the extension version from the manifest
	 *
	 * @return string
	 */
	public function getVersion()
	{
		if (isset($this->manifest->version))
		{
			return (string) $this->manifest->version;
		}

		return '';
	}

	/**
	 * Method to get the translated name of the product form the manifest
	 *
	 * @return string
	 */
	public function getName()
	{
		return JText::_($this->manifest->name);
	}

	/**
	 * Method to check if the current extension has an update available
	 *
	 * @return mixed|null
	 */
	public function checkForUpdate()
	{
		$dbo = JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('*')
			->from('#__updates')
			->where('extension_id = ' . $this->manifest->extension_id);

		$result = $dbo->setQuery($query)->loadObject();

		if(empty($result))
		{
			return null;
		}

		$result->current_version = $this->getVersion();
		return $result;
	}
}
