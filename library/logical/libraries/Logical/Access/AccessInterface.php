<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Access;

// No direct access
defined('_JEXEC') or die;

/**
 * Interface AccessInterface
 *
 * @package  Logical\Access
 * @since    0.0.1
 */
interface AccessInterface
{
	/**
	 * Method to authorise the current user for an action.
	 *
	 * @param   string  $action      ACL action string. I.E. 'core.create'
	 * @param   string  $assetName   Asset name to check against. I.E. 'com_mycomponent.assetname
	 * @param   int     $pk          Primary key to check against for record level ACL.
	 * @param int       $impersonate User id to impersonate, null to use the current user
	 *
	 * @return bool
	 *
	 * @see JUser::authorise
	 */
	public function allowAction($action, $assetName = null, $pk = null, $impersonate = null);

	/**
	 * Method to get a translated access denied message based on the action being denied
	 *
	 * @param   string  $action     ACL action string. I.E. 'core.create'
	 *
	 * @return string
	 */
	public function getAccessDeniedMessage($action);
}
