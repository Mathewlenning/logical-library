<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Model;

// No direct access
defined('_JEXEC') or die;

use Joomla\Event\Event;
use Logical\Access\AccessInterface;
use Logical\Observable\ObservableInterface;
use Logical\Observer\ObserverInterface;
use Logical\Observer\ObserverUpdater;
use Logical\Observer\ObserverMapper;
use Logical\Registry\Registry;

use Exception;
use ErrorException;
use JFactory;
use JUser;
use JCache;
use Joomla\Event\Dispatcher;
use JPluginHelper;
use JText;

/**
 * Class BaseModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.1
 */
abstract class BaseModel implements ObservableInterface, AccessInterface
{
	/**
	 * Configuration array
	 *
	 * @var Registry
	 */
	protected $config;

	/**
	 * @var \Logical\Event\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * Generic Observer Updater for table (Used e.g. for tags Processing)
	 *
	 * @var    ObserverUpdater
	 * @since  0.0.8
	 */
	protected $observers;

	/**
	 * Associative array of models
	 * stored as $models[$prefix][$name] used by get model
	 * @var array
	 */
	protected $models = array();

	/**
	 * List of permission messages
	 * Format [$permissionName => JTEXT_TRANSLATION_KEY]
	 *
	 * @var array
	 */
	protected $accessErrorMessages = array();

	/**
	 * List of task messages used when a task is successfully completed
	 * Format [$task => JTEXT_TRANSLATION_KEY]
	 *
	 * @var array
	 */
	protected $taskMessages = array();

	/**
	 * Constructor gets the name of the model
	 *
	 * @param   Registry  $config  Configuration
	 *
	 * @throws Exception
	 */
	public function __construct(Registry $config = null)
	{
		$r = null;

		if (!preg_match('/Model(.*)/i', get_class($this), $r))
		{
			throw new Exception('LOGICAL_MODEL_ERROR_COULD_NOT_DETERMINE_NAME', 500);
		}

		if (empty($config) || !($config instanceof Registry))
		{
			$config = new Registry($config);
		}

		$config->set('resource', strtolower($r[1]));
		$this->config = $config;

		$this->dispatcher = $config->get('dispatcher', new \Logical\Event\Dispatcher(JFactory::getApplication()));

		$this->observers = new ObserverUpdater($this);
		$this->observers->setDispatcher($this->dispatcher);

		ObserverMapper::attachAllObservers($this);

		$this->config['class'] = get_class($this);

		// @todo make this work off the component access.xml
		$accessErrorMessages = array();
		$accessErrorMessages['core.admin'] = 'LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL';
		$accessErrorMessages['core.manage'] = 'LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL';
		$accessErrorMessages['core.create'] = 'LOGICAL_ACL_ERROR_CREATE_RECORD_NOT_PERMITTED';
		$accessErrorMessages['core.delete'] = 'LOGICAL_ACL_ERROR_DELETE_NOT_PERMITTED';
		$accessErrorMessages['core.edit'] = 'LOGICAL_ACL_ERROR_EDIT_RECORD_NOT_PERMITTED';
		$accessErrorMessages['core.edit.state'] = 'LOGICAL_ACL_ERROR_EDIT_STATE_NOT_PERMITTED';
		$accessErrorMessages['core.import'] = 'LOGICAL_ACL_ERROR_IMPORT_NOT_PERMITTED';
		$accessErrorMessages['core.export'] = 'LOGICAL_ACL_ERROR_EXPORT_NOT_PERMITTED';

		$this->accessErrorMessages = $accessErrorMessages;
	}

