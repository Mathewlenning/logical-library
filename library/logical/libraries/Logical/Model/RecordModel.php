<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Model;

// No direct access
defined('_JEXEC') or die;

use Logical\Registry\Registry;

use SimpleXMLElement;

use \JString;
use JDate, JForm, JText;
use Exception;
use ErrorException;

/**
 * Class RecordModel
 *
 * @package  Logical\Model
 *
 * @since    0.0.1
 */
abstract class RecordModel extends CollectionModel
{
	/**
	 * @var string Used to set the JForm control configuration setting.
	 *             By setting the form control to jfrom[something]
	 *             you can render froms from HMVC or AJAX request without
	 *             form input name/id collisions.
	 */
	protected $formControl = 'jform';

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk     The id of the primary key.
	 * @param   string   $class  The class name of the return item default is Logical\Registry\Registry
	 *
	 * @return  object  instance of $class.
	 * @throws ErrorException
	 */
	public function getItem($pk = null, $class = 'Logical\Registry\Registry')
	{
		if (empty($pk))
		{
			$context = $this->getContext();
			$pk = (int) $this->getState($context . '.id');
		}

		$dbo = $this->getDbo();
		$query = $this->getListQuery();

		$this->observers->update('onBeforeGetItem', array($this, $query, $pk, $class));

		$query->where('a.' . $this->getKeyName() . ' = ' . $pk);

		$dbo->setQuery($query);
		$item = new $class($dbo->loadObject());

		$this->observers->update('onAfterGetItem', array($this, $query, $item));

		return $item;
	}

	/**
	 * Method to authorise the current user for an action.
	 * This method is intended to be overridden to allow for customized access rights
	 *
	 * @param string $action      ACL action string. I.E. 'core.create'
	 * @param string $assetName   Asset name to check against.
	 * @param int    $pk          Primary key to check against
	 * @param int    $impersonate User id to impersonate, null to use the current user
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 * @see JUser::authorise
	 */
	public function allowAction($action, $assetName = null, $pk = null, $impersonate = null)
	{
		if ($action == 'core.edit' && !empty($pk) && $this->isOwner($pk, $impersonate))
		{
			$action = 'core.edit.own';
		}

		return parent::allowAction($action, $assetName, $pk, $impersonate);
	}

	/**
	 * Method to check if a record is owned by the current user
	 *
	 * @param int $pk record to check
	 * @param int    $impersonate User id to impersonate, null to use the current user
	 *
	 * @return boolean
	 * @throws ErrorException
	 */
	protected function isOwner($pk, $impersonate = null)
	{
		$table = $this->getTable();
		$ownerField = $table->getOwnerField();

		if (empty($ownerField))
		{
			return false;
		}

		$user = \Joomla\CMS\Factory::getUser($impersonate);
		$item = $table->load($pk);

		return ($user->id == $item->{$ownerField});
	}

	/**
	 * Method to import one or more files.
	 *
	 * This method is intended to be overridden by child classes.
	 *
	 * @param   array  $data   post data from the input
	 * @param   array  $files  files data from the input
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function import($data, $files)
	{
		$fileExt = explode('.', JString::strtolower($files['name']));
		$ext = array_pop($fileExt);
		$importFunction = 'import' . ucfirst($ext);

		if (!is_callable(array($this, $importFunction)))
		{
			throw new ErrorException(JText::_('LOGICAL_MODEL_ERROR_IMPORT_FILE_TYPE_NOT_SUPPORTED') . ' : ' . $files['type']);
		}

		return $this->{$importFunction}($data, $files);
	}

	/**
	 * Method to import XML
	 *
	 * @param   array  $config  from the input
	 * @param   array  $files   tml file data
	 *
	 * @return bool
	 */
	protected function importXml($config, $files)
	{
		/** @var SimpleXmlElement $xmlObject */
		$xmlObject = simplexml_load_file($files['tmp_name']);

		$records = $xmlObject->xpath('/*/record');

		$table = $this->getTable();
		$fields = $table->getFields();

		// Prevent the user from executing other methods via the form field
		$execute = array('create' => 'create', 'update' => 'update');

		if (count($records) != 0)
		{
			foreach ($records AS $record)
			{
				$recordData = array();

				foreach ($record->children() AS $name => $value)
				{
					if (isset($fields[$name]))
					{
						$type = $fields[$name]->Type;

						switch ($type)
						{
							case strpos($type, 'int'):
								$value = (int) $value;
								break;
							case strpos($type, 'datetime'):
								$date = new JDate((string) $value);
								$value = $date->toSql();
								break;
							case strpos($type, 'float'):
							case strpos($type, 'real'):
							case strpos($type, 'double'):
								$value = (float) $value;
								break;
							default:
								$value = (string) $value;
								break;
						}
					}

					$recordData[$name] = trim($value);
				}

				$this->{$execute[$config['type']]}($recordData);
			}
		}

		return true;
	}

