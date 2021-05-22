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

class Server
{
	/**
	 * @var int
	 */
	protected $timestampThreshold = 300;

	/**
	 * @var string
	 */
	protected $version = '1.0';

	/**
	 * @var array
	 */
	protected $signatureMethods = array();

	/**
	 * @var DataStore
	 */
	protected $dataStore;

	/**
	 * Server constructor.
	 *
	 * @param DataStore $dataStore
	 */
	public function __construct($dataStore)
	{
		$this->dataStore = $dataStore;
	}

	/**
	 * Method to add signature methods
	 *
	 * @param Signature $signatureMethod
	 */
	public function addSignatureMethod($signatureMethod)
	{
		$this->signatureMethods[$signatureMethod->getName()] = $signatureMethod;
	}

	/**
	 * process a request_token request
	 * returns the request token on success
	 *
	 * @param Request $request
	 *
	 * @return Request
	 */
	public function fetchRequestToken($request)
	{
		$this->getVersion($request);

		$consumer = $this->getConsumer($request);

		// no token required for the initial token request
		$token = null;

		$this->checkSignature($request, $consumer, $token);

		// Rev A change
		$callback = $request->getParameter('oauth_callback');
		$newToken = $this->dataStore->newRequestToken($consumer, $callback);

		return $newToken;
	}

	/**
	 * process an access_token request
	 * returns the access token on success
	 *
	 * @param Request $request
	 *
	 * @return Request
	 */
	public function fetchAccessToken($request)
	{
		$this->getVersion($request);

		$consumer = $this->getConsumer($request);

		// requires authorized request token
		$token = $this->getToken($request, $consumer, "request");

		$this->checkSignature($request, $consumer, $token);

		// Rev A change
		$verifier = $request->getParameter('oauth_verifier');
		$new_token = $this->dataStore->newAccessToken($token, $consumer, $verifier);

		return $new_token;
	}

	/**
	 * verify an api call, checks all the parameters
	 *
	 * @param Request $request
	 *
	 * @return array
	 */
	public function verifyRequest($request)
	{
		$this->getVersion($request);
		$consumer = $this->getConsumer($request);
		$token = $this->getToken($request, $consumer, "access");
		$this->checkSignature($request, $consumer, $token);
		return array($consumer, $token);
	}


	/**
	 * Method to get the version from the request
	 *
	 * @param Request $request
	 *
	 * @return string
	 * @throws \Exception
	 */
	private function getVersion(&$request)
	{
		$version = $request->getParameter("oauth_version");
		if (!$version)
		{
			// Service Providers MUST assume the protocol version to be 1.0 if this parameter is not present.
			// Chapter 7.0 ("Accessing Protected Resources")
			$version = '1.0';
		}

		if ($version !== $this->version)
		{
			throw new \Exception("OAuth version '$version' not supported");
		}

		return $version;
	}

	/**
	 * figure out the signature with some defaults
	 *
	 * @param Request $request
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function getSignatureMethod(&$request)
	{
		$signature_method =
			@$request->getParameter("oauth_signature_method");

		if (!$signature_method)
		{
			// According to chapter 7 ("Accessing Protected Resources") the signature-method
			// parameter is required, and we can't just fallback to PLAINTEXT
			throw new \Exception('No signature method parameter. This parameter is required');
		}

		if (!in_array($signature_method,
			array_keys($this->signatureMethods))
		)
		{
			throw new \Exception(
				"Signature method '$signature_method' not supported " .
				"try one of the following: " .
				implode(", ", array_keys($this->signatureMethods))
			);
		}
		return $this->signatureMethods[$signature_method];
	}

	/**
	 * Try to find the consumer for the provided request's consumer key
	 *
	 * @param Request $request
	 *
	 * @return Consumer
	 * @throws \Exception
	 */
	private function getConsumer($request)
	{
		$consumer_key = @$request->getParameter("oauth_consumer_key");
		if (!$consumer_key)
		{
			throw new \Exception("Invalid consumer key");
		}

		$consumer = $this->dataStore->lookupConsumer($consumer_key);
		if (!$consumer)
		{
			throw new \Exception("Invalid consumer");
		}

		return $consumer;
	}

	/**
	 * try to find the token for the provided request's token key
	 *
	 * @param Request $request
	 * @param Consumer $consumer
	 * @param string $token_type
	 *
	 * @return Token
	 * @throws \Exception
	 */
	private function getToken($request, $consumer, $token_type = "access")
	{
		$token_field = @$request->getParameter('oauth_token');
		$token = $this->dataStore->lookupToken(
			$consumer, $token_type, $token_field
		);
		if (!$token)
		{
			throw new \Exception("Invalid $token_type token: $token_field");
		}
		return $token;
	}

	/**
	 * all-in-one function to check the signature on a request
	 * should guess the signature method appropriately
	 *
	 * @param Request $request
	 * @param Consumer $consumer
	 * @param Token $token
	 *
	 * @throws \Exception
	 */
	private function checkSignature($request, $consumer, $token)
	{
		// this should probably be in a different method
		$timestamp = @$request->getParameter('oauth_timestamp');
		$nonce = @$request->getParameter('oauth_nonce');

		$this->checkTimestamp($timestamp);
		$this->checkNonce($consumer, $token, $nonce, $timestamp);

		$signature_method = $this->getSignatureMethod($request);

		$signature = $request->getParameter('oauth_signature');
		$valid_sig = $signature_method->check_signature(
			$request,
			$consumer,
			$token,
			$signature
		);

		if (!$valid_sig)
		{
			throw new \Exception("Invalid signature");
		}
	}

	/**
	 * check that the timestamp is new enough
	 *
	 * @param   string  $timestamp
	 *
	 * @throws \Exception
	 */
	private function checkTimestamp($timestamp)
	{
		if (!$timestamp)
		{
			throw new \Exception('Missing timestamp parameter. The parameter is required');
		}

		// verify that timestamp is somewhat recent
		$now = time();
		if (abs($now - $timestamp) > $this->timestampThreshold)
		{
			throw new \Exception(
				"Expired timestamp, yours $timestamp, ours $now"
			);
		}
	}

	/**
	 * check that the nonce is not repeated
	 *
	 * @param Consumer $consumer
	 * @param Token $token
	 * @param string $nonce
	 * @param string $timestamp
	 *
	 * @throws \Exception
	 */
	private function checkNonce($consumer, $token, $nonce, $timestamp)
	{
		if (!$nonce)
		{
			throw new \Exception('Missing nonce parameter. The parameter is required');
		}

		// verify that the nonce is somewhat unique
		$found = $this->dataStore->lookupNonce(
			$consumer,
			$token,
			$nonce,
			$timestamp
		);

		if ($found)
		{
			throw new \Exception("Nonce already used: $nonce");
		}
	}
}
