<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Observable;

// No direct access
defined('_JEXEC') or die;

use Logical\Observer\ObserverInterface;

/**
 * Interface ObservableInterface
 *
 * @package  Logical\Observable
 *
 * @since    0.0.1
 */
interface ObservableInterface
{
	/**
	 * Adds an observer to this subject.
	 *
	 * @param   ObserverInterface  $observer  to attach to $this observable subject
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	public function attachObserver(ObserverInterface $observer);
}
