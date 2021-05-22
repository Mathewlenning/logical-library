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

use Exception;
use Logical\Widget\WidgetRenderer;
use Logical\Registry\Registry;
use \JDocument;

/**
 * Class AjaxController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class AjaxController extends BaseController
{
	/**
	 * Ajax controller
	 *
	 * @throws \ErrorException
	 *
	 * @return void
	 */
	public function execute()
	{
		if (!$this->isAjaxRequest())
		{
			throw new \ErrorException(\JText::_('LOGICAL_CONTROLLER_ERROR_INVALID_REQUEST_HEADER'), 400);
		}

		$response = new Registry;

		try
		{
			ob_start();
			$this->executeController();

			$content = ob_get_contents();
			ob_end_clean();

			$response->set('body', $content);
		}
		catch (Exception $e)
		{
			$this->addMessage($e->getMessage(), 'error');
			http_response_code(500);
		}

		$format = $this->config->get('viewType', 'json');
		$messages = $this->getResponseMessages($format);

		$response->set('messages', $messages);

		/** @var \JDocument $document */
		$document = \JFactory::getDocument();
		$script = '';

		if(method_exists($document, 'getHeadData'))
		{
			$documentHeader = $document->getHeadData();

			if(!empty($documentHeader['script']['text/javascript']))
			{
				$script = $documentHeader['script']['text/javascript'];
			}
		}

		$response->set('scripts', $script);

		$response->set('redirect', $this->getRedirects());

		echo $response->toString();

		$this->getApplication()->close();
	}

	/**
	 * Method to get the applicaiton message que
	 *
	 * @param   string  $format  the format of the request
	 *
	 * @return array|\Logical\Widget\WidgetElement if the format is HTML then the messages are returned as a widget.
	 *
	 * @throws \ErrorException
	 */
	private function getResponseMessages($format = 'json')
	{
		/** @var \JApplicationCms $app */
		$app = $this->getApplication();

		$messages = array(
			'message' => array('widgetId' => 'system.message.message', 'messages' => array()),
			'warning' => array('widgetId' => 'system.message.warning', 'messages' => array()),
			'error' => array('widgetId' => 'system.message.error', 'messages' => array()));

		if (empty($app->deferredMsg))
		{
			return $messages;
		}

		$msgQueue = $app->deferredMsg;
		foreach ($msgQueue AS $message)
		{
			$messages[$message['type']]['messages'][] = $message['message'];
		}

		/** @todo fix error message rendering
		if ($format == 'html')
		{
			$model = $this->getModel();
			$widgetRender = new WidgetRenderer(null, true, $model);

			$messages = $widgetRender->render('system.message.container', array('message_types' => $messages));
		}
		 */

		return $messages;
	}

	private function getRedirects()
	{
		/** @var \JApplicationCms $app */
		$app = $this->getApplication();

		if(empty($app->defRedirect))
		{
			return null;
		}

		$uriInstance = \JUri::getInstance();

		return $uriInstance->getScheme().'://'.$uriInstance->getHost() . $app->defRedirect;
	}
}
