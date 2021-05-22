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
 * Class Request
 * @package Logical\Oauth
 */
class Request
{
	private $parameters;

	private $httpMethod;

	private $httpUrl;

	public static $version = '1.0';

	public static $POST_INPUT = 'php://input';

	// for debug purposes
	public $base_string;

	/**
	 * Request constructor.
	 *
	 * @param   string  $httpMethod
	 * @param   string  $httpUrl
	 * @param   array   $parameters
	 */
	public function __construct($httpMethod, $httpUrl, $parameters = array())
	{
		$this->httpMethod = $httpMethod;
		$this->httpUrl = $httpUrl;

		$parameters = array_merge(Utility::parseParameters(parse_url($httpUrl, PHP_URL_QUERY)), $parameters);
		$this->parameters = $parameters;
	}

	/**
	 * Method to build a request from what was passed to the server
	 *
	 * @param   string    $http_method
	 * @param   string    $http_url
	 * @param   array     $parameters
	 *
	 * @return Request
	 */
	public static function fromRequest($http_method = null, $http_url = null, $parameters = null)
	{
		$scheme = (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] != "on")
			? 'http'
			: 'https';
		@$http_url or $http_url = $scheme .
			'://' . $_SERVER['HTTP_HOST'] .
			':' .
			$_SERVER['SERVER_PORT'] .
			$_SERVER['REQUEST_URI'];
		@$http_method or $http_method = $_SERVER['REQUEST_METHOD'];

		// We weren't handed any parameters, so let's find the ones relevant to
		// this request.
		// If you run XML-RPC or similar you should use this to provide your own
		// parsed parameter-list
		if (!$parameters)
		{
			// Find request headers
			$request_headers = Utility::getHeaders();

			// Parse the query-string to find GET parameters
			$parameters = Utility::parseParameters($_SERVER['QUERY_STRING']);

			// It's a POST request of the proper content-type, so parse POST
			// parameters and add those overriding any duplicates from GET
			if ($http_method == "POST"
				&& @strstr($request_headers["Content-Type"],
					"application/x-www-form-urlencoded")
			)
			{
				$post_data = Utility::parseParameters(file_get_contents(self::$POST_INPUT));
				$parameters = array_merge($parameters, $post_data);
			}

			// We have a Authorization-header with OAuth data. Parse the header
			// and add those overriding any duplicates from GET or POST
			if (@substr($request_headers['Authorization'], 0, 6) == "OAuth ")
			{
				$header_parameters = Utility::splitHeader($request_headers['Authorization']);
				$parameters = array_merge($parameters, $header_parameters);
			}

		}

