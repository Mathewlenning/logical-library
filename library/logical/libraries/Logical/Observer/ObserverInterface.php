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
 * Interface ObserverInterface
 *
 * @package  Logical\Observer
 *
 * @since    0.0.1
 */
interface ObserverInterface
{
	/**
	 * Creates the associated observer instance and attaches it to the $observableObject
	 *
	 * @param   ObservableInterface  $subject  to observe
	 * @param   array                $params   for this observer
	 *
	 * @return  ObservableInterface
	 *
	 * @since   0.0.1
	 */
	public static function createObserver(ObservableInterface $subject, $params = array());
}
