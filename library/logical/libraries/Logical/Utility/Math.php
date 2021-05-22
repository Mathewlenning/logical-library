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

class Math
{
	/**
	 * Method to always round up to the nearest precision
	 *
	 * @param   float  $number     number to round
	 * @param   int    $precision  the precision to round to
	 * @return float
	 */
	static public function roundUp($number,$precision = 0)
	{
		$fig = (int) str_pad('1',$precision, '0');
		return (ceil($number * $fig) / $fig);
	}

	/**
	 * Method to get a percentage
	 * @param  int $value the number being converted
	 * @param  int $total the total number in the set
	 * @param int $scaleMin is used for converting a scale the smallest value in the scale (I.E. 1~5 scale would be 1)
	 *
	 * @return float|int
	 */
	static public function getPercentage($value, $total, $scaleMin = 0)
	{
		if($value <= 0 || $total <= 0)
		{
			return 0;
		}

		$percentage = (($value - $scaleMin)/$total)*100;

		return $percentage;
	}
}
