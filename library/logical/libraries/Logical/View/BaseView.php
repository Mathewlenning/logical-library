<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\View;

// No direct access
defined('_JEXEC') or die;

use Logical\Access\AccessInterface;
use Logical\Access\UserAccess;
use Logical\Version\Version;
use Logical\Controller\BaseController;
use Logical\Registry\Registry;
use Logical\Widget\WidgetRenderer;
use Logical\Widget\WidgetRendererInterface;
use Logical\Html\Toolbar;

use ErrorException;
use Exception;
use JFactory;
use JApplicationSite;
use JHtml,JPath,JText, JInput, JToolbarHelper;
use Joomla\Registry\Registry as JRegistry;

/**
 * Class BaseView
 *
 * @package  Logical\View
 * @since    0.0.1
 */
abstract class BaseView
{
	/**
	 * @var string
	 */
	public static $version;

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	public $config = array();

	/**
	 * @var array
	 */
	protected $adminPaths;

	/**
	 * @var array
	 */
	protected $sitePaths;

	/**
	 * Associative array of paths to search for template files in
	 *
	 * @var array
	 */
	protected $paths = array();

	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * Default form URL
	 *
	 * @var string
	 */
	protected $formUrl;

	/**
	 * Layout name
	 *
	 * @var    string
	 */
	protected $layout = 'default';

	/**
	 * @var JRegistry
	 */
	protected $params;

	/**
	 * Array of template location URI generated during loadTemplate
	 * So we don't have to keep looking them up.
	 *
	 * @var array
	 */
	protected $templateLocations = array();

	/**
	 * @var Toolbar
	 */
	protected $toolbar;

	/**
	 * Id of the object to send data to in parent window
	 * This is used for modal iframes
	 * Not sure if this is needed anymore
	 *
	 * @var null
	 *
	 * @deprecated use Bootstrap modal and ajax request instead
	 */
	protected $giveTo = null;

	/**
	 * @var bool
	 *
	 * @deprecated
	 */
	protected $isModal = false;

    /**
     * @var bool Should we look for layouts in the admin area from the site?
     */
	protected $useAdminLayouts = false;

	/**
	 * Constructor
	 * prepares the base form URL from the given config
	 *
	 * @param   array $config configuration array
	 *
	 * @throws Exception
	 */
	public function __construct($config = array())
	{
		if (is_null(static::$version))
		{
			static::$version = new Version($config['option']);
		}

		$this->config = $config;
		$this->setPaths($config);
		$this->isModal = $config['modal'];

		if (empty($this->formUrl))
		{
			$this->formUrl = 'index.php?option=' . $config['option'] . '&view=' . $config['view'];
		}

		// Set the layout
		if (array_key_exists('layout', $config))
		{
			$this->layout = $config['layout'];
		}

		if ($this->layout != 'default')
		{
			$this->formUrl .= '&layout=' . $this->layout;
		}

		if (array_key_exists('params', $config))
		{
			$this->params = new Registry($config['params']);
		}
	}

	/**
	 * Method to set the template paths.
	 * You can set your own by adding an array of paths to $config['templates']
	 *
	 * @param   array $config configuration array
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function setPaths($config = array())
	{
		$class          = get_class($this);
		$flags          = PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE;
		$directoryArray = preg_split('/(?=[A-Z])/', $class, null, $flags);
		$type           = $directoryArray[(count($directoryArray) - 1)];
		$component      = $this->clean(array_shift($directoryArray));

		$path_to_file = '/';

		foreach ($directoryArray as $dir)
		{
			if ($dir != $type)
			{
				$path_to_file .= strtolower($dir) . '/';
			}
		}

		$app           = JFactory::getApplication();
		$template_path = JPATH_THEMES . '/' . $app->getTemplate() . '/html/';
		$template_path .= 'com_' . strtolower($component) . '/' . $config['view'];
		$this->paths['template'][] = $template_path;

		$componentPart = '/components/' . 'com_' . strtolower($component);
		$admin_path = JPATH_ADMINISTRATOR . $componentPart . $path_to_file . 'tmpl';
		$admin_layout_path = JPATH_ADMINISTRATOR . $componentPart . '/layout';
		$this->adminPaths = array($admin_path, $admin_layout_path);

		$site_path = JPATH_SITE . $componentPart . $path_to_file . 'tmpl';
		$site_layout_path  = JPATH_SITE . $componentPart . '/layout';
		$this->sitePaths = array($site_path, $admin_layout_path);

		if (!JFactory::getApplication()->isClient('site'))
		{
			// Check admin then site
			$this->paths['template'][] = $admin_path;
			$this->paths['template'][] = $admin_layout_path;
			$this->paths['template'][] = $site_path;
			$this->paths['template'][] = $site_layout_path;
		}
		else
		{
			// Check site
			$this->paths['template'][] = $site_path;
			$this->paths['template'][] = $site_layout_path;

			if($this->useAdminLayouts)
            {
                $this->paths['template'][] = $admin_path;
                $this->paths['template'][] = $admin_layout_path;
            }
		}

		// Add paths from the config

		if (array_key_exists('templates', $config))
		{
			foreach ((array) $config['templates'] AS $tmpl_path)
			{
				$this->paths['template'][] = $tmpl_path;
			}
		}
	}

	/**
	 * Method to set the template paths.
	 * @param   string  $path    path to add
	 * @param   bool    $before  should it be at the beginning or the end of the templates?
	 *
	 * @return $this
	 */
	public function addTemplatePath($path, $before = false)
	{
		if ($before)
		{
			array_unshift($this->paths['template'], $path);

			return $this;
		}

		array_push($this->paths['template'], $path);

		return $this;
	}

