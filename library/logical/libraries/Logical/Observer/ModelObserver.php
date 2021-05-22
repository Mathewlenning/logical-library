<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Observer;

use Logical\Model\RecordModel;
use Logical\Model\CollectionModel;
use Logical\Observable\ObservableInterface;

use JForm;
use JDatabaseQuery;

/**
 * Class ModelObserver
 *
 * @package  Logical\Observer
 *
 * @since    0.0.1
 */
abstract class ModelObserver implements ModelObserverInterface
{
	/**
	 * @var array
	 */
	protected $config = array();

	/**
	 * Constructor checks if this observer can be attached to this model
	 *
	 * @param   ObservableInterface  $model   Model to attach the observer to
	 * @param   array                $config  Configuration
	 */
	public function __construct(ObservableInterface $model, $config = array())
	{
		if ($this->canAttach($model))
		{
			$config['model_class'] = get_class($model);
			$this->config = $config;
			$model->attachObserver($this);
		}
	}

	/**
	 * Method to create an observer instance
	 *
	 * @param   ObservableInterface  $observableObject  Model to attach the observer to
	 * @param   array                $config            Configuration
	 *
	 * @return static
	 */
	public static function createObserver(ObservableInterface $observableObject, $config = array())
	{
		$observer = new static($observableObject, $config);

		return $observer;
	}

	/**
	 * Method to check if this observer can be attached to this observable model
	 *
	 * @param   ObservableInterface  $model  the observable
	 *
	 * @return mixed
	 */
	abstract protected function canAttach($model);

	/**
	 * Event triggered before getList method is executed
	 *
	 * @param   CollectionModel  $model  executing the method
	 * @param   JDatabaseQuery   $query  object
	 * @param   string           $key    the name of a field on which to key the result array.
	 * @param   string           $class  the class name of the return item
	 *
	 * @return mixed
	 */
	public function onBeforeGetList(CollectionModel $model, $query, $key = null, $class = null)
	{
		return;
	}

	/**
	 * Event triggered after getList method is executed
	 *
	 * @param   CollectionModel  $model  executing the method
	 * @param   JDatabaseQuery   $query  object
	 * @param   array            $list   to be returned
	 *
	 * @return mixed
	 */
	public function onAfterGetList(CollectionModel $model, $query, $list)
	{
		return;
	}

	/**
	 * Event triggered before getItem method is executed
	 *
	 * @param   RecordModel     $model  executing the method
	 * @param   JDatabaseQuery  $query  object
	 * @param   integer         $pk     The id of the primary key.
	 * @param   string          $class  the class name of the return item
	 *
	 * @return mixed
	 */
	public function onBeforeGetItem(RecordModel $model, $query, $pk, $class)
	{
		return;
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
		return;
	}

	/**
	 * Event triggered before getForm method is executed
	 *
	 * @param   RecordModel  $model   Observable
	 * @param   string       $name    The name of the form.
	 * @param   string       $source  The form source. Can be XML string if file flag is set to false.
	 * @param   array        $config  Configuration
	 *
	 * @return mixed
	 */
	public function onBeforeGetForm(RecordModel $model, $name, $source, $config = array())
	{
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
		return;
	}

	/**
	 * Event triggered before delete method is executed
	 *
	 * @param   RecordModel $model Observable
	 * @param   int         $id      primary key
	 * @param   object      $record  record data to be deleted
	 *
	 * @return mixed
	 */
	public function onBeforeDelete(RecordModel $model, $id, $record)
	{
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
		return;
	}
}
