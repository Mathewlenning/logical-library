<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Access;

// No direct access
use Exception;

defined('_JEXEC') or die;

/**
 * Class UserAccess
 *
 * @package  Logical\Access
 * @since    0.0.25
 */
class UserAccess implements AccessInterface
{
	/**
	 * List of permission messages
	 * Format [$permissionName => JTEXT_TRANSLATION_KEY)
	 *
	 * @var array
	 */
	protected $accessErrorMessages = array();

	/**
	 * UserAccess constructor.
	 */
	public function __construct()
	{
		// @todo make this work off the component access.xml
		$accessErrorMessages = array();
		$accessErrorMessages['core.admin'] = 'LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL';
		$accessErrorMessages['core.manage'] = 'LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL';
		$accessErrorMessages['core.create'] = 'LOGICAL_ACL_ERROR_CREATE_RECORD_NOT_PERMITTED';
		$accessErrorMessages['core.delete'] = 'LOGICAL_ACL_ERROR_DELETE_NOT_PERMITTED';
		$accessErrorMessages['core.edit'] = 'LOGICAL_ACL_ERROR_EDIT_RECORD_NOT_PERMITTED';
		$accessErrorMessages['core.edit.state'] = 'LOGICAL_ACL_ERROR_EDIT_STATE_NOT_PERMITTED';
		$accessErrorMessages['core.import'] = 'LOGICAL_ACL_ERROR_IMPORT_NOT_PERMITTED';
		$accessErrorMessages['core.export'] = 'LOGICAL_ACL_ERROR_EXPORT_NOT_PERMITTED';

		$this->accessErrorMessages = $accessErrorMessages;
	}

	/**
	 * This is a fallback for instances when using the model is not possible
	 *
	 * @param string $action      ACL action string. I.E. 'core.create'
	 * @param string $assetName   Asset name to check against.
	 * @param int    $pk          Primary key to check against
	 * @param int    $impersonate User id to impersonate, null to use the current user
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @see JUser::authorise
	 */
	public function allowAction($action, $assetName = null, $pk = null, $impersonate = null)
	{
		if (is_null($assetName))
		{
			$app = \JFactory::getApplication();
			$input = $app->input;
			$option = $input->getCmd('option');
			$view = $input->getCmd('view');
			$assetName = strtolower($option) . '.' . strtolower($view);
		}

		/** @var JUser $user */
		$user = \JFactory::getUser($impersonate);

		return $user->authorise($action, $assetName);
	}

	/**
	 * Method to get a translated access denied message based on the action being denied
	 *
	 * @param   string  $action     ACL action string. I.E. 'core.create'
	 *
	 * @return string
	 */
	public function getAccessDeniedMessage($action)
	{
		$msg = \JText::_('LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL');

		if(!empty($this->accessErrorMessages[$action]))
		{
			$msg = \JText::_($this->accessErrorMessages[$action]);
		}

		if(\JFactory::getUser()->guest)
		{
			$msg .= ' ' . \JText::_('LOGICAL_ACL_ERROR_LOGIN_REQUIRED');
		}

		return $msg;
	}
}