	/**
	 * Method to get the models configuration array
	 *
	 * @return Registry
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * Method to authorise the current user for an action.
	 * This method is intended to be overridden to allow for customized access rights
	 *
	 * @param string $action      ACL action string. I.E. 'core.create'
	 * @param string $assetName   Asset name to check against.
	 * @param int    $pk          Primary key to check against
	 * @param int    $impersonate User id to impersonate, null to use the current user
	 *
	 * @return bool
	 *
	 * @see JUser::authorise
	 */
	public function allowAction($action, $assetName = null, $pk = null, $impersonate = null)
	{
		if (is_null($assetName))
		{
			$assetName = $this->getContext();
		}

		$responseObject = new \stdClass();

		/** @var JUser $user */
		$responseObject->user = JFactory::getUser($impersonate);

		// gives me an opportunity to wrap the user object if needed.
		$dispatcher = $this->getDispatcher();
		$dispatcher->trigger('onBeforeLogicalAuthorise', array($this, $action, $assetName, $pk, $impersonate, $responseObject));

		return $responseObject->user->authorise($action, $assetName);
	}

	/**
	 * Method to get a translated access denied message based on the action being denied
	 *
	 * @param   string  $action     ACL action string. I.E. 'core.create'
	 *
	 * @return string
	 */
	public function getAccessDeniedMessage($action)
	{
		$msg = \JText::_('LOGICAL_ACL_ERROR_INSUFFICIENT_ACCESS_LEVEL');

		if(!empty($this->accessErrorMessages[$action]))
		{
			$msg = \JText::_($this->accessErrorMessages[$action]);
		}

		if(\JFactory::getUser()->guest)
		{
			$msg .= ' ' . \JText::_('LOGICAL_ACL_ERROR_LOGIN_REQUIRED');
		}

		return $msg;
	}

	/**
	 * Method to get messages for completed tasks.
	 * This allows the model to override the default system messages via the $taskMessage property
	 *
	 * @param   string  $task
	 *
	 * @return mixed
	 */
	public function getTaskMessage($task)
	{
		$msg = JText::_('LOGICAL_CONTROLLER_MESSAGE_'. strtoupper($task) .'_COMPLETED');

		if(!empty($this->taskMessages[$task]))
		{
			$msg = \JText::_($this->taskMessages[$task]);
		}


		if(!$this->needsTaskMessage($task))
		{
			return '';
		}

		return $msg;
	}

	/**
	 * Method to check if this task needs a message
	 * This method is intended to be overridden to allow fine grain control over system messages.
	 *
	 * @param   string  $task
	 *
	 * @return bool
	 */
	protected function needsTaskMessage($task)
	{
		$excluded = array('add', 'edit', 'cancel');

		if(in_array($task, $excluded))
		{
			return false;
		}

		return true;
	}

	/**
	 * Method to get a redirect URL by task
	 *
	 * @param   string  $task
	 *
	 * @return  string  URL to redirect
	 */
	public function getTaskRedirect($task)
	{
		return '';
	}

	/**
	 * Method to get the model context.
	 * $context = $config['option'].'.'.$config['resource'];
	 *
	 * @return string
	 */
	public function getContext()
	{
		$config = $this->config;

		return $config->get('option') . '.' . $config->get('resource');
	}

	/**
	 * Clean the cache
	 *
	 * @param   string   $group      The cache group
	 * @param   integer  $client_id  The ID of the client
	 *
	 * @return  void
	 *
	 * @since   0.0.1
	 */
	protected function cleanCache($group = null, $client_id = 0)
	{
		$localConfig = $this->config;

		$options = array();

		$options['defaultgroup'] = $localConfig['option'];

		if ($group)
		{
			$options['defaultgroup'] = $group;
		}

		$options['cachebase'] = JPATH_ADMINISTRATOR . '/cache';

		if ($client_id === 0)
		{
			$globalConfig = JFactory::getConfig();
			$options['cachbase'] = $globalConfig->get('cache_path', JPATH_SITE . '/cache');
		}

		$cache = JCache::getInstance('callback', $options);
		$cache->clean();

		// Trigger the onContentCleanCache event.
		$dispatcher = $this->getDispatcher();
		$event = new Event('onContentCleanCache', $options);
		$dispatcher->dispatch('onContentCleanCache', $event);
	}

