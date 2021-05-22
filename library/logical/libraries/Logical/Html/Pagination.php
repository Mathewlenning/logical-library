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

use Logical\Registry\Registry;
use Logical\Widget\WidgetRendererInterface;
use JText;

/**
 * Class Pagination
 *
 * @package  Logical\Pagination
 * @since    0.0.60
 */
class Pagination
{
	protected $state;

	protected $widgetRenderer;

	protected $totalPages;

	protected $startPage;

	protected $currentPage;

	protected $stopPage;

	/**
	 * Pagination constructor.
	 *
	 * @param   int                           $total           total number of records
	 * @param   Registry|null                 $state           registry object containing the model state
	 * @param   WidgetRendererInterface|null  $widgetRenderer  to render templates with
	 */
	public function __construct($total, Registry $state = null, WidgetRendererInterface $widgetRenderer = null)
	{
		if (is_null($state))
		{
			$list = array('limit' => 20, 'start' => 0, 'total' => $total);
			$state = new Registry(array('list' => $list));
		}

		$this->state = $state;
		$limit = (!empty($this->state->list->limit)) ? $this->state->list->limit: '*';
		$limitStart = (!empty($this->state->list->start)) ? $this->state->list->start : 0;

		if ($limit > $total)
		{
			$limit = 0;
		}

		if ($limit === 0)
		{
			$limitStart = 0;
			$limit = $total;
		}

		if($limit === '*')
		{
			$limit = 0;
		}

		$isOverLimit = ($limitStart > ($total - $limit));

		if ($isOverLimit)
		{
			$limitStart = max(0, (int) (ceil($total / $limit) - 1) * $limit);
		}

		if ($limit > 0)
		{
			$this->totalPages = ceil($total / $limit);
			$this->currentPage = ceil(($limitStart + 1) / $limit);
		}

		$displayedPages = 10;

		if ($displayedPages > $this->totalPages)
		{
			$displayedPages = $this->totalPages;
		}

		$this->startPage = $this->currentPage - ceil($displayedPages / 2);

		if ($this->startPage < 1)
		{
			$this->startPage = 1;
		}

		$this->stopPage = $this->startPage + $displayedPages - 1;

		if ($this->stopPage > $this->totalPages)
		{
			$this->stopPage = $this->totalPages;
		}

		if ($this->stopPage < 1)
		{
			$this->stopPage = 1;
		}

		$this->widgetRenderer = $widgetRenderer;
	}

	/**
	 * Method to set the widget renderer
	 *
	 * @param   WidgetRendererInterface  $widgetRenderer  used to render the html output
	 *
	 * @return $this
	 */
	public function setWidgetRenderer(WidgetRendererInterface $widgetRenderer)
	{
		$this->widgetRenderer = $widgetRenderer;

		return $this;
	}

	/**
	 * Creates a dropdown box for selecting how many records to show per page.
	 *
	 * @return  \Logical\Widget\WidgetElement  The HTML for the limit # input box.
	 */
	public function getLimitBox()
	{
		$widget = $this->widgetRenderer->render('filter.list.limit', array('selected' => $this->state->get('list.limit')));

		return $widget;
	}

	/**
	 * Create and return the pagination pages counter string, ie. Page 2 of 4.
	 *
	 * @return  string   Pagination pages counter string.
	 */
	public function getPagesCounter()
	{
		$html = null;

		if ($this->totalPages > 1)
		{
			$html .= \JText::sprintf('JLIB_HTML_PAGE_CURRENT_OF_TOTAL', $this->currentPage, $this->totalPages);
		}

		return $html;
	}

	/**
	 * Create and return the pagination result set counter string, e.g. Results 1-10 of 42
	 *
	 * @return  string   Pagination result set counter string.
	 */
	public function getResultsCounter()
	{
		$fromResult = $this->state->list->start + 1;
		$toResult = $this->state->list->start + $this->state->list->limit;

		// If the limit is reached before the end of the list.
		if ($toResult > $this->state->list->total)
		{
			$toResult = $this->state->list->total;
		}

		$msg = JText::_('JLIB_HTML_NO_RECORDS_FOUND');

		// If there are results found.
		if ($this->state->list->total > 0)
		{
			$msg = JText::sprintf('JLIB_HTML_RESULTS_OF', $fromResult, $toResult, $this->state->list->total);
		}

		return $msg;
	}

	/**
	 * Return the pagination footer.
	 *
	 * @param   string  $baseUrl  the base url to use for page links
	 *
	 * @return  string  Pagination footer.
	 */
	public function getListFooter($baseUrl)
	{
		if ($this->state->list->total < $this->state->list->limit)
		{
			return null;
		}

		$links = array();

		$first = array(
			'widgetId' => 'filter.list.pagination.directional.enabled',
			'link-href' => \JRoute::_($baseUrl . '&limitstart=0'),
			'link-title' => 'LOGICAL_PAGINATION_START',
			'icon-class' => 'icon-first'
		);

		$prevPage = ($this->currentPage - 2) * $this->state->limit;

		$prev = array(
			'widgetId' => 'filter.list.pagination.directional.enabled',
			'link-href' => \JRoute::_($baseUrl . '&limitstart=' . (int) $prevPage),
			'link-title' => 'LOGICAL_PAGINATION_PREV',
			'icon-class' => 'icon-previous'
		);

		if ($this->currentPage == 1)
		{
			$first['widgetId'] = 'filter.list.pagination.directional.disabled';
			$prev['widgetId'] = 'filter.list.pagination.directional.disabled';
		}

		$links[] = $first;
		$links[] = $prev;

		$stop = $this->stopPage;

		for ($i = $this->startPage; $i <= $stop; $i++)
		{
			$offset = ($i - 1) * $this->state->list->limit;

			$page = array(
				'widgetId' => 'filter.list.pagination.page.inactive',
				'link-href' => \JRoute::_($baseUrl . '&limitstart=' . (int) $offset),
				'page_number' => $i,
			);

			if ($i == $this->currentPage)
			{
				$page['widgetId'] = 'filter.list.pagination.page.active';
			}

			$links[] = $page;
		}

		$nextPage = $this->currentPage * $this->state->list->limit;
		$endPage = ($this->totalPages - 1) * $this->state->list->limit;

		$next = array(
			'widgetId' => 'filter.list.pagination.directional.enabled',
			'link-href' => \JRoute::_($baseUrl . '&limitstart=' . (int) $nextPage),
			'link-title' => 'LOGICAL_PAGINATION_NEXT',
			'icon-class' => 'icon-next'
		);

		$last = array(
			'widgetId' => 'filter.list.pagination.directional.enabled',
			'link-href' => \JRoute::_($baseUrl . '&limitstart=' . (int) $endPage),
			'link-title' => 'LOGICAL_PAGINATION_LAST',
			'icon-class' => 'icon-last'
		);

		if ($this->currentPage >= $this->totalPages)
		{
			$next['widgetId'] = 'filter.list.pagination.directional.disabled';
			$last['widgetId'] = 'filter.list.pagination.directional.disabled';
		}

		$links[] = $next;
		$links[] = $last;

		return $this->widgetRenderer->render('filter.list.pagination', array('page_links' => $links));
	}
}
