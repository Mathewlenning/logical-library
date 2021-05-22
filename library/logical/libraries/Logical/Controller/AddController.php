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
 * Class AddController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class AddController extends BaseController
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
		/** @var RecordModel $model */
		$model = $this->getModel();
		$context = $model->getContext();
		$formControl = $model->getFormControl();

		$ids = $this->getIds();
		$editId = $this->getUserState($context . '.edit.id', $ids[0]);

		if (!empty($editId))
		{
			$model->checkin($editId);
		}

		$this->setUserState($model->getContext() . '.edit.id', null);
		$this->setUserState($context . '.' . $formControl . '.data', null);

		$this->completeTask('add', $model);

		return $this->executeController();
	}
}
