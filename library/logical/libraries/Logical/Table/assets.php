<?php
/**
 * @version   1.0.0
 * @package   Babel-U-Lib
 * @copyright Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license   GNU General Public License version 2 or later; see LICENSE.txt
 * @author    Mathew Lenning - http://babel-university.com/
 */

// No direct access
defined('_JEXEC') or die;

class Babelu_libTableAssets extends JTableAsset
{
	/**
	 * Method to get the parent asset under which to register this one.
	 * By default, all assets are registered to the ROOT node with ID,
	 * which will default to 1 if none exists.
	 * The extended class can define a table and id to lookup.  If the
	 * asset does not exist it will be created.
	 *
	 * @param   Babelu_libTableCms   $table      A JTable object for the asset parent.
	 * @param   string               $assetName   to look up
	 *
	 * @return  integer
	 *
	 * @since   0.0.8
	 */
	public function getAssetParentId(Babelu_libTableCms $table, $assetName = null)
	{
		if (is_null($assetName))
		{
			//strip ids because items are not allowed to parent other items
			$assetName = $table->getAssetName(false);
		}

		//make sure we are using a different asset object to avoid side effects.
		$parentTable = new Babelu_libTableAssets(JFactory::getDbo());

		//cascade through the assetName and try to load the nearest parent
		while(!$parentTable->loadByName($assetName))
		{
			$assets = explode('.', $assetName);
			array_pop($assets);

			$assetName = implode('.', $assets);

			$noAssetFound = (empty($assetName));
			if ($noAssetFound)
			{
				//default to root
				return $parentTable->getRootId();
			}
		}

		return $parentTable->id;
	}

	/**
	 * Assert that the nested set data is valid.
	 *
	 * @throws InvalidArgumentException
	 * @return  boolean  True if the instance is sane and able to be stored in the database.
	 *
	 * @link    http://docs.joomla.org/JTable/check
	 * @since   11.1
	 */
	public function check()
	{
		if (!parent::check())
		{
			$msg = JText::_('BABELU_LIB_TABLE_ERROR_INVALID_PARENT_ID');
			throw new InvalidArgumentException($msg);
		}

		return true;
	}
}