    /**
     * Method to get a dispatcher
     *
     * @param array $groups (OPTIONAL) array of plugin groups to import
     *
     * @return ObserverUpdater
     */
	public function getDispatcher($groups = array())
	{
		if (!is_array($groups))
		{
			$groups = array($groups);
		}

		foreach ($groups AS $pluginGroup)
		{
			JPluginHelper::importPlugin($pluginGroup);
		}

		return $this->observers;
	}

	/**
	 * Method to validate the results of a plugin event
	 *
	 * @param   ObserverUpdater  $dispatcher  used to trigger the event
	 * @param   array            $results     results returned by the event
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function validateEventResults($dispatcher, $results)
	{
		// Check for errors encountered while preparing the form.
		if (count($results) && in_array(false, $results, true))
		{
			// Get the last error.
			$error = $dispatcher->getError();

			if (!($error instanceof \Exception))
			{
				throw new \Exception($error);
			}
		}

		return true;
	}

	/**
	 * Method to attach an observer to the model
	 *
	 * @param   ObserverInterface  $observer  observer instance
	 *
	 * @return void
	 */
	public function attachObserver(ObserverInterface $observer)
	{
		$this->observers->attachObserver($observer);
	}

	/**
	 *  Method to enable observer events
	 *
	 * @return void
	 */
	public function enableEvents()
	{
		$this->observers->enableEvents();
	}

	/**
	 * Method to disable observer events
	 *
	 * @return void
	 */
	public function disableEvents()
	{
		$this->observers->disableEvents();
	}

	/**
	 * Utility method used to convert raw data to a class
	 *
	 * @param   mixed   $data   to be converted
	 * @param   string  $class  the name of the class
	 *
	 * @return mixed if the $data is not already an instance of $class, return new $class($data)
	 */
	protected function toObject($data, $class = 'stdClass')
	{
		if (!($data instanceof $class))
		{
			return new $class($data);
		}

		return $data;
	}

	/**
	 * Method to get a model.
	 * Uses the prefix and $name to create the class name. Format $prefix.'Model'.$name
	 * If null default values are taken from $config array
	 *
	 * @param   string  $name    The name of the model.
	 * @param   string  $prefix  name of the component without 'com_', Defaults to $this->getPrefix();
	 * @param   array   $config  configuration array. This array is normalized. So you only need to send context specific configuration details.
	 *
	 * @return BaseModel
	 *
	 * @throws ErrorException
	 */
	public function getModel($name, $prefix, $config = array())
	{
		$config += $this->getConfig()->toArray();

		$prefix = ucfirst($prefix);
		$name = ucfirst($name);

		if (isset($this->models[$prefix][$name]))
		{
			return $this->models[$prefix][$name];
		}

		$class = $prefix . 'Model' . $name;

		if (!class_exists($class))
		{
			throw new ErrorException(JText::sprintf('JLIB_APPLICATION_ERROR_MODELCLASS_NOT_FOUND', $class));
		}

		$this->models[$prefix][$name] = new $class(new Registry($config));

		return $this->models[$prefix][$name];
	}

	/**
	 * Method to save the user input into state.
	 * This is intended to be used to preserve form data when server side validation fails
	 *
	 * @param   string  $key   dot delimited string format $context.$dataIdentifier
	 * @param   mixed   $data  the data to store
	 *
	 * @return void
	 */
	protected function setUserState($key = null, $data = null)
	{
		if (!is_null($key))
		{
			$session = JFactory::getSession();
			$registry = $session->get('registry');

			if (!is_null($registry))
			{
				$registry->set($key, $data);
			}
		}
	}

	/**
	 * Method to get the users session state
	 *
	 * @param   string  $key      the name of the state variable
	 * @param   mixed   $default  return value if the state isn't set
	 *
	 * @return mixed
	 */
	public function getUserState($key, $default = null)
	{
		$session = JFactory::getSession();
		$registry = $session->get('registry');

		if (!is_null($registry))
		{
			return $registry->get($key, $default);
		}

		return null;
	}

	/**
	 * Method to perform any conversion on values before use in search replace operations
	 *
	 * @param string $key
	 * @param string $value
	 *
	 * @return string converted value
	 */
	public function convertValue($key, $value)
	{
		return $value;
	}
}
