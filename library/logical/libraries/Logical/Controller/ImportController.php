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

use ErrorException;
use Exception;
use JDatabaseDriver;
use JText;

/**
 * Class Import
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class ImportController extends BaseController
{
	/**
	 * Controller method to import records into the database
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		// Check for request forgeries
		$this->validateSession();

		/** @var RecordModel $model */
		$model = $this->getModel();

		if (!$model->allowAction('core.import'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_IMPORT_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$dispatcher = $model->getDispatcher(array('extension'));
		$event = new Event('onBeforeImport', array($this));
		$dispatcher->dispatch('onBeforeImport', $event);

		$input = $this->getInput();

		$data = $input->post->get('import', array(), 'array');
		$files = $input->files->get('import', array(), 'array');

		/** @var JDatabaseDriver $dbo */
		$dbo = $model->getDbo();

		$dbo->transactionStart(true);

		try
		{
			$model->import($data, $files['file']);
		}
		catch (Exception $e)
		{
			$dbo->transactionRollback(true);
			$this->addMessage($e->getMessage(), 'error');

			return false;
		}

		$dbo->transactionCommit(true);

		$this->completeTask('import', $model);

		return $this->executeController();
	}
}
