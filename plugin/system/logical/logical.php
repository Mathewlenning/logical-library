<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Class PlgSystemLogical
 *
 * @since  0.0.1
 */
class PlgSystemLogical extends JPlugin
{
	/**
	 * Method to register custom library.
	 *
	 * @return  void
	 */
	public function onAfterInitialise()
	{
		// Register our name space with the autoloader
		JLoader::registerNamespace('Logical', JPATH_LIBRARIES);

		if(file_exists(JPATH_LIBRARIES.'/Logical/Vendor/autoload.php'))
        {
            require_once JPATH_LIBRARIES.'/Logical/Vendor/autoload.php';
        }

		// Load Logical Language
		$lang = JFactory::getLanguage();
		$lang->load('logical', JPATH_SITE);

		if (!class_exists('Logical\Widget\WidgetRenderer'))
		{
			JFactory::getApplication()->enqueueMessage('Logical library not found. Please insure you have the latest version installed.', 'warning');

			return;
		}

		\Logical\Widget\WidgetRenderer::setControls(
			array(
				'foreach' => '\Logical\Widget\Control\ForeachControl',
				'var' => '\Logical\Widget\Control\VarControl',
				'widget' => '\Logical\Widget\Control\WidgetControl',
				'jtext' => '\Logical\Widget\Control\JtextControl',
				'layout' => '\Logical\Widget\Control\LayoutControl',
				'token' => '\Logical\Widget\Control\TokenControl',
				'editor' => '\Logical\Widget\Control\EditorControl',
				'select' => '\Logical\Widget\Control\SelectControl'
			)
		);

		$templateDir = $this->params->get('widget_template_dir', 'media/logical/widget');

		if (strpos($templateDir, '/') == 0)
		{
			$templateDir = substr($templateDir, 1);
		}

		$templatePaths = array();
		$templatePaths[] = JPATH_SITE . '/' . $templateDir;
		$templatePaths[] = JPATH_SITE . '/media/logical/widget';

		\Logical\Widget\WidgetRenderer::setSearchPaths($templatePaths);

		// At the moment this only works in the front-end, because the admin only supports bootstrap-v2
		if (JFactory::getApplication()->isSite())
		{
			\Logical\Widget\WidgetRenderer::setTemplateFile($this->params->get('widget_template', 'bootstrap-v2.xml'));
		}

		$this->checkRestFul();
	}

	/**
	 * Method to check if a task is set and set it to restful if the configuration is set to use restful services
	 *
	 * @return void
	 *
	 * @throws Exception
	 */
	private function checkRestFul()
	{
		$input = JFactory::getApplication()->input;

		$task = $input->get('task');
		$layout = $input->get('layout');
		$id = $input->get('id');

		if (!empty($task) || $this->params->get('use_rest', 'NO') == "NO")
		{
			return;
		}

		switch ($input->get('method', 'GET'))
		{
			case 'PUT':
				$input->set('task', 'create');
				break;
			case 'POST':
				$input->set('task', 'update');
				break;
			case 'DELETE':
				$input->set('task', 'delete');
				break;
			case 'GET':
			default:
				$input->set('task', 'display');

				if (empty($layout) && !empty($id))
				{
					$input->set('layout', 'item');
				}

				break;
		}
	}

	/**
	 * Initialize component specific
	 *
	 * @return  void
	 */
	public function onAfterRoute()
	{
	}

	/**
	 * This event is triggered before the framework creates the Head section of the Document.
	 *
	 * @return  void
	 */
	public function onBeforeCompileHead()
	{
	}

	/**
	 * This event is triggered immediately before pushing the document buffers into the template placeholders,
	 * retrieving data from the document and pushing it into the into the JResponse buffer.
	 * http://docs.joomla.org/Plugin/Events/System
	 *
	 * @return  void
	 */
	public function onBeforeRender()
	{
	}

	/**
	 * This event is triggered after pushing the document buffers into the template placeholders,
	 * retrieving data from the document and pushing it into the into the JResponse buffer.
	 * http://docs.joomla.org/Plugin/Events/System
	 *
	 * @return boolean
	 */
	public function onAfterRender()
	{
	}
}
