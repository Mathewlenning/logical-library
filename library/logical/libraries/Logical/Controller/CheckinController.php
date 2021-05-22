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
 * Class CheckinController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class CheckinController extends BaseController
{
	/**
	 * Controller method to check-in a locked record
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

		if (!$model->allowAction('core.manage'))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_CHECKIN_NOT_ALLOWED');
			throw new ErrorException($msg);
		}

		$ids = $this->getIds();

		foreach ($ids AS $id)
		{
			$model->checkin($id);
		}

		$this->completeTask('checkin', $model);

		return $this->executeController();
	}
}
