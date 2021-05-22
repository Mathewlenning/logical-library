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
 * Class Signature
 *
 * @package Logical\Oauth
 */
abstract class Signature
{
	/**
	 * Name of the signature type
	 *
	 * @var string
	 */
	protected $name;

	/**
	 * Method to return the name of the Signature method (ie HMAC-SHA1)
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

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
	abstract public function buildSignature($request, $consumer, $token);

	/**
	 * Verifies that a given signature is correct
	 *
	 * @param Request $request
	 * @param Consumer $consumer
	 * @param Token $token
	 *
	 * @param string $signature
	 * @return bool
	 */
	public function checkSignature($request, $consumer, $token, $signature)
	{
		$built = $this->buildSignature($request, $consumer, $token);
		return $built == $signature;
	}
}