		return new Request($http_method, $http_url, $parameters);
	}

	/**
	 * pretty much a helper function to set up the request
	 *
	 * @param   Consumer  $consumer
	 * @param   Token     $token
	 * @param   string    $http_method
	 * @param   string    $http_url
	 * @param   array     $parameters
	 *
	 * @return Request
	 */
	public static function fromConsumerToken($consumer, $token, $http_method, $http_url, $parameters = array())
	{
		$defaults = array(
			"oauth_version" => Request::$version,
			"oauth_nonce" => Utility::generateNonce(),
			"oauth_timestamp" => Utility::generateTimestamp(),
			"oauth_consumer_key" => $consumer->key
		);

		if ($token)
		{
			$defaults['oauth_token'] = $token->key;
		}

		$parameters = array_merge($defaults, $parameters);

		return new Request($http_method, $http_url, $parameters);
	}

	/**
	 * @param   string  $name
	 * @param   mixed   $value
	 * @param   bool    $allow_duplicates
	 */
	public function setParameter($name, $value, $allow_duplicates = true)
	{
		if ($allow_duplicates && isset($this->parameters[$name]))
		{
			// We have already added parameter(s) with this name, so add to the list
			if (is_scalar($this->parameters[$name]))
			{
				// This is the first duplicate, so transform scalar (string)
				// into an array so we can add the duplicates
				$this->parameters[$name] = array($this->parameters[$name]);
			}

			$this->parameters[$name][] = $value;
		}
		else
		{
			$this->parameters[$name] = $value;
		}
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 */
	public function getParameter($name)
	{
		return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
	}

	/**
	 * Method to get all the parameters
	 * @return array
	 */
	public function getParameters()
	{
		return $this->parameters;
	}

	/**
	 * Method to unset a parameter by name
	 *
	 * @param $name
	 */
	public function unsetParameter($name)
	{
		unset($this->parameters[$name]);
	}

	/**
	 * The request parameters, sorted and concatenated into a normalized string.
	 *
	 * @return string
	 */
	public function getSignableParameters()
	{
		// Grab all parameters
		$params = $this->parameters;

		// Remove oauth_signature if present
		// Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
		if (isset($params['oauth_signature']))
		{
			unset($params['oauth_signature']);
		}

		return Utility::buildHttpQuery($params);
	}

	/**
	 * Returns the base string of this request
	 *
	 * The base string defined as the method, the url
	 * and the parameters (normalized), each urlencoded
	 * and the concated with &.
	 */
	public function getSignatureBaseString()
	{
		$parts = array(
			$this->getNormalizedHttpMethod(),
			$this->getNormalizedHttpUrl(),
			$this->getSignableParameters()
		);

		$parts = Utility::urlencodeRFC3986($parts);

		return implode('&', $parts);
	}

	/**
	 * just uppercases the http method
	 */
	public function getNormalizedHttpMethod()
	{
		return strtoupper($this->httpMethod);
	}

	/**
	 * parses the url and rebuilds it to be
	 * scheme://host/path
	 */
	public function getNormalizedHttpUrl()
	{
		$parts = parse_url($this->httpUrl);

		$port = @$parts['port'];
		$scheme = $parts['scheme'];
		$host = $parts['host'];
		$path = @$parts['path'];

		$port or $port = ($scheme == 'https') ? '443' : '80';

		if (($scheme == 'https' && $port != '443')
			|| ($scheme == 'http' && $port != '80')
		)
		{
			$host = "$host:$port";
		}
		return "$scheme://$host$path";
	}

	/**
	 * builds a url usable for a GET request
	 */
	public function toUrl()
	{
		$post_data = $this->toPostdata();
		$out = $this->getNormalizedHttpUrl();
		if ($post_data)
		{
			$out .= '?' . $post_data;
		}
		return $out;
	}

	/**
	 * builds the data one would send in a POST request
	 */
	public function toPostdata()
	{
		return Utility::buildHttpQuery($this->parameters);
	}

	/**
	 * builds the Authorization: header
	 *
	 * @param null $realm
	 *
	 * @return string
	 *
	 * @throws \Exception
	 */
	public function toHeader($realm = null)
	{
		$first = true;
		if ($realm)
		{
			$out = 'Authorization: OAuth realm="' . Utility::urlencodeRFC3986($realm) . '"';
			$first = false;
		}
		else
		{
			$out = 'Authorization: OAuth';
		}

		foreach ($this->parameters as $k => $v)
		{
			if (substr($k, 0, 5) != "oauth")
			{
				continue;
			}

			if (is_array($v))
			{
				throw new \Exception('OAuth error arrays not supported in headers');
			}

			$out .= ($first) ? ' ' : ',';
			$out .= Utility::urlencodeRFC3986($k) . '="' . Utility::urlencodeRFC3986($v) . '"';
			$first = false;
		}

		return $out;
	}

	/**
	 * Method to convert to a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->toUrl();
	}

	/**
	 * Method to sign the request
	 *
	 * @param   Signature  $signature_method
	 * @param   Consumer   $consumer
	 * @param   Token      $token
	 */
	public function signRequest($signature_method, $consumer, $token)
	{
		$this->setParameter(
			"oauth_signature_method",
			$signature_method->getName(),
			false
		);
		$signature = $this->buildSignature($signature_method, $consumer, $token);
		$this->setParameter("oauth_signature", $signature, false);
	}

	/**
	 * Method to build the signature using the signature object
	 *
	 * @param   Signature  $signature_method
	 * @param   Consumer   $consumer
	 * @param   Token      $token
	 *
	 * @return mixed
	 */
	public function buildSignature($signature_method, $consumer, $token)
	{
		$signature = $signature_method->buildSignature($this, $consumer, $token);
		return $signature;
	}
}
