<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Observer;

use Joomla\Event\Event;
use Logical\Event\Dispatcher;
use Logical\Observable\ObservableInterface;

/**
 * Observer updater pattern implementation
 *
 * @package  Logical\Observer
 *
 * @since    0.0.1
 **/
class ObserverUpdater implements ObserverUpdaterInterface
{
	/**
	 * Observer Events Toggle
	 *
	 * @var    boolean
	 *
	 * @since  0.0.8
	 */
	protected $eventsEnabled = true;

	/**
	 * Array of observers
	 *
	 * @var    array
	 *
	 * @since  0.0.8
	 */
	protected $observers = array();

    /**
     * @var Dispatcher
     */
	protected $dispatcher;

	/**
	 * Constructor
	 *
	 * @param   ObservableInterface $subject to observe
	 *
	 * @since   0.0.1
	 */
	public function __construct(ObservableInterface $subject)
	{
		// nothing to do
	}

	public function setDispatcher($dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

	public function enableEvents()
	{
		$this->eventsEnabled = true;
	}

	public function disableEvents()
	{
		$this->eventsEnabled = false;
	}

	/**
	 * Adds an observer to the subject
	 *
	 * @param ObserverInterface $observer object
	 *
	 * @return  void
	 *
	 * @since   0.0.8
	 */
	public function attachObserver(ObserverInterface $observer)
	{
		// name spaced to prevent duplication
		$this->observers[get_class($observer)] = $observer;
	}

	/**
	 * Gets the instance of the observer of class $observerClass, if it exists
	 *
	 * @param   string  $observerClass  The class name of the observer
	 *
	 * @return  ObserverInterface  The observer object or null
	 *
	 * @since   0.0.8
	 */
	public function getObserverOfClass($observerClass)
	{
		if (!isset($this->observers[$observerClass]))
		{
			return null;
		}

		return $this->observers[$observerClass];
	}

	public function update($event, $params = array())
	{
		if (!$this->eventsEnabled)
		{
			return;
		}

		foreach ($this->observers as $observer)
		{
			$eventListener = array($observer, $event);

			$this->callEvent($eventListener, $params);
		}
	}

	protected function callEvent($eventListener, $params)
	{
		if (is_callable($eventListener))
		{
			call_user_func_array($eventListener, $params);
		}
	}

    /**
     * Pass through for newer event calls
     *
     * @param   string  $eventName
     * @param   Event   $event
     *
     * @return mixed
     */
    public function dispatch($eventName, $event)
    {
        if (!$this->eventsEnabled)
        {
            return;
        }

        return $this->dispatcher->dispatch($eventName, $event);
    }

    /**
     * Pass through for older event calls
     *
     * @param   string  $eventName
     * @param   array   $args
     *
     * @return mixed
     */
    public function trigger($eventName, $args)
    {
        if (!$this->eventsEnabled)
        {
            return;
        }

        return $this->dispatcher->trigger($eventName, $args);
    }

    public function getError($i = null, $toString = true)
    {
        if(!method_exists($this->dispatcher, 'getError'))
        {
            return 'Error messages not supported';
        }

        return $this->dispatcher->getError($i, $toString);
    }
}