	/**
	 * Method to add the admin template paths to search for layouts
	 *
	 * @param   bool    $before  should it be at the beginning or the end of the templates?
	 *
	 * @return void
	 */
	protected function addAdminPaths($before = false)
	{
		if (!JFactory::getApplication()->isClient('site'))
		{
			return;
		}

		foreach ($this->adminPaths AS $path)
		{
			$this->addTemplatePath($path, $before);
		}
	}

	/**
	 * Method to add the site template paths to search for layouts
	 *
	 * @param   bool    $before  should it be at the beginning or the end of the templates?
	 *
	 * @return void
	 */
	protected function addSitePaths($before = false)
	{
		foreach ($this->sitePaths AS $path)
		{
			$this->addTemplatePath($path, $before);
		}
	}

	/**
	 * Method to get an access object
	 *
	 * @return AccessInterface
	 */
	public function getAccessObject()
	{
		return new UserAccess;
	}

    /**
     * Method to prepare to the view before executing the render method.
     * This method runs after the model has been assigned, but before the records have been pulled
     * from it.
     *
     * This is intended to be overridden when you want to adjust the model state before pulling the list/item.
     *
     * @return void
     */
	public function prepareView()
    {
    }

	/**
	 * Method to render a template script and return the output.
	 *
	 * @param   string  $tpl  The name of the template file to parse. Automatically searches through the template paths.
	 *
	 * @throws ErrorException
	 * @throws Exception
	 *
	 * @return mixed $output A string
	 */
	public function render($tpl = null)
	{
		$template = JFactory::getApplication()->getTemplate();
		$this->loadTplLanguageFiles($template);

		$this->prepareToRender();

		$output = $this->loadTemplate($tpl);

		return $output;
	}

	/**
	 * Method to do any last minute preparation before rendering the view
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	protected function prepareToRender()
	{
		$app = \JFactory::getApplication();

		if ($app->isClient('site') || $this->isHmvc() || $this->config['isAjax'])
		{
			return;
		}

		JToolbarHelper::title(JText::_(strtoupper($this->config['option'] . '_header_' . $this->config['view'] . '_' . $this->config['layout'])));

		return;
	}

	/**
	 * Method to render a layout template file
	 *
	 * @param   string  $tpl     name of the template
	 * @param   string  $layout  layout name
	 *
	 * @throws ErrorException
	 * @return string
	 */
	public function loadTemplate($tpl = null, $layout = null)
	{
		if (is_null($layout))
		{
			$layout = $this->layout;
		}

		if (isset($tpl))
		{
			$fileName = $layout . '_' . $tpl;

			$defaultName = $tpl;

			$tpl = $this->clean($tpl);
		}
		else
		{
			$fileName    = $layout;
			$defaultName = 'default';
		}

		$fileName = $this->clean($fileName);
		$defaultName = $this->clean($defaultName);

		if(substr($fileName, -1) === '_')
        {
            $fileName = substr($fileName, 0, -1);
        }

        if(substr($defaultName, -1) === '_')
        {
            $defaultName = substr($defaultName, 0, -1);
        }

		if (!isset($this->templateLocations[$fileName]))
		{
			$file        = $fileName . '.php';
			$defaultFile = $defaultName . '.php';

			jimport('joomla.filesystem.path');
			$templateLocation = JPath::find($this->paths['template'], $file);

			// If we couldn't find the layout_file, look for default_file
			if ($templateLocation == false && $defaultFile != 'default.php')
			{
				$templateLocation = JPath::find($this->paths['template'], $defaultFile);
			}

			if ($templateLocation == false)
			{
				throw new ErrorException(JText::sprintf('LOGICAL_VIEW_ERROR_LAYOUT_FILE_NOT_FOUND', $fileName), 500);
			}

			$this->templateLocations[$fileName] = $templateLocation;
		}

		// Unset so as not to introduce into template scope
		unset($tpl);
		unset($file);

		// Never allow a 'this' property
		if (isset($this->this))
		{
			unset($this->this);
		}

		// Start capturing output into a buffer
		ob_start();

		// Include the requested template filename in the local scope
		// (this will execute the view logic).
		include $this->templateLocations[$fileName];

		// Done with the requested template; get the buffer and
		// clear it.
		$output = ob_get_contents();

		ob_end_clean();

		return $output;
	}

