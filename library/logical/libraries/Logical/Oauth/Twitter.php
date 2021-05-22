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
 * Class Twitter
 * @package Logical\Oauth
 */
class Twitter
{
	/**
	 * the last HTTP headers returned
	 * @var array
	 */
	protected $httpHeader = array();

	/**
	 * the last HTTP status code returned.
	 * @var string
	 */
	protected $httpStatusCode;

	/**
	 * Contains the last API call
	 * @var string
	 */
	protected $lastApiCall;

	/**
	 * the API root URL
	 * @var string
	 */
	protected $host = "https://api.twitter.com/1.1/";

	/**
	 * timeout default
	 * @var int
	 */
	protected $timeout = 30;

	/**
	 * connect timeout
	 * @var int
	 */
	protected $connectTimeout = 30;

	/**
	 * Verify SSL Cert
	 * @var bool
	 */
	protected $sslVerifypeer = false;

	/**
	 * Response format
	 * @var string
	 */
	protected $format = 'json';

	/**
	 * Decode returned json data
	 * @var bool
	 */
	protected $decodeJson = true;

	/**
	 * the user agent
	 * @var string
	 */
	protected $userAgent = 'TwitterOAuth v0.2.0-beta2';

	/**
	 * Associative array of API urls
	 * @var array
	 */
	protected $apiUrls = array();

	/**
	 * @var Signature
	 */
	protected $signature = null;

	/**
	 * @var Consumer
	 */
	protected $consumer = null;

	/**
	 * @var $token
	 */
	protected $token = null;

	/**
	 * Application bearer token
	 *
	 * @var string
	 */
	protected $bearer = null;

	/**
	 * construct TwitterOAuth object
	 *
	 * @param string  $consumerKey      The Application Consumer Key
	 * @param string  $consumerSecret   The Application Consumer Secret
	 * @param string  $oauthToken       The Client Token (optional)
	 * @param string  $oauthTokenSecret The Client Token Secret (optional)
	 */
	public function __construct($consumerKey, $consumerSecret, $oauthToken = null, $oauthTokenSecret = null)
	{
		$this->apiUrls = array(
			'access_token' => 'https://api.twitter.com/oauth/access_token',
			'authenticate' => 'https://twitter.com/oauth/authenticate',
			'authorize' => 'https://twitter.com/oauth/authorize',
			'request_token' => 'https://api.twitter.com/oauth/request_token'
		);

		$this->signature = new HmacSha1();

		$this->consumer = new Consumer($consumerKey, $consumerSecret);

		if (!empty($oauthToken) && !empty($oauthTokenSecret))
		{
			$this->token = new Token($oauthToken, $oauthTokenSecret);
		}

		if (!empty($oauthTokenSecret) && empty($oauthToken))
		{
			$this->bearer = $oauthTokenSecret;
		}
	}

	/**
	 * Method to get the last status code received by the API call
	 *
	 * @return string
	 */
	public function lastStatusCode()
	{
		return $this->httpStatusCode;
	}

	/**
	 * Method to return the last API call made
	 *
	 * @return string
	 */
	public function lastAPICall()
	{
		return $this->lastApiCall;
	}

	/**
	 * Get a request token from Twitter
	 *
	 * @param string $oauthCallback
	 *
	 * @return array key/value array containing oauth_token and oauth_token_secret
	 */
	public function getRequestToken($oauthCallback = null)
	{
		$parameters = array();

		if (!empty($oauthCallback))
		{
			$parameters['oauth_callback'] = $oauthCallback;
		}

		$request = $this->oAuthRequest($this->apiUrls['request_token'], 'GET', $parameters);
		$token = Utility::parseParameters($request);

		$this->token = new Token($token['oauth_token'], $token['oauth_token_secret']);

		return $token;
	}

	/**
	 * Get the authorize URL
	 *
	 * @param   string  $token
	 * @param   bool    $signInWithTwitter
	 *
	 * @return string
	 */
	public function getAuthorizeURL($token, $signInWithTwitter = true)
	{
		if (is_array($token))
		{
			$token = $token['oauth_token'];
		}

		if (empty($signInWithTwitter))
		{
			return  $this->apiUrls['authorize'] . "?oauth_token={$token}";
		}

		return  $this->apiUrls['authenticate'] . "?oauth_token={$token}";
	}

	/**
	 * Exchange request token and secret for an access token and
	 * secret, to sign API calls.
	 *
	 * @param bool $oauthVerifier
	 *
	 * @return array Example array("oauth_token" => "the-access-token", "oauth_token_secret" => "the-access-secret", "user_id" => "9436992", "screen_name" => "abraham")
	 */
	public function getAccessToken($oauthVerifier = false)
	{
		$parameters = array();

		if (!empty($oauthVerifier))
		{
			$parameters['oauth_verifier'] = $oauthVerifier;
		}

		$request = $this->oAuthRequest( $this->apiUrls['access_token'], 'GET', $parameters);
		$token = Utility::parseParameters($request);
		$this->token = new Consumer($token['oauth_token'], $token['oauth_token_secret']);

		return $token;
	}

