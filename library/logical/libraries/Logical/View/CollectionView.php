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

use Logical\Html\Pagination;
use Logical\Model\CollectionModel;
use JRoute;

/**
 * Class CollectionView
 *
 * @package  Logical\View
 * @since    0.0.1
 */
abstract class CollectionView extends DataView
{
	/**
	 * Layouts that should autoload items list
	 *
	 * @var array
	 */
	protected $listLayouts = array('default', 'list');

	/**
	 * Should we load the list
	 *
	 * @var bool
	 * @deprecated use addListLayouts instead
	 */
	protected $getList = false;

	/**
	 * Array of items from the model
	 *
	 * @var array
	 */
	protected $items;

	/**
	 * A pagination object
	 *
	 * @var Pagination
	 */
	protected $pagination;

	/**
	 * Constructor
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
	{
		$layout = $config['layout'];

		if (in_array($layout, $this->listLayouts))
		{
			$this->getList = true;
		}

		parent::__construct($config);
	}

	/**
	 * Calls CollectionModel::getList if the getList flag is set to true
	 *
	 * @param   string $tpl template string
	 *
	 * @return mixed
	 *
	 * @throws \ErrorException
	 */
	public function render($tpl = null)
	{
		/** @var CollectionModel $model */
		$model = $this->getModel();

		$getList = in_array($this->layout, $this->listLayouts);

		if ($getList && empty($this->items))
		{
			$this->items = $model->getList();
		}

		if ($getList && empty($this->pagination))
		{
			$this->pagination = $model->getPagination();
			$this->pagination->setWidgetRenderer($this->getWidgetRenderer());
		}

		return parent::render($tpl);
	}

	/**
	 * Method to get a sorting column link
	 *
	 * @param   string  $sortingColumn  the prefixed alias of the column to sort by
	 * @param   string  $linkText       the link text
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	protected function getSortingLink($sortingColumn, $linkText)
	{
		/** @var \Logical\Model\CollectionModel $model */
		$model = $this->getModel();
		$sortDefault = 'a.' . $model->getKeyName();

		/** @var  \Logical\Model\Filter\Sort $sortFilter */
		$sortFilter = $model->getFilters('Logical\Model\Filter\Sort');

		if (!empty($sortFilter))
		{
			$sortDefault = $sortFilter->getDefaultOrderBy();
		}

		if (!is_array($sortDefault))
        {
           $sortDefault = array($sortDefault);
        }

		$listOrdering = $model->getState('list.ordering', $sortDefault);

		$render = $this->getWidgetRenderer();
		$link = new \Logical\Html\SortingLink($render, $this->getSortingUrl($sortingColumn), $sortingColumn, \JText::_($linkText), $listOrdering);


		return $link->render();
	}

	protected function getSortColumnDirection($sortingColumn, $model, $invert = false)
    {
        $listOrdering = $model->getState('list.ordering', array());
        $listDirection = $model->getState('list.direction', array('asc'));
        $direction = 'asc';

        foreach ($listOrdering AS $index => $value)
        {
            if ($value != $sortingColumn)
            {
                continue;
            }

            $direction = (array_key_exists($index, $listDirection)) ? $listDirection[$index] : $direction;

            if ($invert)
            {
                $direction = (strtolower($direction) == 'asc') ? 'desc' : 'asc';
            }
        }

        return $direction;
    }

	/**
	 * Method to return the ordering URL stubs for sorting
	 *
	 * @param   string  $sortField  Prefixed name of the field to sort by I.E. "a.title"
	 *
	 * @return string
	 */
	protected function getSortingUrl($sortField)
	{
		/** @var CollectionModel $model */
		$model = $this->getModel();
		$direction = $this->getSortColumnDirection($sortField, $model, true);

        $url = $this->formUrl . '&filter_order=' . $sortField . '&filter_order_Dir=' . $direction;

        return JRoute::_($url);
	}

	/**
	 * Method to add a layout that uses the list data
	 *
	 * @param   string  $layout  layout to add
	 *
	 * @return $this
	 */
	protected function addListLayout($layout)
	{
		if (!in_array($layout, $this->listLayouts))
		{
			$this->listLayouts[] = $layout;
		}

		return $this;
	}
}
