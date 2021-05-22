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
 * Class OrderController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class OrderController extends BaseController
{
	/**
	 * Controller Method to add a record to the database
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

		if (!$model->allowAction('core.edit.state'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_EDIT_STATE_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$formControl = $model->getFormControl();
		$data = $this->getInput()->get($formControl, array(), 'array');
		// Make sure the item ids are integers
		$cid = $this->getIds();

		$model->saveOrder($cid, $data);

		$this->completeTask('order', $model);

		return $this->executeController();
	}
}
