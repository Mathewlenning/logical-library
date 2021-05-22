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
 * Class Edit
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class EditController extends BaseController
{
	/**
	 * Method to set an item id to edit.id
	 *
	 * @return mixed
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		/** @var RecordModel $model */
		$model = $this->getModel();
		$context = $model->getContext();

		$ids = $this->getIds();
		$editId = $this->getUserState($context . '.edit.id', $ids[0]);

		if (!$model->allowAction('core.edit', null, $editId) && $editId != 0)
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_EDIT_RECORD_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$model->checkout($editId);
		$item = $model->getItem($editId);
		$formControl = $model->getFormControl();
		$this->setUserState($context . '.' . $formControl . '.data', json_encode($item));

		$config = $this->config;
		$url = $model->getTaskRedirect('edit');

		if ($editId != 0)
		{
			$model->checkout($editId);
			$url .= '&id=' . $editId;
		}

		if($return = $this->getInput()->getBase64('return', false))
		{
			$url .= '&return=' . $return;
		}

		$this->setReturn($url);

		return $this->executeController();
	}
}