	/**
	 * Method to clean illegal characters from path variables
	 *
	 * @param   mixed   $subject      string to clean
	 * @param   string  $pattern      regular expression used to clean a string default is '/[^A-Z0-9_\.-]/i'
	 * @param   mixed   $replacement  default is ''
	 *
	 * @return mixed
	 */
	protected function clean($subject, $pattern = '/[^A-Z0-9_\.-]/i', $replacement = '')
	{
		$subject = preg_replace($pattern, $replacement, $subject);

		return $subject;
	}

	/**
	 * Method to load the language files for the template
	 *
	 * @param   string  $template  name
	 *
	 * @return void
	 */
	protected function loadTplLanguageFiles($template)
	{
		// Load the language file for the template
		$lang = JFactory::getLanguage();

		if (!$lang->load('tpl_' . $template, JPATH_BASE, null, false, true))
		{
			$lang->load('tpl_' . $template, JPATH_THEMES . "/$template", null, false, true);
		}
	}

	/**
	 * Method to escape variables
	 *
	 * @param   mixed  $var               to escape
	 * @param   bool   $htmlSpecialChars  true for htmlspecialchars or false for htmlentities
	 * @param   bool   $doubleEncode      When doubleEncode is false PHP will not encode existing html entities, the default is to convert everything.
	 *
	 * @return string
	 */
	public function escape($var, $htmlSpecialChars = true, $doubleEncode = true)
	{
		if ($htmlSpecialChars)
		{
			$escaped = htmlspecialchars($var, ENT_COMPAT, 'UTF-8', $doubleEncode);
		}
		else
		{
			$escaped = htmlentities($var, ENT_COMPAT, 'UTF-8', $doubleEncode);
		}

		return $escaped;
	}

	/**
	 * Method to check if the current user has view level access to this view
	 * This method is intended to be overridden by subclass and should throw an exception
	 *
	 * @return bool
	 */
	public function canView()
	{
		return true;
	}

	/**
	 * Method to get the component params
	 *
	 * @param   JRegistry  $itemParams  item level params
	 *
	 * @throws Exception
	 *
	 * @return JRegistry
	 */
	protected function getParams($itemParams = null)
	{
		if (!isset($this->params))
		{
			$this->params = new Registry;
		}

		/** @var JApplicationSite $app */
		$app = JFactory::getApplication();

		if ($app->isClient('site'))
		{
			/** @var JRegistry $appParams */
			$appParams = $app->getParams();
			$this->params->merge($appParams);
		}

		if (!is_null($itemParams) && ($itemParams instanceof JRegistry))
		{
			$this->params->merge($itemParams);
		}

		return $this->params;
	}

	/**
	 * Method to get the form token
	 *
	 * @return string form token input
	 */
	public function getFormToken()
	{
		return JHtml::_('form.token');
	}

    /**
     * Method to render an HMVC view
     *
     * @param string $option name of the component
     * @param string $view name of the view to render
     * @param string $layout name of the layout to render
     * @param string $resource name of the resource model to load
     * @param array $config Configuration
     * @param array $input variables for the input
     *
     * @return string
     */
	public function renderHMVC($option, $view, $layout= 'default', $resource = null, $config = array(), $input = array())
	{
		$config['isHmvc'] = true;
		$prefix = substr($this->config['option'], 4);
		$dispatcherName = ucfirst($prefix) . 'ControllerDispatcher';

		if (empty($resource))
		{
			$resource = $view;
		}

		$standardInput =  array(
            'option' => $option,
            'view' => $view,
            'layout' => $layout,
            'task' => 'display',
            'resource' => $resource,
            'ignore_request' => 1
        );

		if(!empty($input))
        {
          $standardInput += $input;
        }

		$input = new JInput($standardInput);

		/** @var BaseController $dispatcher */
		$dispatcher = new $dispatcherName($input, null, new Registry($config));

		// Start capturing output into a buffer
		ob_start();

		$dispatcher->execute();

		// Get the buffer and clear it.
		$output = ob_get_contents();

		ob_end_clean();

		return $output;
	}

	/**
	 * Method to check if this is an HMVC request
	 *
	 * @return bool
	 *
	 * @throws Exception
	 */
	public function isHmvc()
	{
		return (!empty($this->config['isHmvc']));
	}

	/**
	 * Method to get a widget render
	 *
	 * @return WidgetRendererInterface
	 */
	protected function getWidgetRenderer()
	{
		if (is_null($this->widgetRenderer))
		{
			$this->widgetRenderer = new WidgetRenderer(null, true, $this->getAccessObject());
		}

		return $this->widgetRenderer;
	}

	protected function getUri($baseOnly = false)
	{
		return \JUri::base($baseOnly);
	}

	protected function isSite()
	{
		return JFactory::getApplication()->isClient('site');
	}
}
