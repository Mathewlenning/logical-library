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

use Logical\Registry\Registry;
use Logical\Model\BaseModel;

use ErrorException;
use Joomla\Application\AbstractApplication;
use JControllerBase;
use JFactory;
use JInput;
use JRoute;
use JSession;
use Jtext;

/**
 * Class BaseController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
abstract class BaseController extends JControllerBase
{
    /**
     * Configuration variables
     * @var array
     * @since  0.0.1
     */
    protected $config;

    /**
     * The Task Controller
     * @var BaseController
     */
    protected $controller;

    /**
     * Associative array of models
     * stored as $models[$prefix][$name] used by get models
     * @var array
     */
    protected $models = array();

    /**
     * Should we add input variable to the URL
     *
     * @var bool
     */
    protected $addUrlAddons = true;

    /**
     * Instantiate the controller.
     *
     * @param   JInput            $input   The input object.
     * @param   AbstractApplication  $app     The application object.
     * @param   Registry          $config  Configuration
     */
    public function __construct(JInput $input = null, AbstractApplication $app = null, Registry $config = null)
    {
        parent::__construct($input, $app);

        if (!($config instanceof Registry))
        {
            $config = new Registry($config);
        }

        // Set all the defaults for the config
        $config->set('option', $config->get('option', $this->getInput()->get('option')));
        $config->set('prefix', $config->get('prefix', $this->getPrefix($config->get('option'))));
        $config->set('default_view', $config->get('default_view', 'default'));
        $config->set('view', $config->get('view',  $this->getInput()->get('view', $config->get('default_view'))));
        $config->set('resource', $config->get('resource', $this->getInput()->get('resource', $config->get('view'), 'CMD')));
        $config->set('ignore_request', $config->get('ignore_request', $this->getInput()->get('ignore_request', false)));
        $config->set('layout', $config->get('layout', $this->getInput()->get('layout', 'default')));
        $config->set('tmpl', $config->get('tmpl',  $this->getInput()->get('tmpl', null)));
        $config->set('isAjax', $config->get('isAjax',  $this->isAjaxRequest()));
        $config->set('isHmvc', $config->get('isHmvc', false));

        $viewType = JFactory::getDocument()->getType();
        $config->set('viewType', $config->get('viewType', $viewType));
        $config->set('modal', $config->get('modal', $this->getInput()->get('modal', false, 'BOOLEAN')));

        $app = $this->getApplication();

        //Get the component configuration
        $component = \JComponentHelper::getComponent($config->get('option'));
        $params = $component->params->toArray();

        if ($app->isClient('site'))
        {
            $params = $app->getParams()->toArray();
        }

        $config->set('params', $params);

        $this->config = $config;
    }

    /**
     * Method to get the option prefix from the input
     *
     * @param   string  $option  component option string 'com_{componentName}'
     *
     * @return string ucfirst(substr($this->config['option'], 4));
     */
    protected function getPrefix($option = null)
    {
        if (is_null($option))
        {
            $option = $this->config['option'];
        }

        $prefix = ucfirst(substr($option, 4));

        return $prefix;
    }

    /**
     * Get the local configuration
     *
     * @param   boolean  $toArray  should we return the config as an object or an array?
     *
     * @return array
     *
     * @since 0.0.1
     */
    public function getConfig($toArray = false)
    {
        if (!$toArray)
        {
            return $this->config;
        }

        return $this->config->toArray();
    }

    /**
     * Method to get a model, creating it if it does not already exist.
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
     * @see   \Logical\Controller\Base::NormalizeConfig
     *
     * @since 0.0.1
     */
    public function getModel($name = null, $prefix = null, $config = array())
    {
        $config = $this->normalizeConfig($config);

        if (is_null($prefix))
        {
            $prefix = $config['prefix'];
        }

        if (is_null($name))
        {
            $name = $config['resource'];
        }

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
     * Method to insure all config variables are are included.
     * Intended to be used in getModel, getView and other factory methods
     * that can be passed a config array. Normalization is overwrite protected, so you only need to set context specific configuration details
     *
     * @param   array  $config  to normalize
     *
     * @return array normalized config array
     *
     * @since 0.0.2
     */
    protected function normalizeConfig($config)
    {
        // Safe merge. will not overwrite existing keys
        $controllerConfig = $this->config->toArray();

        if (($config instanceof Registry))
        {
            $config = $config->toArray();
        }

        $config += $controllerConfig;

        return $config;
    }

    /**
     * Method to add a messages to the deferred application queue
     *
     * @param   string  $msg      The message
     * @param   string  $msgType  The type of message ('message', 'warning', 'error')
     *
     * @return $this
     */
    public function addMessage($msg, $msgType = 'message')
    {
        /** @var \JApplicationCms $app */
        $app = $this->getApplication();

        if (!empty($app->deferredMsg))
        {
            $app->deferredMsg = array();
        }

        $app->deferredMsg[] = array('message' => $msg, 'type' => $msgType);

        return $this;
    }

    /**
     * Method to clear the deferred Application message queue
     */
    public function clearMessages()
    {
        $this->getApplication()->deferredMsg = array();
    }

    /**
     * Set a URL for application redirect.
     * This method sets the $app->defRedirect value.
     *
     * @param   string  $url        URL to redirect to.
     * @param   bool    $useJRoute  should we phrase the url with JRoute?
     *
     * @return  $this  Object to support chaining.
     *
     * @since   0.0.1
     */
    public function setReturn($url, $useJRoute = true)
    {
        if(!$this->addUrlAddons)
        {
            if ($useJRoute)
            {
                $url = JRoute::_($url, false);
            }

            $this->getApplication()->defRedirect = $url;

            return $this;
        }

        $config = $this->config->toArray();

        if ($config['modal'])
        {
            $url .= '&modal=true';

            $giveTo = $this->getInput()->get('giveTo', null, 'CMD');

            if (!is_null($giveTo))
            {
                $url .= '&giveTo=' . $giveTo;
            }
        }

        if (!is_null($config['tmpl']))
        {
            $url .= '&tmpl=' . $config['tmpl'];
        }

        $tab = $this->getInput()->get('tab', null, 'CMD');

        if(!is_null($tab))
        {
            $url .='&tab=' . $tab;
        }

        if ($useJRoute)
        {
            $url = JRoute::_($url, false);
        }

        $this->getApplication()->defRedirect = $url;

        return $this;
    }

    /**
     * Method to check if the defRedirect is set
     *
     * @return boolean
     *
     * @since 0.0.1
     */
    public function hasDefRedirect()
    {
        $app = $this->getApplication();

        if (isset($app->defRedirect) && !empty($app->defRedirect))
        {
            return true;
        }

        return false;
    }

    /**
     * Method to get the deferred redirect url from the application if one exists
     *
     * @return string  Empty string if redirect isn't set
     */
    public function getDefRedirect()
    {
        $app = $this->getApplication();

        if (empty($app->defRedirect))
        {
            return '';
        }

        return $app->defRedirect;
    }

    /**
     * Convenience method to check the session token.
     *
     * Tokens should be checked whenever a user submits data
     * from a form that could compromise security.
     *
     * @return bool
     *
     * @throws ErrorException
     * @since 0.0.1
     */
    protected function validateSession()
    {
        $token = JSession::getFormToken();

        if (!$this->getInput()->get($token, '', 'alnum'))
        {
            $this->setReturn('index.php');
            throw new ErrorException(JText::_('JINVALID_TOKEN'));
        }

        return true;
    }

    /**
     * Convenience method to refresh the session token to prevent the back button
     *
     * @return void
     *
     * @since 0.0.1
     */
    protected function refreshToken()
    {
        $session = JFactory::getSession();
        $session->getToken(true);
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
    protected function getUserState($key, $default = null)
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
     * Method to set a sub-controller
     *
     * @param   BaseController  $controller  the sub-conroller
     *
     * @return $this
     */
    public function setController(BaseController $controller = null)
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Method to execute the sub-controller
     *
     * @return bool
     */
    protected function executeController()
    {
        if (!($this->controller instanceof BaseController))
        {
            return true;
        }

        return $this->controller->execute();
    }

    /**
     * Method to get a clean set of IDs
     *
     * @param   int  $default  a default value
     *
     * @return array
     *
     * @since 0.0.1
     */
    protected function getIds($default = 0)
    {
        $input = $this->getInput();
        $cid = $this->cleanCid($input->post->get('cid', array($input->getInt('id', $default)), 'array'));

        return $cid;
    }

    /**
     * Method to cast all values in a cid array to integer values
     *
     * @param   array  $cid  array of id values
     *
     * @return array $cleanCid
     *
     * @since 0.0.1
     */
    protected function cleanCid($cid)
    {
        $cleanCid = array();

        foreach ((array) $cid AS $pk)
        {
            $cleanCid[] = (int) $pk;
        }

        return $cleanCid;
    }

    /**
     * Method to check if the request is an ajax request
     *
     * @return bool
     */
    protected function isAjaxRequest()
    {
        if (empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest')
        {
            return false;
        }

        return true;
    }

    /**
     * Method to set task redirects and add task specific messages
     *
     * @param  string     $task
     * @param  BaseModel  $model
     *
     * @return void
     */
    protected function completeTask($task, $model)
    {
        $url = $model->getTaskRedirect($task);
        $return = $this->getInput()->getBase64('return', false);

        if (!empty($url))
        {
            if ($return)
            {
                $url .= '&return=' . $return;
            }

            if ($task =='cancel' && $return)
            {
                $this->addUrlAddons = false;
                $url = base64_decode($return);
            }

            $this->setReturn($url);
        }
        else
        {
            if($return)
            {
                $url = JRoute::_(base64_decode($return));
                $this->setReturn($url);
            }
        }

        $taskMessage = $model->getTaskMessage($task);

        if (!empty($taskMessage))
        {
            $this->addMessage($taskMessage);
        }
    }
}
