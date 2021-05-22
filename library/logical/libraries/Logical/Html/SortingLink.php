<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Html;

// No direct access
defined('_JEXEC') or die;

use Logical\Widget\WidgetRendererInterface;

class SortingLink extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	protected $href;

	protected $sortColumn;

	protected $text;

	protected $hasCarrot = false;

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface $widgetRenderer widget render
	 * @param   string                  $href
	 * @param   string                  $sortColumn
	 * @param   string                  $text
	 * @param   array|string                  $currentSortOrder
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer, $href, $sortColumn, $text, $currentSortOrder)
	{
		parent::__construct($widgetRenderer);

		$this->href = $href;
		$this->sortColumn = $sortColumn;
		$this->text = $text;

		if(!is_array($currentSortOrder))
        {
            $currentSortOrder = array($currentSortOrder);
        }

		foreach ($currentSortOrder AS $sortOrderColumn)
        {
            if ($this->sortColumn == $sortOrderColumn)
            {
                $this->text .= ' <i class="icon-sort"></i>';
            }
        }
	}

	/**
	 * Method to render the element
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		$displayData = array(
			'href' => $this->href,
			'sort-column' => $this->sortColumn,
			'text' => $this->text
		);

		return $this->widgetRenderer->render('filter.sort.control', $displayData);
	}
}