	/**
	 * One time exchange of username and password for access token and secret.
	 *
	 * @param   string  $username
	 * @param   string  $password
	 *
	 * @return array Example array("oauth_token" => "the-access-token",
	 *                              "oauth_token_secret" => "the-access-secret",
	 *                              "user_id" => "9436992",
	 *                              "screen_name" => "abraham",
	 *                              "x_auth_expires" => "0")
	 */
	public function getXAuthToken($username, $password)
	{
		$parameters = array();
		$parameters['x_auth_username'] = $username;
		$parameters['x_auth_password'] = $password;
		$parameters['x_auth_mode'] = 'client_auth';
		$request = $this->oAuthRequest( $this->apiUrls['access_token'], 'POST', $parameters);
		$token = Utility::parseParameters($request);
		$this->token = new Consumer($token['oauth_token'], $token['oauth_token_secret']);

		return $token;
	}

	/**
	 * Method to make a GET request to oAuthRequest.
	 *
	 * @param   string  $url
	 * @param   array   $parameters
	 *
	 * @return  string|array
	 */
	public function get($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'GET', $parameters);

		if ($this->format === 'json' && $this->decodeJson)
		{
			return json_decode($response);
		}

		return $response;
	}

	/**
	 * Method to make a POST request to oAuthRequest.
	 *
	 * @param   string  $url
	 * @param   array   $parameters
	 *
	 * @return  string|array
	 */
	public function post($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'POST', $parameters);
		if ($this->format === 'json' && $this->decodeJson)
		{
			return json_decode($response);
		}
		return $response;
	}

	/**
	 * Method to make a DELETE wrapper for oAuthReqeust.
	 *
	 * @param   string  $url
	 * @param   array   $parameters
	 *
	 * @return  string|array
	 */
	public function delete($url, $parameters = array())
	{
		$response = $this->oAuthRequest($url, 'DELETE', $parameters);
		if ($this->format === 'json' && $this->decodeJson)
		{
			return json_decode($response);
		}
		return $response;
	}

	/**
	 * Format and sign an OAuth / API request
	 *
	 * @param   string  $url
	 * @param   string  $method
	 * @param   array   $parameters
	 *
	 * @return string
	 */
	public function oAuthRequest($url, $method, $parameters)
	{
		if (strrpos($url, 'https://') !== 0 && strrpos($url, 'http://') !== 0)
		{
			$url = "{$this->host}{$url}.{$this->format}";
		}

		$request = Request::fromConsumerToken($this->consumer, $this->token, $method, $url, $parameters);

		$request->signRequest($this->signature, $this->consumer, $this->token);

		if($method == 'GET')
		{
			return $this->http($request->toUrl(), 'GET');
		}

		return $this->http($request->getNormalizedHttpUrl(), $method, $request->toPostdata());

	}

	/**
	 * Make an HTTP request
	 *
	 * @param   string  $url
	 * @param   string  $method
	 * @param   string  $postFields
	 *
	 * @return string API esults
	 */
	public function http($url, $method, $postFields = null)
	{
		$this->httpHeader = array();
		$ci = curl_init();
		/* Curl settings */
		curl_setopt($ci, CURLOPT_USERAGENT, $this->userAgent);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
		curl_setopt($ci, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ci, CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, $this->sslVerifypeer);
		curl_setopt($ci, CURLOPT_HEADERFUNCTION, array($this, 'getHeader'));
		curl_setopt($ci, CURLOPT_HEADER,false);

		switch ($method)
		{
			case 'POST':
				curl_setopt($ci, CURLOPT_POST, true);
				if (!empty($postFields))
				{
					curl_setopt($ci, CURLOPT_POSTFIELDS, $postFields);
				}
				break;
			case 'DELETE':
				curl_setopt($ci, CURLOPT_CUSTOMREQUEST, 'DELETE');

				if (!empty($postFields))
				{
					$url = "{$url}?{$postFields}";
				}
		}

		curl_setopt($ci, CURLOPT_URL, $url);
		$response = curl_exec($ci);

		$this->httpStatusCode = curl_getinfo($ci, CURLINFO_HTTP_CODE);
		$this->httpHeader = array_merge($this->httpHeader, curl_getinfo($ci));
		$this->lastApiCall = $url;

		curl_close($ci);

		return $response;
	}

	/**
	 * Get the header info to store.
	 *
	 * @param  string  $header
	 *
	 * @return int
	 */
	public function getHeader($header)
	{
		$i = strpos($header, ':');

		if (!empty($i))
		{
			$key = str_replace('-', '_', strtolower(substr($header, 0, $i)));
			$value = trim(substr($header, $i + 2));
			$this->httpHeader[$key] = $value;
		}

		return strlen($header);
	}
}
