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

use Logical\Model\RecordModel;
use ErrorException;
use JFactory;
use JForm;

/**
 * Class RecordView
 *
 * @package  Logical\View
 * @since    0.0.1
 */
abstract class RecordView extends CollectionView
{
	/**
	 * Layouts that should autoload the item
	 *
	 * @var array
	 */
	protected $itemLayouts = array('item', 'details', 'form');

	/**
	 * Toggle get item behavior
	 *
	 * @var bool
	 * @deprecated use addItemLayout instead
	 */
	protected $getItem = false;

	/**
	 * Layouts taht should load the form
	 *
	 * @var array
	 */
	protected $formLayouts = array('form');

	/**
	 * Toggle get form behavior
	 *
	 * @var bool
	 * @deprecated use addFormLayout instead
	 */
	protected $getForm = false;

	/**
	 * The item data
	 * @var object
	 */
	public $item;

	/**
	 * JForm Object
	 *
	 * @var JForm
	 */
	protected $form;

	/**
	 * Constructor
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
	{
		$layout = $config['layout'];

		if (in_array($layout, $this->itemLayouts))
		{
			$this->getItem = true;
		}

		if (in_array($layout, $this->formLayouts))
		{
			$this->getForm = true;
			$this->getItem = true;
		}

		parent::__construct($config);
	}

	/**
	 * Method to check if the current user has view level access to this view
	 * This method is intended to be overridden by subclass
	 *
	 * @throws ErrorException
	 *
	 * @return bool
	 */
	public function canView()
	{
		if($this->layout != 'form')
		{
			return parent::canView();
		}

		// Check if this is HMVC call
		if ($this->isHmvc())
		{
			return parent::canView();
		}

		/** @var RecordModel $model */
		$model = $this->getModel();
		$context = $model->getContext();
		$pk = $model->getState($context.'.id');
		$action = empty($pk) ? 'core.create': 'core.edit';

		if (!$model->allowAction($action, null, $pk))
		{
			throw new ErrorException($model->getAccessDeniedMessage($action));
		}

		return parent::canView();
	}

	/**
	 * Method calls RecordModel::getItem and RecordModel::getForm if the class properties by the same name are true
	 *
	 * @param   string $tpl The name of the template file to parse. Automatically searches through the template paths.
	 *
	 * @return mixed $output A string
	 *
	 * @throws ErrorException
	 */
	public function render($tpl = null)
	{
		/** @var RecordModel $model */
		$model  = $this->getModel();

		if (in_array($this->layout, $this->itemLayouts) && empty($this->item))
		{
			$this->item = $model->getItem();
		}

		if (in_array($this->layout, $this->formLayouts) && empty($this->form))
		{
			$this->form = $model->getForm();
		}

		return parent::render($tpl);
	}

    /**
     * Method to prepare the form
     *
     * @return void
     *
     * @throws \Exception
     */
    protected function prepareToRender()
    {
        if (empty($this->form))
        {
            parent::prepareToRender();

            return;
        }

        /** @var RecordModel $model */
        $model  = $this->getModel();

        $this->bindSessionData($model->getContext(), $model->getFormControl());

        parent::prepareToRender();
    }

    /**
     * Method to bind session data to the current JForm object
     *
     * @param string $context       Model context
     * @param string $formControl   JForm control
     *
     * @return void
     */
    protected function bindSessionData($context, $formControl)
    {
        $session = JFactory::getSession();
        $registry = $session->get('registry');
        $data = $registry->get($context . '.' . $formControl .'.data');


        //@todo figure out why clicking the cancel button then loading a different record through edit is clearing the session data.
        if(!empty($this->item))
        {
            $this->form->bind($this->item->toJForm());
        }
    }

	/**
	 * Method to add a layout that uses item data
	 *
	 * @param   string $layout  Layout to add
	 * @param   bool   $useForm Should we also load the form?
	 *
	 * @return $this
	 */
	protected function addItemLayout($layout, $useForm = false)
	{
		if ($useForm)
		{
			$this->addFormLayout($layout);
		}

		if (!in_array($layout, $this->itemLayouts))
		{
			$this->itemLayouts[] = $layout;
		}

		return $this;
	}

	/**
	 * Method to add a layout that uses form data
	 *
	 * @param   string  $layout   Layout to add
	 *
	 * @return $this
	 */
	protected function addFormLayout($layout)
	{
		if (!in_array($layout, $this->formLayouts))
		{
			$this->formLayouts[] = $layout;
		}

		return $this;
	}
}
