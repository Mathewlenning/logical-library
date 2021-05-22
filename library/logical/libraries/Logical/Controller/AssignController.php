<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Controller;

// No direct access
defined('_JEXEC') or die;

use RuntimeException, ErrorException;
use JAccess, JAccessRules, JFactory, JTable;
use JTableAsset, JText;


/**
 * Class AssignController
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class AssignController extends BaseController
{
	/**
	 * Control method to assign user ACL permissions
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 * @throws RuntimeException
	 * @throws \ErrorException
	 */
	public function execute()
	{
		// Check for request forgeries
		$this->validateSession();

		$config = $this->config;
		$user = JFactory::getUser();

		if (!$user->authorise('core.admin', $config->get('option')))
		{
			$msg = JText::_('LOGICAL_ACL_ERROR_SAVE_PERMISSIONS_NOT_PERMITTED');
			throw new ErrorException($msg);
		}

		$input = $this->getInput();
		$data = $input->get('aclform', array(), 'array');
		$this->save($data);

		$this->addMessage(JText::_('LOGICAL_CONTROLLER_MESSAGE_SAVE_COMPLETED'));

		$url = 'index.php?option=' . $config->get('option') . '&view=' . $config->get('view') . '&layout=rules';
		$this->setReturn($url);

		return $this->executeController();
	}

	/**
	 * Method to save data to the the asset table
	 *
	 * @param   array  $data  the ACL permissions data
	 *
	 * @return bool
	 */
	protected function save($data)
	{
		$config = $this->config->toArray();
		$asset = JTable::getInstance('asset');

		if (!$asset->loadByName($config['option'] . '.' . $config['resource']))
		{
			/** @var JTableAsset $component */
			$component = JTable::getInstance('asset');

			// Check if there is a parent
			if (!$component->loadByName($config['option']))
			{
				$root = JTable::getInstance('asset');
				$root->loadByName('root.1');

				$component->name = $config['option'];
				$component->title = $config['option'];
				$component->setLocation($root->id, 'last-child');

				// Get the actions for the asset.
				$file = JPATH_ADMINISTRATOR . '/components/' . $config['option'] . '/access.xml';
				$xpath = "/access/section[@name='component']/";
				$actions = JAccess::getActionsFromFile($file, $xpath);

				$actionArray = array();

				foreach ($actions AS $action)
				{
					$actionArray[$action->name] = array();
				}

				$componentRules = new JAccessRules($actionArray);

				$component->rules = (string) $componentRules;

				if (!$component->check() || !$component->store())
				{
					throw new RuntimeException(JText::_('BU_ERROR_UNABLE_TO_SAVE_ACL_RULES'));
				}
			}

			$asset->name = $config['option'] . '.' . $config['resource'];
			$asset->title = $config['option'] . '.' . $config['resource'];
			$asset->setLocation($component->id, 'last-child');
		}

		foreach ($data['rules'] AS $name => $rule)
		{
			$cleanRules = array();

			foreach ($rule AS $key => $group)
			{
				if (is_numeric($group))
				{
					$cleanRules[$key] = $group;
				}
			}

			$data['rules'][$name] = $cleanRules;
		}

		$rules = new JAccessRules($data['rules']);
		$asset->rules = (string) $rules;

		if (!$asset->check() || !$asset->store())
		{
			throw new RuntimeException($asset->getError());
		}

		return true;
	}
}
