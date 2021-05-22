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
 * Class Rsa_sha1
 * @package Logical\Oauth
 */
abstract class RsaSha1 extends Signature
{
	protected $name = "RSA-SHA1";

	// Up to the SP to implement this lookup of keys. Possible ideas are:
	// (1) do a lookup in a table of trusted certs keyed off of consumer
	// (2) fetch via http using a url provided by the requester
	// (3) some sort of specific discovery code based on request
	//
	// Either way should return a string representation of the certificate
	/**
	 * @param Request $request
	 * @return mixed
	 */
	protected abstract function fetchPublicCert($request);

	// Up to the SP to implement this lookup of keys. Possible ideas are:
	// (1) do a lookup in a table of trusted certs keyed off of consumer
	//
	// Either way should return a string representation of the certificate
	/**
	 * @param Request $request
	 *
	 * @return mixed
	 */
	protected abstract function fetchPrivateCert($request);

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
	public function buildSignature($request, $consumer, $token) {
		$base_string = $request->getSignatureBaseString();
		$request->base_string = $base_string;

		// Fetch the private key cert based on the request
		$cert = $this->fetchPrivateCert($request);

		// Pull the private key ID from the certificate
		$privatekeyid = openssl_get_privatekey($cert);

		// Sign using the key
		$ok = openssl_sign($base_string, $signature, $privatekeyid);

		// Release the key resource
		openssl_free_key($privatekeyid);

		return base64_encode($signature);
	}

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
		$decoded_sig = base64_decode($signature);

		$base_string = $request->getSignatureBaseString();

		// Fetch the public key cert based on the request
		$cert = $this->fetchPublicCert($request);

		// Pull the public key ID from the certificate
		$publickeyid = openssl_get_publickey($cert);

		// Check the computed signature against the one passed in the query
		$ok = openssl_verify($base_string, $decoded_sig, $publickeyid);

		// Release the key resource
		openssl_free_key($publickeyid);

		return $ok == 1;
	}
}
