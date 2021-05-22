<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Sql;

class Base
{
	/**
	 * @var string
	 */
	protected $sqlDir;

	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var string
	 */
	protected $fullPath;

	/**
	 * @var array
	 */
	protected $forms = array();


	public function __construct($sqlDir, $fileName)
	{
		$this->sqlDir = $sqlDir;

		if (!file_exists($sqlDir))
		{
			mkdir($sqlDir, '0777', true);
		}

		$this->fileName = $fileName;
		$this->fullPath = $sqlDir . '/' .$fileName .'.sql';

		if (!file_exists($this->fullPath))
		{
			touch($this->fullPath);
		}
	}

	/**
	 * Method to get the field definitions
	 *
	 * @return array
	 */
	public function getFormFields()
	{
		if(!empty($this->forms))
		{
			return $this->forms;
		}

		$this->forms = $this->parseForms();

		return $this->forms;
	}

	protected function parseForms()
	{
		$forms = array();

		$fileContent = file_get_contents($this->fullPath);
		$sqlComments = '@(--[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)@ms';
		$cleanSQL = trim(preg_replace($sqlComments, ' ', $fileContent));

		if (empty(trim($cleanSQL)))
		{
			return $forms;
		}

		$sqlStatements = explode(';', $cleanSQL);

		foreach ($sqlStatements AS $statement)
		{
			if(strpos($statement, 'CREATE TABLE') === false)
			{
				continue;
			}

			$formName = $this->getFormName($statement);
			$forms[$formName] = array();

			$fieldList = $this->getFieldList($statement);

			foreach ($fieldList AS $field)
			{
				$definition = $this->getFieldDefinition($field);

				if(empty($definition))
				{
					continue;
				}

				$forms[$formName][] = $definition;
			}
		}

		return $forms;
	}

	protected function getFormName($sql)
	{
		$tableNameRaw = substr($sql, (strpos($sql, '#__')+3));
		$tableNameRaw = substr($tableNameRaw,0, (strpos($tableNameRaw, '(') -2));

		$parts = explode('_', $tableNameRaw);
		array_shift($parts);

		$form = implode('_', $parts);

		return $form;
	}

	protected function getFieldList($sql)
	{
		$fieldsOnly = substr($sql, (strpos($sql, '(') + 1));
		$fieldsOnly = substr($fieldsOnly,0, strpos($fieldsOnly, ') ENGINE'));

		$fieldsList = explode("\n", trim($fieldsOnly));

		return $fieldsList;
	}

	/**
	 * Method to get the field definitions from the SQL file
	 *
	 * @return array
	 */
	protected function getFieldDefinitions()
	{
		$fields = array();

		$fileContent = file_get_contents($this->fullPath);
		$sqlComments = '@(--[^\r\n]*)|(/\*[\w\W]*?(?=\*/)\*/)@ms';
		$cleanSQL = trim(preg_replace($sqlComments, ' ', $fileContent));

		if (empty(trim($cleanSQL)))
		{
			return $fields;
		}

		$sqlStatements = explode(';', $cleanSQL);

		foreach ($sqlStatements AS $statement)
		{
			if(strpos($statement, 'CREATE TABLE') === false)
			{
				continue;
			}

			$fieldsOnly = substr($statement, (strpos($statement, '(') + 1));
			$fieldsOnly = substr($fieldsOnly,0, strpos($fieldsOnly, ') ENGINE'));

			$fieldsList = explode("\n", trim($fieldsOnly));

			foreach ($fieldsList AS $field)
			{
				$definition = $this->getFieldDefinition($field);

				if(empty($definition))
				{
					continue;
				}

				$fields[] = $definition;
			}
		}

		return $fields;
	}

	/**
	 * Method to get the field definition from create table substring
	 *
	 * @param   string  $field  single sql field definition
	 *
	 * @return array|bool
	 */
	protected function getFieldDefinition($field)
	{
		if (empty($field) || $this->isIndex($field))
		{
			return false;
		}

		$fieldRaw = explode(' ', trim($field));

		$fieldDefinition = array('name' => trim(substr($fieldRaw[0],1, -1)));

		$fieldType = strtolower($fieldRaw[1]);

		if(strpos($fieldType, '(') !== false)
		{
			$fieldType = substr($fieldType, 0, strpos($fieldType, '('));
		}

		$fieldDefinition['type'] = $fieldType;

		if ($fieldType == 'enum')
		{
			$optionString = substr($fieldRaw[1], (strpos($fieldRaw[1], '(') +1));
			$optionString = substr($optionString,0, (strpos($optionString, ')')));

			$optionList = explode(',',$optionString);

			$fieldDefinition['options'] = array();

			foreach ($optionList AS $option)
			{
				$fieldDefinition['options'][] = substr($option, 1, -1);
			}
		}

		return $fieldDefinition;
	}

	protected function isIndex($field)
	{
		if (strpos($field, 'PRIMARY KEY') !== false)
		{
			return true;
		}

		if (strpos($field, 'UNIQUE KEY') !== false)
		{
			return true;
		}

		return false;
	}
}
