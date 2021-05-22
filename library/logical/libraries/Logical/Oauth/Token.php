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
 * Class Token
 * @package Logical\Oauth
 */
class Token
{
	public $key;
	public $secret;

	/**
	 * @param string  $key     the token
	 * @param string  $secret  the token secret
	 */
	public function __construct($key, $secret)
	{
		$this->key = $key;
		$this->secret = $secret;
	}

	/**
	 * Return this token as a string value
	 *
	 * @return string
	 */
	public function __toString()
	{
		$string = 'oauth_token=';
		$string .= Utility::urlencodeRFC3986($this->key);
		$string .= '&oauth_token_secret=';
		$string .= Utility::urlencodeRFC3986($this->secret);

		return $string;
	}
}
