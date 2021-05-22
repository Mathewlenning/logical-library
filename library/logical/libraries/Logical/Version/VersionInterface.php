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


/**
 * Interface VersionInterface
 *
 * @package  Logical\Version
 * @since    0.0.1
 */
interface VersionInterface
{
	/**
	 * Method to get the extension manifest
	 *
	 * @return object
	 */
	public function getManifest();

	/**
	 * Method to get the extension version from the manifest
	 *
	 * @return string
	 */
	public function getVersion();

	/**
	 * Method to get the translated name of the product form the manifest
	 *
	 * @return string
	 */
	public function getName();
}
