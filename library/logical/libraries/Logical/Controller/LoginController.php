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

use Logical\Model\RecordModel;

use ErrorException;
use JText;
use JSession;

/**
 * Class UpdateController
 *
 * @package  Logical\Controller
 * @since    0.0.11
 */
class LoginController extends BaseController
{
	/**
	 * Method to save a new record to the database
	 *
	 * @throws ErrorException
	 *
	 * @return bool
	 */
	public function execute()
	{
		// Check for request forgeries
		$this->validateSession();

		$oldToken = JSession::getFormToken();
		/** @var \Logical\Model\BaseModel $model */
		$model = $this->getModel();
		$context = $model->getContext();

		$app = $this->getApplication();
		$input = $this->getInput();

		$data = $input->get('jform', array(), 'array');
		$data['return']    = $this->getReturnFromResponse();
		$app->setUserState('users.login.form.return', $data['return']);

		// Get the log in options.
		$options = array();
		$options['remember'] = (!empty($data['rememberMe'])) ? true : false;
		$options['return']   = $data['return'];

		// Get the log in credentials.
		$credentials = array();
		$credentials['username']  = $data['username'];
		$credentials['password']  = $data['password'];

		if(empty($data['secretkey']))
        {
            $data['secretkey'] = null;
        }

		$credentials['secretkey'] = $data['secretkey'];

		if (true !== $app->login($credentials, $options))
		{
			$data['username'] = '';
			$data['password'] = '';
			$data['secretkey'] = '';

			$this->setUserState($context . '.jform.data', json_encode($data));

			// return false to create an error without an error message
			return false;
		}

		// Success
		if ($options['remember'] == true)
		{
			$app->setUserState('rememberLogin', true);
		}

		//now that their logged in we set the token to allow other controllers to verify
		$token = JSession::getFormToken();
		$input->set($token, 1);

		$this->setUserState($context . '.jform.data', null);

		return $this->executeController();
	}

	protected function getReturnFromResponse()
	{
		$input = $this->getInput();
		$return = base64_decode($input->get('return', '', 'BASE64'));

		if (is_numeric($return))
		{
			return $this->getMenuItemReturn($return);
		}

		if (!\JUri::isInternal($return) || empty($return))
		{
			return 'index.php?option=com_users&view=profile';
		}

		return $return;
	}

	protected function getMenuItemReturn($return)
	{
		$returnUrl = 'index.php?Itemid=' . $return;

		if (!\JLanguageMultilang::isEnabled())
		{
			return $returnUrl;
		}

		//@todo might want to use menu object here
		$db = \JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('language')
			->from($db->quoteName('#__menu'))
			->where('client_id = 0')
			->where('id =' . (int) $return);

		$db->setQuery($query);

		try
		{
			$language = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			return $returnUrl;
		}

		if($language === '*')
		{
			return $returnUrl;
		}

		return $returnUrl . '&lang=' . $language;
	}
}
