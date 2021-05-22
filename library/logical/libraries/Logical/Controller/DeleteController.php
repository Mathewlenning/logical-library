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
 * Class Delete
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class DeleteController extends BaseController
{
	/**
	 * Control method to delete one or more records
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

		If (!$model->allowAction('core.delete'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_DELETE_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$context = $model->getContext();
		$editId = $this->getUserState($context . '.edit.id', 0);

		if ($editId != 0)
		{
			$model->checkin($editId);
		}

		$formControl = $model->getFormControl();
		$this->setUserState($context . '.edit.id', null);
		$this->setUserState($context . '.'.$formControl.'.data', null);

		$ids = $this->getIds();

		// Then we might be dealing with a compound pk
		if (empty($ids[0]))
		{
			$data = $this->getInput()->get($formControl, array(), 'array');

			if(empty($data))
			{
				$msg = JText::_('LOGICAL_CONTROLLER_ERROR_NO_ITEM_SELECTED');
				throw new ErrorException($msg);
			}

			$model->deleteCompoundPk($data);
			$this->completeTask('delete', $model);

			return $this->executeController();
		}

		foreach ($ids AS $id)
		{
			$model->delete($id);
		}

		$this->completeTask('delete', $model);

		return $this->executeController();
	}
}
