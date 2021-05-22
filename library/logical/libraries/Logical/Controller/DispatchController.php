<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Controller;

// No direct access
defined('_JEXEC') or die;

use Joomla\Event\Event;
use Logical\Registry\Registry;

use Exception;
use InvalidArgumentException;
use Joomla\Application\AbstractApplication;
use	JApplicationCms, Joomla\Event\Dispatcher, JFactory;
use JInput, JPluginHelper, JRoute, JText;

/**
 * Class Dispatcher
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class DispatchController extends BaseController
{
	/**
	 * The application object.
	 *
	 * @var    JApplicationCms
	 * @since  0.0.1
	 */
	protected $app;

	/**
	 * Uses the input to select the task controller and set the subject to the configuration
	 *
	 * @param   JInput            $input   The input object.
	 * @param   AbstractApplication  $app     The application object.
	 * @param   Registry          $config  Configuration
	 */
	public function __construct(JInput $input = null, AbstractApplication $app = null, Registry $config = null)
	{
		parent::__construct($input, $app, $config);

		$this->config->set('task', $this->config->get('task', $input->get('task', 'display', 'CMD')));
		$task = $this->config->get('task');

		if (empty($task))
		{
			$this->config->set('task', 'display');
		}

		$this->controller = $this->getController($this->config->get('task'), null, $input, $app, $this->config);
	}

	/**
	 * Method to get a controller
	 *
	 * @param   string               $name    of the controller to return
	 * @param   string               $prefix  using the format $prefix.'Controller'.$name
	 * @param   JInput               $input   to use in the constructor method
	 * @param   AbstractApplication  $app     to use in the constructor method
	 * @param   array                $config  to use in the constructor method, this is normalized using the calling classes config array.
	 *
	 * @return mixed
	 */
	protected function getController($name, $prefix = null, JInput $input = null, $app = null, $config = array())
	{
		$config = $this->normalizeConfig($config);

		if (strpos($name, '.'))
		{
			$name = explode('.', $name);
		}

		$resources = $config['resource'];

		if(strpos($resources, '.'))
		{
			$resources = explode('.', $resources);
		}

		// Make sure we have an array
		settype($name, 'array');
		settype($resources, 'array');

		$tasks = array_reverse($name);
		$resources = array_reverse($resources);

		$controller = null;
		$subController = null;

		foreach ($tasks AS $index => $task)
		{
			$config['task'] = $task;
			$config['resource'] = $resources[0];

			if (isset($resources[$index]))
			{
				$config['resource'] = $resources[$index];
			}

			if (is_null($prefix))
			{
				$prefix = $config['prefix'];
			}

			$class = ucfirst($prefix) . 'Controller' . ucfirst($task);

			if (!class_exists($class))
			{
				$class = $this->getFallbackController($task, $input);
			}

			/** @var BaseController $controller */
			$controller = new $class($input, $app, new Registry($config));
			$controller->setController($subController);
			$subController = $controller;
		}

		return $controller;
	}

	/**
	 * Method to get a the default task controller.
	 *
	 * Override this to use your own Fallback controller family.
	 *
	 * @param   string  $task   postfix task name
	 * @param   JInput  $input  The input object.
	 *
	 * @throws InvalidArgumentException
	 * @return string
	 */
	protected function getFallbackController($task, JInput $input = null)
	{
		$fallbackClass = '\Logical\Controller\\' . ucfirst($task) . 'Controller';

		if (!class_exists($fallbackClass))
		{
			$format = $input->getWord('format', 'html');
			throw new InvalidArgumentException(JText::sprintf('JLIB_APPLICATION_ERROR_INVALID_CONTROLLER', $fallbackClass, $format));
		}

		return $fallbackClass;
	}

	/**
	 * Proxy for $this->controller->execute()
	 *
	 * @return bool True if the controller executed successfully
	 * @throws Exception
	 */
	public function execute()
	{
		JPluginHelper::importPlugin('extension');

		$dispatcher = new \Logical\Event\Dispatcher(JFactory::getApplication());
		$event= new Event('onDispatchControllerExecute', array($this));
		$dispatcher->dispatch('onDispatchControllerExecute', $event);

		try
		{
			// sometimes we need to handle and error without an error message
			// This is used by LoginController because Joomla automatically sets the error message to the application
			if ($this->controller->execute() === false)
			{
				$this->handleDisplayError();

				return false;
			}
		}
		catch (Exception $e)
		{
			$this->addMessage($e->getMessage(), 'error');

			$this->getApplication()->setHeader('status', $e->getCode());

			$this->handleDisplayError();

			return false;
		}

		return true;
	}

	private function handleDisplayError()
	{
		$config = $this->config->toArray();
		$tasks = explode('.', $config['task']);

		if (!in_array('display', (array) $tasks))
		{
			return;
		}

		$menuItem = JFactory::getApplication()->getMenu()->getDefault();

		$id = 0;

		if (!is_null($menuItem))
		{
			$id = $menuItem->id;
		}

		$this->setReturn('index.php?Itemid=' . $id);
	}

	/**
	 * Redirects the browser or returns false if no redirect is set.
	 *
	 * @return  boolean  False if no redirect exists.
	 *
	 * @since   0.0.1
	 */
	public function redirect()
	{
		/** @var \JApplicationCms $app */
		$app = $this->getApplication();

		if (!empty($app->deferredMsg))
		{
			foreach ($app->deferredMsg AS $msg)
			{
				$app->enqueueMessage($msg['message'], $msg['type']);
			}
		}

		$config = $this->config->toArray();
		$tasks = explode('.', $config['task']);

		if (!in_array('display', (array) $tasks) && !$this->hasDefRedirect())
		{
			$defaultRedirect = 'index.php?option=' . $config['option'] . '&view=' . $config['view'] . '&layout=' . $config['layout'];

			$ids = $this->getIds();

			if ((!empty($ids[0])))
			{
				$defaultRedirect .= '&id=' . $ids[0];
			}

			$return = $this->getInput()->getBase64('return' ,null);

			if (!is_null($return))
			{
				$defaultRedirect = base64_decode($return);
			}

			$this->setReturn($defaultRedirect);
		}

		$dispatcher = new \Logical\Event\Dispatcher(JFactory::getApplication());
		$event= new Event('onBeforeDispatchControllerRedirect', array($this));
		$dispatcher->dispatch('onBeforeDispatchControllerRedirect', $event);

		if ($this->hasDefRedirect())
		{
			// Execute the redirect
			$app->redirect($app->defRedirect);
		}

		return false;
	}
}
