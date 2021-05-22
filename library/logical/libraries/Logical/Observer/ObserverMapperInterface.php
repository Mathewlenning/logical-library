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
 * Interface ObserverMapperInterface
 *
 * @package Logical\Observer
 *
 * @since 0.0.1
 */
interface ObserverMapperInterface
{
	/**
	 * Adds a mapping to observe $observerClass subjects with $observableClass observer/listener, attaching it on creation with $params
	 * on $observableClass instance creations
	 *
	 * @param   string         $observerClass    The name of the observer class (implementing ObserverInterface)
	 * @param   string         $observableClass  The name of the observable class (implementing ObservableInterface)
	 * @param   array|boolean  $params           The params to give to the ObserverInterface::createObserver() function
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	public static function addObserverMap($observerClass, $observableClass, $params = array());

	/**
	 * Method to remove a observer/observable relationship map
	 *
	 * @param   string         $observerClass    The name of the observer class (implementing ObserverInterface)
	 * @param   string         $observableClass  The name of the observable class (implementing ObservableInterface)
	 *
	 * @return  void
	 *
	 * @since 0.0.1
	 */
	public static function removeObserverMap($observerClass, $observableClass);

	/**
	 * Attaches all applicable observers to an $observableObject
	 *
	 * @param   ObservableInterface  $observableInstance to observe
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	public static function attachAllObservers(ObservableInterface $observableInstance);
}
