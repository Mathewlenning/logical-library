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

use Logical\View\DataView;
use Logical\Registry\Registry;

use ErrorException;
use Joomla\Application\AbstractApplication;
use JFactory;
use JInput;
use JText;
use JUri;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Class Display
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class DisplayController extends BaseController
{
	/**
	 * View to display
	 *
	 * @var mixed view object
	 *
	 * @since 0.0.1
	 */
	protected $view;

	/**
	 * Output buffer content from the views.
	 * @var
	 */
	public $output;

	/**
	 * Should we echo the output or not?
	 * @var bool
	 */
	public $echoOutput = true;

	/**
	 * Display $this->view.
	 *
	 * If this->view is not set, load it and set the default model.
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		if (empty($this->view))
		{
			$config = $this->config->toArray();
			$this->view = $this->getView($config['view']);

			if (($this->view instanceof DataView))
			{
				$this->attachModel($config['resource']);
			}
		}

		// Check view level
		$this->view->canView();

		// Prepare the view
        $this->view->prepareView();

		$output = $this->view->render();

		$this->output = $output;

		if ($this->echoOutput)
		{
            if(is_object($output) && method_exists($output, 'output'))
            {
                $output->output();
                $this->getApplication()->close();
            }

			echo $this->output;

		}

		return true;
	}

	/**
	 * Method to get a view, initiating it if it does not already exist.
	 * This method assumes auto-loading
	 * format is $prefix.'View'.$name.$type
	 * $type is used for the file name which is a deviation from the traditional
	 * Joomla naming convention.
	 *
	 * @param   string  $name    name of the view folder exp. articles
	 * @param   string  $prefix  option prefix exp. com_content
	 * @param   string  $type    name of the file exp. html = html.php
	 * @param   array   $config  settings
	 *
	 * @return mixed
	 *
	 * @throws ErrorException
	 */
	protected function getView($name, $prefix = null, $type = null, $config = array())
	{
		$config = $this->normalizeConfig($config);

		if (is_null($prefix))
		{
			$prefix = $config['prefix'];
		}

		if (is_null($type))
		{
			$type = $config['viewType'];
		}

		$class = ucfirst($prefix) . 'View' . ucfirst($name) . ucfirst($type);

		if ($this->view instanceof $class)
		{
			return $this->view;
		}

		if (!class_exists($class))
		{
			$path = 'com_' . strtolower($prefix) . '/view';
			$path .= '/' . strtolower($name) . '/';

			if (!JFactory::getUser()->authorise('core.manage'))
			{
				$class = strtolower($name) . '.' . strtolower($type);
				$path = JUri::base();
			}

			throw new ErrorException(JText::sprintf('JLIB_APPLICATION_ERROR_VIEW_CLASS_NOT_FOUND', $class, $path), 404);
		}

		$this->view = new $class($config);

		return $this->view;
	}

	/**
	 * Method to attach a model to the view
	 *
	 * @param   string  $resource  name of the resource model to attach
	 *
	 * @throws ErrorException
	 *
	 * @return void
	 */
	protected function attachModel($resource)
	{
		$resource = explode('.', $resource);
		$default = true;

		foreach ((array) $resource AS $modelName)
		{
			$model   = $this->getModel($modelName);
			$context = $model->getContext();

			// Only the first model can be the default
			if (!$default)
			{
				// Push the model into the view (as default)
				$this->view->setModel($modelName, $model, false);
				continue;
			}

			$ids = $this->getIds($this->getUserState($context . '.edit.id', 0));

			if ($ids[0] !== 0 && is_callable(array($model, 'setState')))
			{
				$model->setState($context . '.id', $ids[0]);
			}

			// Push the model into the view (as default)
			$this->view->setModel($modelName, $model, true);

			$default = false;
		}
	}
}
