<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Mail;


// No direct access
defined('_JEXEC') or die;

use Joomla\CMS\Mail\Mail,  \Joomla\CMS\Factory;
use Joomla\CMS\User\User, JText;

/**
 * Class Email
 *
 * @package Logical\Mail
 */
class Email
{
	/**
	 * @var Mail
	 */
	protected $mailer;

	/**
	 * @var string
	 */
	protected $senderName;

	/**
	 * @var string
	 */
	protected $senderAddress;

	/**
	 * @var string
	 */
	protected $recipientAddress;

	/**
	 * @var string
	 */
	protected $subject;

	/**
	 * @var string
	 */
	protected $body;

    /**
     * @var null|array
     */
	protected $bcc;

	public function __construct($senderName = null, $senderAddress = null, $recipientAddress = null, $subject = null, $body = null)
	{
		$this->mailer = Factory::getMailer();
		$this->senderName = $senderName;
		$this->senderAddress = $senderAddress;
		$this->recipientAddress = $recipientAddress;
		$this->subject = $subject;
		$this->body = $body;
	}

	/**
	 * Method to clear the sender, recipient and message values
	 *
	 * @return $this
	 */
	public function clear()
	{
		$this->senderName = null;
		$this->senderAddress = null;
		$this->recipientAddress = null;
		$this->subject = null;
		$this->body = null;
		$this->bcc = null;

		$this->mailer->clearAllRecipients();

		return $this;
	}

	/**
	 * Method to get the mailer instance
	 *
	 * @return \JMail|Mail
	 */
	public function getMailer()
	{
		return $this->mailer;
	}

	/**
	 * Method to send a message from a user to a user with user IDs
	 *
	 * @param   int    $senderId    ID of the sending User
	 * @param   int    $recipientId ID of the recipient User
	 * @param   string $subject     Subject line
	 * @param   string $body        Message body
	 *
	 * @return $this
	 * @throws \ErrorException
	 */
	public function sendUserNotification($senderId, $recipientId, $subject, $body)
	{
		$this->setSenderByUserId($senderId);
		$this->setRecipientByUserId($recipientId);

		$this->subject = $subject;
		$this->body = $body;

		return $this->send();
	}

	/**
	 * Method to set the sender name and address by user ID
	 *
	 * @param   int  $userId  user ID to use
	 *
	 * @return $this
	 */
	public function setSenderByUserId($userId)
	{
		$user = Factory::getUser($userId);

		$this->validateUser($user);
		$this->setSenderName($user->name);
		$this->setSenderAddress($user->email);

		return $this;
	}

	/**
	 * Method to validate the user
	 *
	 * @param   User  $user  user instance
	 *
	 * @return bool
	 */
	protected function validateUser($user)
	{
		if ($user->guest)
		{
			throw new \InvalidArgumentException(JText::_('LOGICAL_ERROR_EMAIL_NOTIFICATION_CANNOT_SEND_FROM_GUEST_USER'));
		}

		return true;
	}

	/**
	 * Method to set the senders name
	 *
	 * @param   string  $name  name to use as the sender
	 *
	 * @return $this
	 */
	public function setSenderName($name)
	{
		$this->senderName = $name;

		return $this;
	}

	/**
	 * Method to set the senders address
	 *
	 * @param   string  $address  email address to use as sender
	 *
	 * @return $this
	 */
	public function setSenderAddress($address)
	{
		$this->validateEmail($address, 'LOGICAL_ERROR_EMAIL_NOTIFICATION_INVALID_SENDERS_ADDRESS');
		$this->senderAddress = $address;

		return $this;
	}

	/**
	 * Method to validate an email address
	 *
	 * @param   string  $email           email address to validate
	 * @param   string  $invalidMessage  Message to use if the email is invalid
	 *
	 * @return bool
	 */
	public function validateEmail($email, $invalidMessage)
	{
		if (strpos($email, '@') === false)
		{
			throw new \InvalidArgumentException(JText::_($invalidMessage));
		}

		return true;
	}

	/**
	 * Method to set the recipient address by user id
	 *
	 * @param   int  $userId  user ID to use
	 *
	 * @return $this
	 */
	public function setRecipientByUserId($userId)
	{
		$user = Factory::getUser($userId);
		$this->validateUser($user);
		$this->setRecipientAddress($user->email);

		return $this;
	}

	/**
	 * Method to set the recipients address
	 *
	 * @param   string  $address  email address to use as the recipient
	 *
	 * @return $this
	 */
	public function setRecipientAddress($address)
	{
		$this->validateEmail($address, 'LOGICAL_ERROR_EMAIL_NOTIFICATION_INVALID_RECIPIENT_ADDRESS');
		$this->recipientAddress = $address;

		return $this;
	}

	public function setSubject($subject)
	{
		$this->subject = $subject;
	}

	public function setBody($body)
	{
		$this->body = $body;
	}

	/**
	 * Method to send a the email using the current instance configuration values
	 *
	 * @return $this
	 *
	 * @throws \ErrorException
	 */
	public function send($isHtml = false)
	{
		$result = $this->mailer->sendMail(
			$this->senderAddress,
			$this->senderName,
			$this->recipientAddress,
			$this->subject,
			$this->body,
			$isHtml,
            null,
            $this->bcc
		);

		if (!$result)
		{
			throw new \ErrorException(\JText::_('JERROR_SENDING_EMAIL'));
		}

		return $this;
	}

	/**
	 * Method to send a system notification using the configuration values as the sender
	 *
	 * @param   string $subject          Subject line
	 * @param   string $body             Message body
	 * @param   string $recipientAddress email address to use as the recipient
	 *
	 * @return $this
	 * @throws \ErrorException
	 */
	public function sendSystemNotification($subject, $body, $recipientAddress = null)
	{
		$this->setSystemAsSender();

		if (!empty($recipientAddress))
		{
			$this->setRecipientAddress($recipientAddress);
		}

		$this->subject = $subject;
		$this->body = $body;

		return $this->send();
	}

	/**
	 * Method to set the system defaults as the sender
	 *
	 * @return $this
	 *
	 * @since version
	 */
	public function setSystemAsSender()
	{
		$config = Factory::getConfig();

		$this->setSenderName($config->get('fromname', $config->get('sitename')));
		$this->setSenderAddress($config->get('mailfrom'));

		return $this;
	}

    /**
     * Method to add the bcc
     * @param string|array  $bcc
     *
     * @return $this
     */
	public function setBBC($bcc)
    {
        if(is_string($bcc))
        {
            $bcc = array($bcc);
        }

        if (is_object($bcc))
        {
           $bcc = (array) $bcc;
        }

        foreach ($bcc AS $address)
        {
            $this->validateEmail($address, 'Invalid Email Address');

            if (empty($this->bcc))
            {
               $this->bcc = array();
            }

            $this->bcc[] = $address;
        }

        return $this;
    }

}
