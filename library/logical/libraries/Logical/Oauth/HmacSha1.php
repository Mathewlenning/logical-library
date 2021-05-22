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
 * Class Hmac_sha1
 * @package Logical\Oauth
 */
class HmacSha1 extends Signature
{
	protected $name = 'HMAC-SHA1';

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
		$base_string = $request->getSignatureBaseString();

		$request->base_string = $base_string;

		$key_parts = array(
			$consumer->secret,
			($token) ? $token->secret : ""
		);

		$key_parts = Utility::urlencodeRFC3986($key_parts);
		$key = implode('&', $key_parts);

		return base64_encode(hash_hmac('sha1', $base_string, $key, true));
	}
}
