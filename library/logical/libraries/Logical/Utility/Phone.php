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

class Phone
{
	public static function format_phone_us($phone)
	{
		// note: making sure we have something
		if (empty($phone))
		{
			return 'Invalid phone number';
		}
		// note: strip out everything but numbers
		$phone = preg_replace("/[^0-9]/", "", $phone);
		$length = strlen($phone);

		switch($length)
		{
			case 7:
				return preg_replace("/([0-9]{3})([0-9]{4})/", "$1-$2", $phone);
				break;
			case 10:

				return preg_replace("/([0-9]{3})([0-9]{3})([0-9]{4})/", "($1) $2-$3", $phone);
				break;
			case 11:
				return preg_replace("/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$1($2) $3-$4", $phone);
				break;
			default:
				return $phone;
				break;
		}
	}
}
