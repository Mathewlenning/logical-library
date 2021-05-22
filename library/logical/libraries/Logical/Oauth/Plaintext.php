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
 * Class Plaintext
 * @package Logical\Oauth
 */
class Plaintext extends Signature
{
	protected $name = "PLAINTEXT";

	/**
	 * Build up the signature
	 * NOTE: The output of this function MUST NOT be urlencoded.
	 * the encoding is handled in Request when the final
	 * request is serialized
	 *
	 * @param   Request   $request
	 * @param   Consumer  $consumer
	 * @param   Token     $token
	 *
	 * @return string
	 */
	public function buildSignature($request, $consumer, $token)
	{
		$key_parts = array(
			$consumer->secret,
			($token) ? $token->secret : ""
		);

		$key_parts = Utility::urlencodeRFC3986($key_parts);
		$key = implode('&', $key_parts);
		$request->base_string = $key;

		return $key;
	}
}
