<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Model\Observer;


// No direct access
defined('_JEXEC') or die;

use Joomla\Event\Event;
use Logical\Observer\ModelObserver,
	Logical\Model\BaseModel,
	Logical\Observable\ObservableInterface,
	Logical\Model\RecordModel;

use JForm,
	JDatabaseQuery;

/**
 * Joomla content event API
 *
 * Attach this to models that need to integrate with content plugins.
 *
 * @package Logical\Model\Observer
 */
class JoomlaApi extends ModelObserver
{
	protected $deleteData = null;

	/**
	 * Method to check if this observer can be attached to this observable model
	 *
	 * @param   ObservableInterface  $model  the observable
	 *
	 * @return mixed
	 */
	protected function canAttach($model)
	{
		return ($model instanceof BaseModel);
	}

	/**
	 * Event triggered after getItem method is executed
	 *
	 * @param   RecordModel     $model  executing the method
	 * @param   JDatabaseQuery  $query  object
	 * @param   object          $item   to be returned
	 *
	 * @return mixed
	 */
	public function onAfterGetItem(RecordModel $model, $query, $item)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentPrepareData',array($context, $item));
		$dispatcher->dispatch('onContentPrepareData', $event);

		return;
	}

	/**
	 * Event triggered after getForm method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   JForm        $form   form being requested
	 *
	 * @return mixed
	 */
	public function onAfterGetForm(RecordModel $model, JForm $form)
	{
		$data = $model->getItem();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentPrepareForm', array($form, $data));
		$dispatcher->dispatch('onContentPrepareForm', $event);

		return;
	}

	/**
	 * Event triggered before create is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onBeforeCreate(RecordModel $model, $data)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentBeforeSave', array($context, $data, true));
		$dispatcher->dispatch('onContentBeforeSave', $event);

		return;
	}

	/**
	 * Event Triggered after create method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onAfterCreate(RecordModel $model, $data)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentAfterSave', array($context, $data, true));
		$dispatcher->dispatch('onContentAfterSave', $event);

		return;
	}

	/**
	 * Event Triggered before update method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onBeforeUpdate(RecordModel $model, $data)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentBeforeSave', array($context, $data, false));
		$dispatcher->dispatch('onContentBeforeSave', $event);

		return;
	}

	/**
	 * Event Triggered after update method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onAfterUpdate(RecordModel $model, $data)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentAfterSave', array($context, $data, false));
		$dispatcher->dispatch('onContentAfterSave', $event);

		return;
	}

	/**
	 * Event triggered before delete method is executed
	 *
	 * @param   RecordModel $model  Observable
	 * @param   int         $id     primary key
	 * @param   object      $record record data to be deleted
	 *
	 * @return mixed
	 * @throws \ErrorException
	 */
	public function onBeforeDelete(RecordModel $model, $id, $record)
	{
		$context = $model->getContext();

		$this->deleteData = $model->getItem($id);

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentBeforeDelete', array($context, $this->deleteData));
		$dispatcher->dispatch('onContentBeforeDelete', $event);

		return;
	}

	/**
	 * Event triggered after delete method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   int          $id     primary key
	 * @param   object      $record  record data to be deleted
	 *
	 * @return mixed
	 */
	public function onAfterDelete(RecordModel $model, $id, $record)
	{
		$context = $model->getContext();

		$dispatcher = $model->getDispatcher();
		$event = new Event('onContentAfterDelete', array($context, $this->deleteData));
		$dispatcher->dispatch('onContentAfterDelete', $event);

		return;
	}
}
