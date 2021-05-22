<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Oauth;

// No direct access
defined('_JEXEC') or die;

/**
 * Class Consumer
 * @package Logical\Oauth
 */
class Consumer
{
	public $key;
	public $secret;

	/**
	 * Consumer constructor.
	/**
	 * @param string  $key           the token
	 * @param string  $secret        the token secret
	 * @param string  $callback_url  callback Url
	 */
	public function __construct($key, $secret, $callback_url=null)
	{
		$this->key = $key;
		$this->secret = $secret;
		$this->callback_url = $callback_url;
	}

	/**
	 * Method to convert to a string
	 * @return string
	 */
	public function __toString()
	{
		return " Logical\\Oauth\\Consumer[key=$this->key,secret=$this->secret]";
	}
}
