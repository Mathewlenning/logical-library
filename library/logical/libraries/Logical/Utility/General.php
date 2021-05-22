<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Utility;

// No direct access
use Logical\Model\BaseModel;
use Logical\Registry\Registry;

defined('_JEXEC') or die;

class General
{
	/**
	 * Method to create a flat structured array for search replace operations
	 * @param array|object  $item
	 * @param string        $prefix
	 * @param BaseModel     $converter
	 *
	 * @return array
	 */
	public static function getSearchReplace($item, $prefix = '', $converter = null)
	{
		$search = array();
		$replace = array();

		foreach ($item AS $key => $value)
		{
			if (!empty($prefix))
			{
				$key = $prefix . '[' . $key .']';
			}

			if($value instanceof Registry)
			{
				$value = $value->toArray();
			}

			if (is_object($value))
			{
				$value = get_object_vars($value);
			}

			if (is_array($value))
			{
				// if we're dealing with a Register::toArray value
				if(array_key_exists('data', $value)
					&& array_key_exists('initialized', $value)
					&& array_key_exists('separator', $value))
				{
					$value = $value['data'];
				}

				$searchReplace = self::getSearchReplace($value, $key, $converter);

				foreach ($searchReplace['search'] AS $index => $newKey)
				{
					$search[] = $newKey;
					$replace[] = $searchReplace['replace'][$index];
				}

				continue;
			}

			$search[] = '%' . $key . '%';

			if (is_callable(array($converter, 'convertValue')))
			{

				$value = $converter->convertValue('%'. $key .'%', $value);
			}

			$replace[] = $value;
		}

		return array('search' => $search, 'replace' => $replace);
	}

    /**
     * Method to clean an array of items to insure it processes properly for CSV format
     *
     * @param   array  $item            to clean
     *
     * @return array
     */
    public static function cleanCSV($item = array())
    {
        foreach ($item as $name => $property)
        {
            $item[$name] = '"' . str_replace('"', '&quot;', str_replace(array("\n", "\r", "\r\n", "\n\r"), '', trim($property))) . '"';
        }

        return $item;
    }

    /**
     * Method to reverse engineer an address string to its component parts
     *
     * @param $address
     *
     * @return array
     */
    public static function segmentAddressString($address)
    {
        $parts = explode(',', $address);

        $addressParts = explode(' ', trim($parts[0]));

        $stateZipParts = explode(' ', trim($parts[1]));

        $segMentaddress = array();
        $segMentaddress['state'] = $stateZipParts[0];
        $segMentaddress['zip'] = $stateZipParts[1];
        $segMentaddress['city'] = array_pop($addressParts);
        $segMentaddress['address1'] = implode(' ', $addressParts);
        $segMentaddress['address2'] = '';

        return $segMentaddress;
    }
}
