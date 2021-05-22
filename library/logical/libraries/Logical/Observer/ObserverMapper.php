<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Observer;

use Logical\Observable\ObservableInterface;

/**
 * Class ObserverMapper
 *
 * @package Logical\Observer
 *
 * @since 0.0.1
 */
class ObserverMapper implements ObserverMapperInterface
{
	/**
	 * Associative array of observer/observable relationships
	 * Format
	 * $observationMap[JObservableClassName] = array( JObserverClassName => array( paramname => param, .... ))
	 *
	 * @var    array
	 * @since  0.0.1
	 */
	 static public $observationMap = array();

	public static function addObserverMap($observerClass, $observableClass, $params = array())
	{
		static::$observationMap[$observableClass][$observerClass] = $params;
	}

	public static function removeObserverMap($observerClass, $observableClass)
	{
		unset(static::$observationMap[$observableClass][$observerClass]);
	}

	public static function attachAllObservers(ObservableInterface $observableInstance)
	{
		$className = get_class($observableInstance);

		$map = static::$observationMap;

		// Attach applicable Observers for the class to the Observable subject:
		if (!isset($map[$className]))
		{
			return;
		}

		foreach ($map[$className] as $observerClass => $params)
		{
			// Attach an Observer to the Observable subject:
			/**
			 * @var ObserverInterface $observerClass
			 */
			$observerClass::createObserver($observableInstance, $params);
		}
	}
}