	/**
	 * Method to import CSV
	 *
	 * @param   array  $config  from the input
	 * @param   array  $files   tml file data
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	protected function importCsv($config, $files)
	{
		if (($handle = fopen($files['tmp_name'], 'r')) == false)
		{
			$msg = JText::_('LOGICAL_IMPORT_ERROR_UNABLE_TO_READ_FILE');
			throw new ErrorException($msg);
		}

		// Prevent the user from executing other methods via the form field
		$execute = array('create' => 'create', 'update' => 'update');

		$importKeys = array();

		while (($data = fgetcsv($handle)) != false)
		{
			// Skip empty columns
			if (count($data) == 1 && $data[0] == null)
			{
				continue;
			}

			// Capture the import keys and then run the loop again
			if (empty($importKeys))
			{
				$data[0] = preg_replace('/\x{EF}\x{BB}\x{BF}/','',$data[0]);
				$importKeys = $data;

				continue;
			}

			$recordData = array();

			foreach ($importKeys AS $i => $property)
			{
				$recordData[$property] = str_ireplace('"', '',$data[$i]);
			}

			$this->{$execute[$config['type']]}($recordData);
		}

		return true;
	}

	/**
	 * Method to validate data and insert into db
	 *
	 * @param   array $src    Form data to be inserted
	 * @param   array $ignore An optional array properties to ignore while binding.
	 *
	 * @return int primary key of the created record
	 *
	 * @since 0.0.1
	 * @throws ErrorException
	 */
	public function create($src, $ignore = array())
	{
		$table = $this->getTable();
		$pkName = $table->getKeyName();

		if (!($src instanceof Registry))
		{
			$src = new Registry($src);
		}

		$pk = $src->get($pkName);

		// We're making a copy
		if (!empty($pk))
		{
			$tableData = $table->load($pk);

			$record = new Registry($tableData);
			$record->merge($src);

			if ($table->supportsOrdering())
			{
				$record->set($table->getOrderingField(), null);
			}

			$src = $record;
		}

		$this->observers->update('onBeforeCreate', array($this, $src));

		$form = $this->getForm();
		$validData = $this->validate($form, $src->toArray());

		// Always ignore check out data
		$ignore[] = 'checked_out';
		$ignore[] = 'checked_out_time';

		$table = $this->getTable();
		$pk = $table->create($validData, $ignore);

		// Clean the cache.
		$this->cleanCache();

		if (!empty($pk))
		{
			$this->setState($this->getContext() . '.id', $pk);
		}

		$this->observers->update('onAfterCreate', array($this, $src));

		return $pk;
	}

	/**
	 * Method to validate data and update into db
	 *
	 * @param   array  $src     Data to be used to update the record
	 * @param   array  $ignore  An optional array properties to ignore while binding.
	 *
	 * @throws ErrorException
	 *
	 * @return Registry
	 *
	 * @since 0.0.1
	 */
	public function update($src, $ignore = array())
	{
		$table = $this->getTable();
		$pkName = $table->getKeyName();

		if (!($src instanceof Registry))
		{
			$src = new Registry($src);
		}

		$pk = $src->get($pkName);
		$record = $this->getItem($pk);

		$record->fullMerge($src);

		$this->observers->update('onBeforeUpdate', array($this, $record));

		$form = $this->getForm();
		$validData = $this->validate($form, $record->toArray());

		// Always ignore check out data
		$ignore[] = 'checked_out';
		$ignore[] = 'checked_out_time';

		// Store the data.
		$table = $this->getTable();
		$table->update($validData, $ignore);

		// Clean up
		$this->cleanCache();
		$this->setState($this->getContext() . '.id', $validData[$pkName]);

		$this->observers->update('onAfterUpdate', array($this, $record));

		return $record;
	}

