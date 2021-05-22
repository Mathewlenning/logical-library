<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Event;

// No direct access
use Joomla\Event\Event;
use Joomla\Application\AbstractApplication;

defined('_JEXEC') or die;

/**
 * Class Dispatcher
 * Comparability wrapper for Joomla 3.x and 4
 *
 * @package Logical\Event
 */
class Dispatcher
{
	/**
	 * @var object
	 */
	protected $dispatcher;

	/**
	 * Dispatcher constructor.
	 * @param AbstractApplication $app
	 */
	public function __construct($app)
	{
		if(method_exists($app, 'getDispatcher'))
		{
			$this->dispatcher = $app->getDispatcher();

			return;
		}

		$this->dispatcher = \JEventDispatcher::getInstance();
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
		if(($this->dispatcher instanceof \JEventDispatcher))
		{
			return $this->dispatcher->trigger($eventName, $event->getArguments());
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
		$event = new Event($eventName, $args);

		return $this->dispatch($eventName, $event);
	}
}
