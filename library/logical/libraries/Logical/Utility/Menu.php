<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Utility;

// No direct access

use JLoader;

defined('_JEXEC') or die;

JLoader::register('MenusModelItem', JPATH_ADMINISTRATOR . '/components/com_menus/models/item.php');
JLoader::register('MenusTableMenu', JPATH_ADMINISTRATOR . '/components/com_menus/tables/menu.php');

class Menu
{
	static $menuItems = array();

	/**
	 * Method to create a menu item
	 *
	 * @param string $title          link title
	 * @param string $link           raw link url
	 * @param string $component      com_COMPONENT_NAME
	 * @param string $alias          default is null to auto generate alias from title
	 * @param int    $published      1 = published 0 = unpublished
	 * @param int    $parentMenuItem default parent menu item id
	 * @param int    $access         view access level id for menu
	 * @param string $language       language string or * symbol for all languages
	 *
	 * @return int  Menu item ID that was created.
	 */
	public static function createMenuItem($title, $link, $component, $alias = null, $published = 0, $parentMenuItem = 101, $access = 1, $language = '*')
	{
		$newMenu = array('id' => 0);
		$newMenu['browserNav'] = 0;
		$newMenu['template_style_id'] = 0;
		$newMenu['home'] = 0;
		$newMenu['toggle_modules_assigned'] = 1;
		$newMenu['toggle_modules_published'] = 1;

		$newMenu['title'] = $title;
		$newMenu['link'] = $link;
		$newMenu['alias'] = $alias;

		/** @var \JComponentRecord $componentId */
		$componentId = \JComponentHelper::getComponent($component);

		$newMenu['type'] = 'component';
		$newMenu['component_id'] = $componentId->id;
		$newMenu['published'] = $published;

		$menu = static::getMenuItem($parentMenuItem);
		$newMenu['menutype'] = $menu->menutype;
		$newMenu['parent_id'] = $parentMenuItem;

		$newMenu['access'] = $access;
		$newMenu['language'] = $language;


		$menuModel = new \MenusModelItem(array('ignore_request' => true));
		$menuModel->save($newMenu);

		$menuId = $menuModel->getState('item.id');

		return $menuId;
	}

    /**
     * Method to create an alias of a menu item
     *
     * @param $menuItem
     * @param $alias
     */
	public static function createMenuAlias($menuItem, $alias)
    {
        $newMenu = array('id' => 0);
        $newMenu['browserNav'] = 0;
        $newMenu['template_style_id'] = $menuItem->template_style_id;
        $newMenu['home'] = 0;
        $newMenu['toggle_modules_assigned'] = 1;
        $newMenu['toggle_modules_published'] = 1;
        $newMenu['note'] = 'Automatically created menu alias';

        $newMenu['title'] = $menuItem->title;
        $newMenu['link'] = 'index.php?Itemid=';
        $newMenu['alias'] = $alias;

        $newMenu['type'] = 'alias';
        $newMenu['component_id'] = 0;
        $newMenu['published'] = $menuItem->published;

        $newMenu['menutype'] = $menuItem->menutype;
        $newMenu['parent_id'] = $menuItem->parent_id;

        $newMenu['access'] = $menuItem->access;
        $newMenu['language'] = $menuItem->language;
        $newMenu['menuordering'] = -2;

        $newMenu['params'] = array();
        $newMenu['params']['aliasoptions'] = $menuItem->id;
        $newMenu['params']['alias_redirect'] = 1;
        $newMenu['params']['menu-anchor_title'] = '';
        $newMenu['params']['menu-anchor_css'] = '';
        $newMenu['params']['menu_image'] = '';
        $newMenu['params']['menu_image_css'] = '';
        $newMenu['params']['menu_text'] = 0;
        $newMenu['params']['menu_show'] = 0;

        $menuModel = new \MenusModelItem(array('ignore_request' => true));
        $menuModel->save($newMenu);

        $menuId = $menuModel->getState('item.id');

        return $menuId;
    }

	/**
	 * Method to get a menu item by primary key
	 *
	 * @param int $itemId
	 *
	 * @return mixed
	 */
	public static function getMenuItem($itemId)
	{
		if(!empty(static::$menuItems[$itemId]))
		{
			return static::$menuItems[$itemId];
		}

		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);

		$query->select('*')
			->from('#__menu')
			->where('id = ' . (int) $itemId);

		$menu = $dbo->setQuery($query)->loadObject();

		static::$menuItems[$itemId] = $menu;

		return static::$menuItems[$itemId];
	}

	/**
	 * Method to update a menu item
	 *
	 * @param $src
	 */
	public static function updateMenuItem($src)
	{
		$menuModel = new \MenusModelItem(array('ignore_request' => true));
		$menuModel->save($src);
	}

	/**
	 * Method to copy module assignements for a menu item
	 *
	 * @param $targetMenuId
	 * @param $copyFromMenuId
	 */
	public static function copyAssignedModules($targetMenuId, $copyFromMenuId)
	{
		if (empty($copyFromMenuId))
		{
			return;
		}

		$dbo = \JFactory::getDbo();
		$query = $dbo->getQuery(true);
		$query->select('moduleid')
			->from('#__modules_menu')
			->where('menuid = ' . $copyFromMenuId);

		$results = $dbo->setQuery($query)->loadColumn();

		foreach ($results AS $moduleId)
		{
			$query->clear();
			$query->insert('#__modules_menu')
				->set('moduleid = ' . (int) $moduleId)
				->set('menuid = ' . (int) $targetMenuId);

			$dbo->setQuery($query)->execute();
		}
	}
}
