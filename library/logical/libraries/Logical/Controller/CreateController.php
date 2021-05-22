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

use Logical\Model\RecordModel;

use ErrorException;
use JText;

/**
 * Class UpdateController
 *
 * @package  Logical\Controller
 * @since    0.0.11
 */
class CreateController extends BaseController
{
	/**
	 * Method to save a new record to the database
	 *
	 * @throws ErrorException
	 *
	 * @return bool
	 */
	public function execute()
	{
		// Check for request forgeries
		$this->validateSession();

		/** @var RecordModel $model */
		$model = $this->getModel();
		$context = $model->getContext();
		$formControl = $model->getFormControl();

		$data = $this->getInput()->get($formControl, array(), 'array');

		$this->setUserState($context . '.'. $formControl .'.data', json_encode($data));

		if (!$model->allowAction('core.create'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_CREATE_RECORD_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$data['files'] = $this->getInput()->files->get($formControl, array(), 'raw');
		$id = $model->create($data);

		$this->setUserState($model->getContext() . '.edit.id', $id);

		// if the ID is empty we keep the data in the form
		if(!empty($id))
		{
			$this->setUserState($context . '.'. $formControl .'.data', null);
		}

		$this->completeTask('create', $model);

		return $this->executeController();
	}
}
