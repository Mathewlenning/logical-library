<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Utility;


// No direct access
defined('_JEXEC') or die;

use \Joomla\CMS\Component\ComponentHelper,
	\Joomla\CMS\User\User as JUser,
	Joomla\CMS\User\UserHelper;
use mysql_xdevapi\Exception;

/**
 * Utility class for handling user related tasks
 *
 * @package Logical\Utility
 */
class User
{
	/**
	 * Method to create a new joomla user
	 *
	 * @param   string  $name           Name of the user
	 * @param   string  $email          Email of the user
	 * @param   string  $userName       Login username, if not provided one will be created based off the email address
	 * @param   string  $passwordClear  Login password, if not provided one will be created
	 * @param   array   $defaultGroups  If not provided usergroup 2 will be used
	 * @param   bool    $requireReset   Should the user have to reset their password on their first login?
	 *
	 * @return JUser
	 * @throws \ErrorException
	 */
	public static function createNewUser($name, $email, $userName = null, $passwordClear = null, $defaultGroups = array(), $requireReset = true)
	{
		$userName = trim($userName);

		if (empty($userName))
		{
			$userName = self::getUserNameFromEmail($email);
		}

		if (empty($passwordClear))
		{
			$passwordClear = UserHelper::genRandomPassword( 10 );
		}

		if(empty($defaultGroups))
		{
			$params = ComponentHelper::getParams('com_users');
			$defaultGroups = array($params->get( 'new_usertype', 2 ));
		}

		if (!is_array($defaultGroups))
		{
			$defaultGroups = (array) $defaultGroups;
		}

		$password = UserHelper::hashPassword($passwordClear);

		$user = JUser::getInstance();
		$user->id = 0;
		$user->name = $name;
		$user->username = $userName;
		$user->email = $email;
		$user->password_clear = $passwordClear;
		$user->password = $password;
		$user->groups = $defaultGroups;
		$user->requireReset = ($requireReset) ? 1 : 0;

		if (!$user->save())
		{
			throw new \ErrorException($user->getError());
		}

		return $user;
	}

	/**
	 * Method to get a unique username based off the provided username
	 *
	 * @param   string  $userName
	 *
	 * @return mixed
	 */
	public static function getUniqueUserName($userName)
	{
		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('id')
			->from('#__users')
			->where('username = ' . $dbo->q($userName));

		$result = $dbo->setQuery($query)->loadResult();

		if (empty($result))
		{
			return $userName;
		}

		return self::getCountName($userName);
	}

	/**
	 * Method to add a numeric number to the end of a username
	 *
	 * @param   string  $userName
	 *
	 * @return mixed
	 */
	protected static function getCountName($userName)
	{
		$dbo = $dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('COUNT(id) AS count')
			->from('#__users')
			->where('username = ' . $dbo->q($userName));

		$result = $dbo->setQuery($query)->loadResult();

		return self::getUniqueUserName($userName . (int) $result);
	}

	/**
	 * Utility method to get a unique username from an email address
	 *
	 * @param   string  $email
	 * @return  string
	 */
	public static function getUserNameFromEmail($email)
	{
		$parts = explode('@', $email);

		$rawUserName = array_shift($parts);
		return self::getUniqueUserName($rawUserName);
	}

	/**
	 * Method to get an array of user email addresses from a list of usergroup ids
	 *
	 * @param   array  $userGroupIds array of Joomla usergroup IDs
	 *
	 * @return  array
	 * @throws \Exception
	 */
	public static function getUserGroupEmails($userGroupIds = array())
	{
		if (empty($userGroupIds))
		{
			return array(\JFactory::getApplication()->get('mailfrom'));
		}

		if (!is_array($userGroupIds))
		{
			$userGroupIds = array($userGroupIds);
		}

		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('DISTINCT(user.email)')
			->from('#__user_usergroup_map AS usergroup')
			->join('left', '#__users AS user ON user.id = usergroup.user_id')
			->where('usergroup.group_id IN (' . implode(',', $userGroupIds) .')');

		$return = $dbo->setQuery($query)->loadColumn();

		if(empty($return))
		{
			return array();
		}

		return $return;
	}

	/**
	 * Method to load the user by email address
	 *
	 * @param   string  $email
	 *
	 * @return  \Joomla\CMS\User\User | false
	 */
	public static function getUserByEmail($email)
	{
		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('id')
			->from('#__users')
			->where('email = ' . $dbo->q($email));

		$userId = $dbo->setQuery($query)->loadResult();

		if (empty($userId))
		{
			return false;
		}

		return \JFactory::getUser($userId);
	}

	/**
	 * Method to deactivate non super user accounts
	 *
	 * @param $userId
	 *
	 * @throws \ErrorException if the userId is a super admin
	 */
	public static function deactivate($userId)
	{
		$user = \JFactory::getUser($userId);

		if ($user->authorise('core.admin'))
		{
			throw new \ErrorException('Cannot deactivate super admin through this feature');
		}

		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->update('#__users')
			->set('block = ' . 1)
			->where('id = ' . (int) $user->id);

		$dbo->setQuery($query)->execute();
	}
}
