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
class UpdateController extends BaseController
{
	/**
	 * Method to update a database record
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
		$data = $this->getInput()->get('jform', array(), 'array');
		$this->setUserState($context . '.jform.data', json_encode($data));
		$ids = $this->getIds();
		$keyName = $model->getKeyName();

		//@todo Figure out how to deal with compound keys.

		if (empty($ids[0]))
		{
		    if (!array_key_exists($keyName, $data))
            {
                $data[$keyName] = 0;
            }

            $ids[0] = $data[$keyName];
		}

		if (!$model->allowAction('core.edit',null, $ids[0]))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_UPDATE_RECORD_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$data['files'] = $this->getInput()->files->get('jform', array(), 'raw');


		foreach ($ids AS $id)
		{
			$data[$keyName] = $id;

			$model->checkout($id);
			$model->update($data, array());
			$model->checkin($id);
		}

		if (count($ids) == 1)
		{
			$this->setUserState($context . '.edit.id', $ids[0]);
		}

		$this->setUserState($context . '.jform.data', null);
		$this->completeTask('update', $model);

		return $this->executeController();
	}
}
