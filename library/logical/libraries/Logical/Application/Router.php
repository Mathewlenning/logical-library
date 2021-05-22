<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Application;

// No direct access
defined('_JEXEC') or die;

use JFactory;
/**
 * Class Router
 *
 * @package  Logical\Application
 * @since    0.0.5
 */
class Router
{
	/**
	 * Method to build a route from a query array
	 *
	 * @param   array  &$query  the query
	 *
	 * @return array
	 */
	public function BuildRoute(&$query)
	{
		$segments = array();

		$app = \JFactory::getApplication();
		$input = $app->input;

		if (isset($query['view']))
		{
			$segments[] = $query['view'];
		}

		if (isset($query['layout']) && $query['layout'] != 'default')
		{
			$segments[] = $query['layout'];
		}

		if (isset($query['id']))
		{
			$segments[] = $query['id'];
		}

		if (!isset($query['view']))
		{
			unset($query['view']);
			unset($query['layout']);
			unset($query['id']);

			return $segments;
		}

		$itemId = $this->getMenuItem($query);
		$needsUpdating = (!empty($itemId) && isset($query['Itemid']));

		if($needsUpdating)
		{
			$query['Itemid'] = (int) $itemId->id;
			$segments = array();
		}

		unset($query['view']);
		unset($query['layout']);
		unset($query['id']);

		return $segments;
	}

	protected function getMenuItem($urlQuery)
	{
		static $routeItems;

		if(!is_array($routeItems))
		{
			$routeItems = array();
		}

		$itemId = (isset($urlQuery['Itemid'])) ? $urlQuery['Itemid'] : 0;

		$route = $this->buildRouteFromQuery($urlQuery);

		$hash = md5($route);

		if(isset($routeItems[$hash]))
		{
			return $routeItems[$hash];
		}

		$user = JFactory::getUser();
		$viewLevels = $user->getAuthorisedViewLevels();

		$dbo = JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('id, alias')
			->from('#__menu')
			->where('link = ' . $dbo->q($route))
			->where('access IN (' . implode(',', $viewLevels) .')')
			->where('published = ' . (int) 1);

		$result = $dbo->setQuery($query)->loadObjectList('id');

		if(empty($result))
		{
			$routeItems[$hash] = 0;
		}

		if(array_key_exists($itemId, $result))
		{
			$routeItems[$hash] = $result[$itemId];
		}
		else
		{
			$routeItems[$hash] = array_shift($result);
		}

		return $routeItems[$hash];
	}

	protected function buildRouteFromQuery($urlQuery)
	{
		$app = JFactory::getApplication();
		/** @var \JInput $input */
		$input = $app->input;

		$id = $input->get('id', 0);
		$layout = $input->getCmd('layout', 'default');

		$itemId = 0;

		if(isset($urlQuery['Itemid']))
		{
			$itemId = $urlQuery['Itemid'];
			unset($urlQuery['Itemid']);
		}

		$route = 'index.php?';

		foreach ($urlQuery AS $name => $value)
		{
			if(is_array($value))
			{
				foreach($value AS $key => $keyValue)
				{
					$route .= $name .'[' . $key . ']=' .$keyValue.'&';
				}

				continue;

			}

			$route .= $name .'='.$value.'&';
		}

		$route = substr($route, 0, -1);

		if($layout != 'default' && !isset($urlQuery['layout']))
		{
			$route .= '&layout=' . $layout;
		}

		if (!empty($id) && !isset($urlQuery['id']))
		{
			$route .= '&id=' . $id;
		}

		return $route;
	}

	/**
	 * Method to parse a route into an associative query array
	 *
	 * @param   array  &$segments  array of route segments
	 *
	 * @return array
	 */
	public function ParseRoute(&$segments)
	{
		$query         = array();
		$query['view'] = array_shift($segments);

		if (strpos($query['view'], '.'))
		{
			list($view, $format) = explode('.', $query['view']);
			$query['view'] = $view;
			$query['format'] = $format;
		}

		$count = count($segments);

		if ($count)
		{
			$count--;
			$segment = array_shift($segments);

			if (is_numeric($segment))
			{
				$query['id'] = $segment;
			}
			else
			{
				$query['layout'] = $segment;
			}
		}

		if ($count)
		{
			$count--;

			$segment = array_shift($segments);

			if (is_numeric($segment))
			{
				$query['id'] = $segment;
			}
		}

		return $query;
	}
}
