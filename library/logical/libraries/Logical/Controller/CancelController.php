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

/**
 * Class CancelController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class CancelController extends BaseController
{
	/**
	 * Control method to cancel an edit process and return to the default view
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		/** @var RecordModel $model */
		$model = $this->getModel();
		$context = $model->getContext();
		$editId = $this->getUserState($context . '.edit.id', 0);

		if ($editId != 0)
		{
			$model->checkin($editId);
		}

		$context = $model->getContext();
		$formControl = $model->getFormControl();

		// Clear the form state
		$this->setUserState($context . '.'.$formControl.'.data', null);
		$this->setUserState($context . '.edit.id', null);

		$this->completeTask('cancel', $model);

		return $this->executeController();
	}
}
