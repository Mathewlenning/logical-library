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
use \JDatabaseQuery;
use \JForm;

/**
 * Interface ModelObserverInterface
 *
 * @package  Logical\Observer
 *
 * @since    0.0.1
 */
interface ModelObserverInterface extends ObserverInterface
{
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
	public function onBeforeGetList(CollectionModel $model, $query, $key = null, $class = null);

	/**
	 * Event triggered after getList method is executed
	 *
	 * @param   CollectionModel  $model  executing the method
	 * @param   JDatabaseQuery   $query  object
	 * @param   array            $list   to be returned
	 *
	 * @return mixed
	 */
	public function onAfterGetList(CollectionModel $model, $query, $list);

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
	public function onBeforeGetItem(RecordModel $model, $query, $pk, $class);

	/**
	 * Event triggered after getItem method is executed
	 *
	 * @param   RecordModel     $model  executing the method
	 * @param   JDatabaseQuery  $query  object
	 * @param   object          $item   to be returned
	 *
	 * @return mixed
	 */
	public function onAfterGetItem(RecordModel $model, $query, $item);

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
	public function onBeforeGetForm(RecordModel $model, $name, $source, $config = array());

	/**
	 * Event triggered after getForm method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   JForm        $form   form being requested
	 *
	 * @return mixed
	 */
	public function onAfterGetForm(RecordModel $model, JForm $form);

	/**
	 * Event triggered before create is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array               $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onBeforeCreate(RecordModel $model, $data);

	/**
	 * Event Triggered after create method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onAfterCreate(RecordModel $model, $data);

	/**
	 * Event Triggered before update method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onBeforeUpdate(RecordModel $model, $data);

	/**
	 * Event Triggered after update method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   array        $data   Data from the request
	 *
	 * @return mixed
	 */
	public function onAfterUpdate(RecordModel $model, $data);

	/**
	 * Event triggered before delete method is executed
	 *
	 * @param   RecordModel $model Observable
	 * @param   int         $id      primary key
	 * @param   object      $record  record data to be deleted
	 *
	 * @return mixed
	 */
	public function onBeforeDelete(RecordModel $model, $id, $record);

	/**
	 * Event triggered after delete method is executed
	 *
	 * @param   RecordModel  $model  Observable
	 * @param   int          $id     primary key
	 * @param   object      $record  record data to be deleted
	 *
	 * @return mixed
	 */
	public function onAfterDelete(RecordModel $model, $id, $record);
}
