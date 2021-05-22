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
 * Interface ObserverUpdaterInterface
 *
 * @package Logical\Observer
 * @subpackage Updater
 *
 * @since 0.0.1
 */
interface ObserverUpdaterInterface
{
	/**
	 * Constructor
	 *
	 * @param   ObservableInterface $subject to observe
	 *
	 * @since   0.0.8
	 */
	public function __construct(ObservableInterface $subject);

	/**
	 * Enable Observer Event Calls
	 *
	 * @return boolean
	 *
	 * @since   0.0.8
	 */
	public function enableEvents();

	/**
	 * Disable Observer Event Calls
	 *
	 * @return boolean
	 *
	 * @since   0.0.8
	 */
	public function disableEvents();

	/**
	 * Adds an observer to the subject
	 *
	 * @param ObserverInterface $observer object
	 *
	 * @return  void
	 *
	 * @since   0.0.8
	 */
	public function attachObserver(ObserverInterface $observer);

	/**
	 * Call all observers for $event with $params
	 *
	 * @param   string  $event   Event name (function name in observer)
	 * @param   array   $params  Params for the event(observer function)
	 * <br/><b>NOTE</b>: that the subject is always prepended as the first param
	 *
	 * @return  void
	 *
	 * @since   0.0.8
	 */
	public function update($event, $params = array());

}