	/**
	 * Method to delete one or more records.
	 *
	 * @param   int  $id  record primary keys.
	 *
	 * @throws ErrorException
	 *
	 * @return  boolean  True if successful, false if an error occurs.
	 *
	 * @since    0.0.1
	 */
	public function delete($id)
	{
		$pk = (int) $id;
		$table = $this->getTable();
		$record = $table->load($pk);

		$this->observers->update('onBeforeDelete', array($this, $pk, $record));

		$table->delete($pk);

		$this->observers->update('onAfterDelete', array($this, $id, $record));

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to delete records that use compound keys
	 *
	 * @param  array  $src
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function deleteCompoundPk($src)
	{
		$table = $this->getTable();
		$keyName = $table->getKeyName();
		$pkNames = explode('-', $keyName);

		$pks = array();

		foreach ($pkNames AS $key)
		{
			if (empty($src[$key]))
			{
				continue;
			}

			$pks[$key] = $src[$key];
		}


		if(empty($pks))
		{
			$msg = JText::_('LOGICAL_MODEL_ERROR_COMPOUND_KEY_INCOMPLETE');
			throw new ErrorException($msg . ' : ' . $keyName);
		}

		$dbo = $this->getDbo();
		$query = $dbo->getQuery(true);

		$query->delete($this->getTableName());

		foreach ($pks AS $key => $value)
		{
			$query->where($dbo->qn($key) . ' = ' . (int) $value);
		}

		$dbo->setQuery($query);

		if (!$dbo->execute())
		{
			$msg = JText::sprintf('LOGICAL_MODEL_ERROR_DELETE_COMPOUND_FAILED', $keyName);
			throw new ErrorException($msg, 500);
		}

		// Clear the component's cache
		$this->cleanCache();

		return true;
	}

	/**
	 * Convenience Method to get the JForm object with the JForm control value
	 *
	 * @param   string  $name    The name of the form.
	 * @param   string  $source  The form file name.
	 * @param   array   $config  Configuration
	 *
	 * @return bool|JForm
	 */
	public function getForm($name = null, $source = null, $config = array())
	{
		if (is_null($source))
		{
			$source = $this->config->get('resource');
		}

		if (is_null($name))
		{
			$name = $this->getContext();
		}

		$this->observers->update('onBeforeGetForm', array($this, $name, $source, $config));

		/** @var JForm $form */
		$form = $this->loadForm($name, $source, $config);

		$this->observers->update('onAfterGetForm', array($this, $form));

		return $form;
	}

    /**
     * Method to get the JForm Form control property. Defaults to jform
     *
     * @return Registry
     * @throws Exception
     */
	public function getFormControl()
    {
        return $this->getState('form.control', $this->formControl);
    }

	/**
	 * Method to get a form object.
	 *
	 * @param   string       $name    The name of the form.
	 * @param   string       $source  The form source. Can be XML string if file flag is set to false.
	 * @param   array        $config  Optional array of options for the form creation.
	 * @param   boolean      $clear   Optional argument to force load a new form.
	 * @param   bool|string  $xpath   An optional xpath to search for the fields.
	 *
	 * @return  mixed  JForm object on success, False on error.
	 *
	 * @see     JForm
	 */
	public function loadForm($name, $source = null, $config = array(), $clear = false, $xpath = false)
	{
		// Handle the optional arguments.
		if (!isset($config['control']))
		{
			$config['control'] = $this->getFormControl();
		}

		$this->setFormPaths();
		$this->setFieldPaths();

		$form = JForm::getInstance($name, $source, $config, $clear, $xpath);

		$form->parentModel = $this;

		return $form;
	}

	/**
	 * Method to set form search paths
	 * Inheriting classes can override this method
	 *
	 * @param   array  $paths  of paths to search for form definitions
	 *
	 * @return $this
	 */
	public function setFormPaths($paths = array())
	{
		if (empty($paths))
		{
			$config = $this->config;
			$paths[] = JPATH_ADMINISTRATOR . '/components/' . $config['option'] . '/model/forms';
			$paths[] = JPATH_SITE . '/components/' . $config['option'] . '/model/forms';
		}

		foreach ($paths AS $path)
		{
			JForm::addFormPath($path);
		}

		return $this;
	}

	/**
	 * Method to set field search paths
	 * Inheriting classes can override this method
	 *
	 * @param   array  $paths  of paths to search for field definitions
	 *
	 * @return $this
	 */
	public function setFieldPaths($paths = array())
	{
		if (empty($paths))
		{
			$paths[] = JPATH_COMPONENT_ADMINISTRATOR . '/model/fields';
			$paths[] = JPATH_COMPONENT_SITE . '/model/fields';
		}

		foreach ($paths AS $path)
		{
			JForm::addFieldPath($path);
		}

		return $this;
	}

	/**
	 * Method to validate the form data.
	 *
	 * @param   JForm  $form  The form to validate against.
	 * @param   array  $src   The data to validate.
	 * @param   string $group The name of the field group to validate.
	 *
	 * @throws ErrorException
	 * @return  mixed  Array of filtered data if valid
	 *
	 * @see     JFormRule
	 * @see     JFilterInput
	 */
	public function validate($form, $src, $group = null)
	{
		if (($src instanceof Registry))
		{
			$src = $src->toArray();
		}

		// Filter and validate the form data.
		$src = $form->filter($src);

		// Check the validation results.
		if (!$form->validate($src, $group))
		{
			$msg = '';
			$i = 0;

			// Get the validation messages from the form.
			/** @var Exception $e */
			foreach ($form->getErrors() as $e)
			{
				if ($i != 0)
				{
					$msg .= '<br/>';
				}

				$msg .= $e->getMessage();
				$i++;
			}

			throw new ErrorException($msg);
		}

		return $src;
	}

	/**
	 * Method to get a redirect URL by task
	 *
	 * @param   string  $task
	 *
	 * @return  string  URL to redirect
	 */
	public function getTaskRedirect($task)
	{
		$config = $this->getConfig();

		switch ($task)
		{
			case 'add':
			case 'edit':
				return 'index.php?option=' . $config['option'] . '&view=' . $config['view'] . '&layout=form';
			break;
			case 'cancel':
				return 'index.php?option=' . $config['option'] . '&view=' . $config['view'] . '&layout=default';
				break;
			default:
				return parent::getTaskRedirect($task);
				break;
		}
	}
}
