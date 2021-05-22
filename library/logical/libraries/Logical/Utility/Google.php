<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Utility;


// No direct access
defined('_JEXEC') or die;

class Google
{
	public static function encodeAddress($address1, $addressCity, $addressState, $addressZip, $address2 = null, $addressName = null)
	{
		$address = $addressName;
		$address .= ' ' . trim($address1);

		if(!empty(trim($address2)))
		{
			$address .= ' ' . trim($address2);
		}

		$address .= ' ' . trim($addressCity) .',';
		$address .= ' ' . trim($addressState);
		$address .= ' ' . trim($addressZip);

		return urlencode(trim($address));
	}

	public static function getMapUrl($address, $apiKey, $zoom = '17')
	{
		$apiBase = 'https://www.google.com/maps/embed/v1/place?zoom='.$zoom;

		$query = '&q=' . $address;

		// normalize the input
		if(strpos($apiKey, '=') !== false)
		{
			$parts = explode('=',$apiKey);
			$apiKey = array_pop($parts);
		}

		$apiKey = '&key=' . $apiKey;

		return $apiBase.$query.$apiKey;
	}

	public static function getMap($url, $width = '100%', $height = '400', $allowFullScreen = false, $noBorder = true, $zoom = '17')
	{
		$html = '<iframe width="' . $width .'" height="'. $height .'" src="'. $url .'"';

		if ($noBorder)
		{
			$html .= ' frameborder="0" style="border:none;"';
		}

		if($allowFullScreen)
		{
			$html .= ' allowfullscreen';
		}

		$html .= '></iframe>';

		return $html;
	}
}
