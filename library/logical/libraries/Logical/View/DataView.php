<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\View;

// No direct access
defined('_JEXEC') or die;

use Logical\Model\BaseModel;
use Logical\Access\AccessInterface;
use Logical\Model\DataModel;

/**
 * Class DataView
 *
 * @package  Logical\View
 * @since    0.0.1
 */
abstract class DataView extends BaseView
{
	/**
	 * key of the default model in the models array
	 *
	 * @var string
	 */
	protected $defaultModel;

	/**
	 * Associative array of model objects $models[$name]
	 *
	 * @var array
	 */
	protected $models = array();

	/**
	 * @var array of database fields type definitions
	 */
	protected $fieldsData = array();

	/**
	 * Method to get the model object
	 *
	 * @param   string  $name  The name of the model (optional)
	 *
	 * @return  BaseModel
	 */
	public function getModel($name = null)
	{
		if ($name === null)
		{
			$name = $this->defaultModel;
		}

		return $this->models[$name];
	}

	/**
	 * Method to add a model to the view.  We support a multiple model single
	 * view system by which models are referenced by class name.
	 *
	 * @param   string     $name     Name to store the model under
	 * @param   BaseModel  $model    The model to add to the view.
	 * @param   boolean    $default  Is this the default model?
	 *
	 * @return  BaseModel   The input parameters $model.
	 */
	public function setModel($name, $model, $default = false)
	{
		$this->models[$name] = $model;

		if ($default)
		{
			$this->defaultModel = $name;
		}

		return $model;
	}

	/**
	 * Method to get an access object
	 *
	 * @return AccessInterface
	 */
	public function getAccessObject()
	{
		return $this->getModel();
	}

	/**
	 * Method to load the table field data from the model
	 *
	 * @return array
	 */
	protected function loadFieldData()
	{
		/** @var DataModel $model */
		$model = $this->getModel();

		if ($model instanceof DataModel)
		{
			$this->fieldsData = $model->getFields(array(), true);
		}

		return $this->fieldsData;
	}
}
