<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Plugin;

// No direct access
defined('_JEXEC') or die;

use JFactory, JText;
/**
 * Class Notification
 * @package Logical\Plugin
 *
 */
class Email extends \JPlugin
{
	/**
	 * @var \Joomla\CMS\Application\CMSApplication
	 */
	public $app;

	protected function searchReplace($search = array(), $replace = array(), $subject = '')
	{
		$config = JFactory::getConfig();

		$search[] = '%sitename%';
		$replace[] = $config->get('sitename');

		return str_replace($search, $replace, $subject);
	}

	protected function getSiteMailFromAddress()
	{
		$config = JFactory::getConfig();
		return $config->get('mailfrom');
	}

	/**
	 * Method to get the application instance
	 *
	 * @return \Joomla\CMS\Application\CMSApplication
	 *
	 * @throws \Exception
	 */
	protected function getApp()
	{
		if(is_null($this->app))
		{
			$this->app = JFactory::getApplication();
		}

		return $this->app;
	}

	/**
	 * Method to check if the email is a valid email format
	 *
	 * @param string $email
	 *
	 * @return bool
	 */
	protected function validateEmail($email)
	{
		$atPos = strpos($email, '@');

		if ($atPos === false && $atPos != 0)
		{
			$this->app->enqueueMessage(JText::_('LOGICAL_PLUGIN_ERROR_INVALID_EMAIL') . ':' . $email, 'warning');

			return false;
		}

		return $email;
	}

	/**
	 * Method to send an email notification
	 *
	 * @param   string  $fromAddress
	 * @param   string  $fromName
	 * @param   string  $toAddress
	 * @param   string  $subject
	 * @param   string  $message
	 */
	protected function sendMail($fromAddress,$fromName, $toAddress, $subject, $message)
	{
		$mailer = JFactory::getMailer();

		if($mailer->sendMail($fromAddress, $fromName, $toAddress, $subject, $message, true) === false)
		{
			$this->app->enqueueMessage(JText::_('JERROR_SENDING_EMAIL'), 'warning');
		}
	}
}
