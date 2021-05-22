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
 * Interface DataStore
 * @package Logical\Oauth
 */
interface DataStore
{
	public function lookupConsumer($consumer_key);

	public function lookupToken($consumer, $token_type, $token);

	public function lookupNonce($consumer, $token, $nonce, $timestamp);

	public function newRequestToken($consumer, $callback = null);

	public function newAccessToken($token, $consumer, $verifier = null);
}
