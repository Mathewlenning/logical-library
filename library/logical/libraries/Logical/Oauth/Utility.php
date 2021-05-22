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
 * Class Utility
 * @package Logical\Oauth
 */
class Utility
{
	/**
	 * Method to get the current nonce
	 */
	public static function generateNonce()
	{
		$mt = microtime();
		$rand = mt_rand();

		return md5($mt . $rand); // md5s look nicer than numbers
	}

	/**
	 * Method to get the current timestamp
	 */
	public static function generateTimestamp()
	{
		return time();
	}

	/**
	 * Method to urlencode to rfc3986
	 *
	 * @param  mixed  $input
	 *
	 * @return array|mixed|string
	 */
	public static function urlencodeRFC3986($input)
	{
		if (is_array($input))
		{
			return array_map(array('Logical\Oauth\Utility', 'urlencodeRFC3986'), $input);
		}

		if (is_scalar($input))
		{
			return str_replace(
				'+',
				' ',
				str_replace('%7E', '~', rawurlencode($input))
			);
		}

		return '';
	}

	/**
	 * Method to decode from rfc3986
	 * @param $string
	 * @return string
	 */
	public static function urldecodeRFC3986($string)
	{
		return urldecode($string);
	}

	/**
	 * Method to turn the authorization header into parameters.
	 *
	 * @param string  $header
	 * @param bool    $only_allow_oauth_parameters
	 *
	 * @return array
	 */
	public static function splitHeader($header, $only_allow_oauth_parameters = true)
	{
		$pattern = '/(([-_a-z]*)=("([^"]*)"|([^,]*)),?)/';
		$offset = 0;
		$params = array();
		while (preg_match($pattern, $header, $matches, PREG_OFFSET_CAPTURE, $offset) > 0)
		{
			$match = $matches[0];
			$header_name = $matches[2][0];
			$header_content = (isset($matches[5])) ? $matches[5][0] : $matches[4][0];

			if (preg_match('/^oauth_/', $header_name) || !$only_allow_oauth_parameters)
			{
				$params[$header_name] = Utility::urldecodeRFC3986($header_content);
			}

			$offset = $match[1] + strlen($match[0]);
		}

		if (isset($params['realm']))
		{
			unset($params['realm']);
		}

		return $params;
	}

	/**
	 * Method to sort out headers for people who are not running apache
	 *
	 * @return array
	 */
	public static function getHeaders()
	{
		if (function_exists('apache_request_headers'))
		{
			// we need this to get the actual Authorization: header
			// because apache tends to tell us it doesn't exist
			$headers = apache_request_headers();

			// sanitize the output of apache_request_headers because
			// we always want the keys to be Cased-Like-This and arh()
			// returns the headers in the same case as they are in the
			// request
			$out = array();

			foreach( $headers AS $key => $value )
			{
				$key = str_replace(" ", "-", ucwords(strtolower(str_replace("-", " ", $key))));
				$out[$key] = $value;
			}

			return $out;
		}

		// otherwise we don't have apache and are just going to have to hope
		// that $_SERVER actually contains what we need
		$out = array();

		if (isset($_SERVER['CONTENT_TYPE']) )
		{
			$out['Content-Type'] = $_SERVER['CONTENT_TYPE'];
		}

		if (isset($_ENV['CONTENT_TYPE']) )
		{
			$out['Content-Type'] = $_ENV['CONTENT_TYPE'];
		}

		foreach ($_SERVER as $key => $value)
		{
			if (substr($key, 0, 5) == "HTTP_")
			{
				// this is chaos, basically it is just there to capitalize the first
				// letter of every word that is not an initial HTTP and strip HTTP
				// code from przemek
				$key = str_replace(
					" ",
					"-",
					ucwords(strtolower(str_replace("_", " ", substr($key, 5))))
				);
				$out[$key] = $value;
			}
		}

		return $out;
	}

	/**
	 * Method to phrase a string params into an associative array
	 * I.E. a=b&a=c&d=e -> array('a' => array('b','c'), 'd' => 'e')
	 *
	 * @param string $input
	 *
	 * @return array
	 */
	public static function parseParameters($input)
	{
		if (!isset($input) || !$input)
		{
			return array();
		}

		$pairs = explode('&', $input);

		$parsed_parameters = array();

		foreach ($pairs as $pair)
		{
			$split = explode('=', $pair, 2);

			$parameter = Utility::urldecodeRFC3986($split[0]);
			$value = isset($split[1]) ? Utility::urldecodeRFC3986($split[1]) : '';

			if (!isset($parsed_parameters[$parameter]))
			{
				$parsed_parameters[$parameter] = $value;

				continue;
			}

			if (!is_array($parsed_parameters[$parameter]))
			{
				$parsed_parameters[$parameter] = array($parsed_parameters[$parameter]);
			}

			$parsed_parameters[$parameter][] = $value;
		}

		return $parsed_parameters;
	}

	/**
	 * Method to build an http query
	 *
	 * @param  array $params
	 *
	 * @return string
	 */
	public static function buildHttpQuery($params)
	{
		if (!$params)
		{
			return '';
		}

		// Urlencode both keys and values
		$keys = Utility::urlencodeRFC3986(array_keys($params));
		$values = Utility::urlencodeRFC3986(array_values($params));
		$params = array_combine($keys, $values);

		// Parameters are sorted by name, using lexicographical byte value ordering.
		// Ref: Spec: 9.1.1 (1)
		uksort($params, 'strcmp');

		$pairs = array();
		foreach ($params as $parameter => $value)
		{
			if (!is_array($value))
			{
				$pairs[] = $parameter . '=' . $value;

				continue;
			}

			// If two or more parameters share the same name, they are sorted by their value
			natsort($value);

			foreach ($value as $duplicate_value)
			{
				$pairs[] = $parameter . '=' . $duplicate_value;
			}
		}

		// For each parameter, the name is separated from the corresponding value by an '=' character (ASCII code 61)
		// Each name-value pair is separated by an '&' character (ASCII code 38)
		return implode('&', $pairs);
	}
}
