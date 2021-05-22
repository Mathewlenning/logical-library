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
use Logical\Model\RecordModel;
use Logical\Registry\Registry;

use ErrorException;
use Joomla\Application\AbstractApplication;
use JDate;
use JInput;
use JText;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Class Export
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class ExportController extends DisplayController
{
	/**
	 * set the output so that it is not echoed out.
	 *
	 * @var bool
	 */
	public $echoOutput = false;

	/**
	 * Data being exported
	 * @var
	 */
	protected $data;

	/**
	 * Constructor gets the export data from the post array and sets config['viewType'] = $data['exportType']
	 *
	 * @param   JInput            $input   The input object.
	 * @param   AbstractApplication  $app     The application object.
	 * @param   Registry          $config  Configuration
	 */
	public function __construct(JInput $input = null, AbstractApplication $app = null, Registry $config = null)
	{
		$this->data = $input->post->get('export', array(), 'array');

		$config->set('viewType', $input->get('format', 'xml', 'word'));
		parent::__construct($input, $app, $config);
	}

	/**
	 * Method to generate a downloadable export
	 *
	 * @return void
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		// Check for request forgeries
		$this->validateSession();

		/** @var RecordModel $model */
		$model = $this->getModel();

		if (!$model->allowAction('core.export'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_EXPORT_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$dispatcher = $model->getDispatcher(array('extension'));
		$event = new Event('onBeforeExport', array($this));
		$dispatcher->dispatch('onBeforeExport', $event);

		if (isset($this->data['useFilters']) && $this->data['userFilters'] == 'YES')
		{
			$model->getState();
		}

		if (parent::execute())
		{
			$config = $this->config;
			$today = new JDate;
			$fileName = $config['option'] . '_' . $config->get('view') . '-' . $today->format('Y-m-d') . '.' . $config->get('viewType');

            if(is_object($this->output) && method_exists($this->output, 'output'))
            {
                $this->output->output();

                $this->getApplication()->close();
            }

			header('Content-Type: text/' . strtolower($config['viewType']));
			header('Content-Disposition: attachement; filename=' . $fileName);

			if ($handle = fopen('php://output', 'w'))
			{
				fwrite($handle, $this->output);
			}

			fclose($handle);
		}

		$this->getApplication()->close();
	}
}
