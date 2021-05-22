<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */
namespace ExtensionGen\Extension;

use ExtensionGen\Extension\Utility\Language;
use ExtensionGen\Xml\Menu;

class Component extends Base
{
	/**
	 * @var string
	 */
	protected $componentName;

	/**
	 * @var string
	 */
	protected $adminDir;

	/**
	 * @var string
	 */
	protected $siteDir;

	/**
	 * @var \ExtensionGen\Manifest\Component
	 */
	protected $manifest;

	/**
	 * Component constructor.
	 *
	 * @param   string  $baseDir        base dir to the component root directory
	 * @param   string  $tmplDir        Template directory
	 * @param   string  $componentName  component name
	 *
	 * @throws \ErrorException
	 */
	public function __construct($baseDir, $tmplDir, $componentName)
	{
		parent::__construct($baseDir, $tmplDir);


		if(!file_exists($this->tmplDir))
		{
			throw new \ErrorException('Could not find template directory :' . $tmplDir);
		}

		$this->componentName = strtolower($componentName);

		$this->adminDir = $baseDir . '/administrator/components/com_' . $this->componentName;
		$this->siteDir = $baseDir . '/components/com_' . $this->componentName;

		$this->manifest = new \ExtensionGen\Manifest\Component($baseDir, $componentName, $this->adminDir, $this->siteDir);
	}

	/**
	 * @return string
	 */
	public function getComponentName()
	{
		return $this->componentName;
	}

	/**
	 * Method to get an extension prefix
	 *
	 * @return string
	 */
	public function getExtensionPrefix()
	{
		return 'com_'.$this->componentName;
	}

	/**
	 * @return string
	 */
	public function getAdminDir()
	{
		return $this->adminDir;
	}

	/**
	 * Method to manually set the admin directory
	 *
	 * @param   string  $adminDir  path to the admin directory relative to the base dir
	 */
	public function setAdminDir($adminDir)
	{
		$this->adminDir =  $this->baseDir . '/' .$adminDir;
	}

	/**
	 * @return string
	 */
	public function getSiteDir()
	{
		return $this->siteDir;
	}

	/**
	 * Method to manually set the site directory
	 *
	 * @param   string  $siteDir  path to the site directory relative to the base dir
	 */
	public function setSiteDir($siteDir)
	{
		$this->siteDir =  $this->baseDir . '/' . $siteDir;
	}

	/**
	 * Method to generate a new component
	 *
	 * @return bool
	 *
	 * @throws \ErrorException
	 */
	public function generate()
	{
		if(file_exists($this->baseDir . '/' . strtolower($this->componentName) . '.xml'))
		{
			throw new \ErrorException('Component manifest already exists');
		}

		// Create the common component structure
		$searchReplace = $this->getSearchReplace();

		$searchReplace['search'][] = '%version%';
		$searchReplace['replace'][] = '0.0.1';

		// If there is a custom generator then we just use it.
		if($this->executeGenerator('generate', array($this->manifest, $searchReplace)))
		{
			$this->manifest->create($this->generator);

			return true;
		}

		$adminDir = $this->adminDir;
		$siteDir = $this->siteDir;
		$this->preparePath($this->adminDir);
		$this->preparePath($this->siteDir);

		$this->generateCommon($this->manifest, $searchReplace);

		$controller = $this->generateTemplate('controller.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/controller.php', $controller);
		file_put_contents($siteDir . '/controller.php', $controller);

		$this->manifest->create();

		return true;
	}

	/**
	 * Method to generate common files and structure
	 *
	 * @param   \ExtensionGen\Manifest\Component  $manifest       component manifest
	 * @param   array                             $searchReplace  array of search replace values
	 */
	public function generateCommon($manifest, $searchReplace)
	{
		$adminDir = $this->getAdminDir();
		$siteDir = $this->getSiteDir();
		$this->preparePath($this->adminDir);
		$this->preparePath($this->siteDir);
		$componentName = $this->getComponentName();

		$installscript = $this->generateTemplate('installscript.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($this->baseDir . '/installscript.php', $installscript);

		$adminEntry = $this->generateTemplate('adminEntry.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/' . $componentName . '.php', $adminEntry);

		$siteEntry = $this->generateTemplate('siteEntry.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($siteDir . '/' . $componentName . '.php', $siteEntry);

		$router = $this->generateTemplate('router.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($siteDir . '/router.php', $router);

		$access = $this->generateTemplate('access.xml.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/access.xml', $access);

		$config = $this->generateTemplate('config.xml.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/config.xml', $config);

		$this->preparePath($adminDir . '/language/en-GB');

		$adminLang = new Utility\Language($adminDir, $this->tmplDir, $this, 'com_' . strtolower($componentName));
		$adminSysLang = new Utility\Language($adminDir, $this->tmplDir, $this, 'com_' . strtolower($componentName) .  '.sys');

		$prefix = 'COM_' . strtoupper($componentName);
		$value = $this->capitalizeOnUnderscore($componentName, true);
		$adminLang->addTranslation($prefix, $value, 'EXTENSION');
		$adminSysLang->addTranslation($prefix, $value, 'EXTENSION');

		$adminLang->addTranslation($prefix . '_DESC', $value, 'EXTENSION');
		$adminSysLang->addTranslation($prefix . '_DESC', $value, 'EXTENSION');

		$adminLang->save();
		$adminSysLang->save();
	}

	/**
	 * Method to get search/replace for templates
	 *
	 * @return array
	 */
	public function getSearchReplace()
	{
		$searchReplace = array();
		$searchReplace['search'] = array('%ComponentName%', '%component_name%', '%COMPONENT_NAME%');
		$searchReplace['replace'] = array(ucfirst($this->componentName), $this->componentName, strtoupper($this->componentName));
		return $searchReplace;
	}

	/**
	 * Method to get default language strings for a view
	 *
	 * @param   string   $viewName  view name
	 * @param   boolean  $hasForm   does it have a form layout?
	 *
	 * @return  string
	 */
	public function getViewLangContent($viewName, $hasForm)
	{
		$content = array();

		$VIEWNAME = strtoupper($viewName);
		$COMPONENTNAME = strtoupper($this->componentName);

		$content[] = '; ' . $VIEWNAME . ' - Default';
		$content[] = 'COM_' . $COMPONENTNAME . '_HEADER_' . $VIEWNAME . '_DEFAULT = "'. ucfirst($viewName) .' Manager"';

		if($hasForm)
		{
			$content[] = "\n; " . $VIEWNAME . ' - Form';
			$content[] = 'COM_' . $COMPONENTNAME . '_HEADER_' . $VIEWNAME . '_FORM = "'. ucfirst($viewName) .' Manager [Add/Edit]"';
		}

		return "\n" .implode("\n", $content) ."\n";
	}

	public function addModels($modelNames, $modelType, $addForm, $addTable, $application, $excluded = array())
	{
		foreach ($modelNames AS $modelName)
		{
			if (in_array($modelName, $excluded))
			{
				continue;
			}

			$modelName = ucfirst($modelName);
			$this->addModel($modelName, $modelType, $addForm, $addTable, $application);
		}
	}

	/**
	 * Method to add a model to the component
	 *
	 * @param   string   $modelName    Name of the model
	 * @param   string   $modelType    Type of model to extend from
	 * @param   boolean  $addForm      Should we add a form?
	 * @param   boolean  $addTable     Should we add a table?
	 * @param   string   $application  Should this model be placed in site or admin
	 *
	 * @return $this to allow chaining
	 */
	public function addModel($modelName, $modelType, $addForm, $addTable, $application = 'admin')
	{
		$xml = simplexml_load_file($this->baseDir . '/' . strtolower($this->componentName) . '.xml');

		$version = (string) $xml->version;

		$searchReplace = $this->getSearchReplace();

		$searchReplace['search'][] = '%modelName%';
		$searchReplace['replace'][] = $modelName;

		$searchReplace['search'][] = '%ModelName%';
		$searchReplace['replace'][] = ucfirst($modelName);

		$searchReplace['search'][] = '%model_name%';
		$searchReplace['replace'][] = strtolower($modelName);

		$searchReplace['search'][] = '%MODEL_NAME%';
		$searchReplace['replace'][] = strtoupper($modelName);

		$searchReplace['search'][] = '%version%';
		$searchReplace['replace'][] = $version;

		$searchReplace['search'][] = '%modelType%';
		$searchReplace['replace'][] = ucfirst(strtolower($modelType));

		if($this->executeGenerator('addModel', array($modelName, $modelType, $addForm, $addTable, $application, $searchReplace)))
		{
			return $this;
		}

		$adminDir = $this->adminDir;
		$location = $adminDir;

		if($application == 'site')
		{
			$location = $this->siteDir;
		}

		$this->preparePath($location. '/models');

		// Create the Model
		$model = $this->generateTemplate('model.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($location . '/models/' . strtolower($modelName) . '.php', $model);

		if ($addForm)
		{
			$standardLabel = 'COM_'. strtoupper($this->componentName) . '_LEGEND_' . strtoupper($modelName) . '_FORM';

			$formXml = simplexml_load_file($this->tmplDir . '/form.xml');
			$standardFieldset = $formXml->addChild('fieldset');
			$standardFieldset->addAttribute('name', 'standard');
			$standardFieldset->addAttribute('label', $standardLabel);

			$this->preparePath($location . '/models/forms');
			$formXml->asXML($location . '/models/forms/' . strtolower($modelName) . '.xml');
		}

		if ($addTable)
		{
			$this->preparePath($adminDir . '/tables');

			$table = $this->generateTemplate('table.php.txt', $searchReplace['search'], $searchReplace['replace']);
			file_put_contents($adminDir . '/tables/' . strtolower($modelName) . '.php', $table);
		}

		return $this;
	}

	/**
	 * Method to generate multiple views
	 *
	 * @param   array    $views        name of views to generate
	 * @param   string   $formats      comma separated list of formats to use
	 * @param   boolean  $addSidebar   should these views be added to the sidebar?
	 * @param   boolean  $addForm      should we add a form to these views?
	 * @param   boolean  $application  Should this view be placed in site or admin
	 * @param   array    $excluded     views we should not generate
	 *
	 * @return $this
	 */
	public function addViews($views, $formats, $addSidebar, $addForm, $application, $excluded = array())
	{
		foreach ($views AS $viewName)
		{
			if (in_array($viewName, $excluded))
			{
				continue;
			}

			$viewName = ucfirst($viewName);
			$this->addView($viewName, $formats, $addSidebar, $addForm, $application);
		}

		return $this;
	}

	/**
	 * Method to add one or more Views to a component
	 *
	 * @param   string   $viewName     Name of the view
	 * @param   string   $formats      Comma separated list of Formats to make
	 * @param   boolean  $addSidebar   Should we add this view to the sidebar?
	 * @param   boolean  $addForm      Should we add a form view?
	 * @param   string   $application  Should this view be placed in site or admin
	 *
	 * @return boolean
	 */
	public function addView($viewName, $formats, $addSidebar, $addForm, $application = 'admin')
	{
		// Create the View formats
		$formats = explode(',', $formats);

		if (!is_array($formats))
		{
			$formats = array($formats);
		}

		$xml = simplexml_load_file($this->baseDir . '/' . strtolower($this->componentName) . '.xml');
		$version = (string) $xml->version;

		$searchReplace = $this->getSearchReplace();

		$searchReplace['search'][] = '%viewName%';
		$searchReplace['replace'][] = $viewName;

		$searchReplace['search'][] = '%ViewName%';
		$searchReplace['replace'][] = ucfirst($viewName);

		$searchReplace['search'][] = '%view_name%';
		$searchReplace['replace'][] = strtolower($viewName);

		$searchReplace['search'][] = '%VIEWNAME%';
		$searchReplace['replace'][] = strtoupper($viewName);

		$searchReplace['search'][] = '%version%';
		$searchReplace['replace'][] = $version;

		if($this->executeGenerator('addView', array($viewName, $formats, $addSidebar, $addForm, $searchReplace, $application)))
		{
			return true;
		}

		// No generator means we use default logical behavior
		$adminDir = $this->adminDir;
		$location = $adminDir;

		if($application == 'site')
		{
			$location = $this->siteDir;
		}

		$this->preparePath($location .'/views/' . strtolower($viewName) . '/tmpl');


		$lang = new Utility\Language($location, $this->tmplDir, $this, 'com_'. strtolower($this->componentName));
		$VIEWNAME = strtoupper($viewName);

		$searchReplace['search'][] = '%format%';

		foreach ($formats AS $format)
		{
			$formatSearch = array_merge($searchReplace['replace'], array(ucfirst(strtolower($format))));

			if (strtolower($format) == 'html')
			{
				$defaultTmpl = $this->generateTemplate('defaultTmpl.php.txt', $searchReplace['search'], $formatSearch);
				file_put_contents($location . '/views/' . strtolower($viewName) . '/tmpl/default.php', $defaultTmpl);

				$lang->addTranslation(
					$lang->getTranslationKey('HEADER_' . $VIEWNAME . '_DEFAULT'),
					ucfirst($viewName) .' Manager',
					$VIEWNAME . ' Default'
				);

				if ($addForm)
				{
					$formTmpl = $this->generateTemplate('formTmpl.php.txt', $searchReplace['search'], $formatSearch);
					file_put_contents($location . '/views/' . strtolower($viewName) . '/tmpl/edit.php', $formTmpl);

					$lang->addTranslation(
						$lang->getTranslationKey('HEADER_' . $VIEWNAME . '_FORM'),
						ucfirst($viewName) .' Manager [Add/Edit]',
						$VIEWNAME . ' Form'
					);
				}

				$view = $this->generateTemplate('view.html.php.txt', $searchReplace['search'], $formatSearch);
				file_put_contents($location . '/views/' . strtolower($viewName) . '/view.' . strtolower($format) . '.php', $view);

				continue;
			}

			$view = $this->generateTemplate('view.php.txt', $searchReplace['search'], $formatSearch);
			file_put_contents($location . '/views/' . strtolower($viewName) . '/view.' . strtolower($format) . '.php', $view);
		}

		$lang->save();

		return true;
	}

	/**
	 * Method to generate JForm xml definitions from sql file
	 *
	 * @param   string  $sqlFile       SQL file name without the extension (I.E. .sql)
	 * @param   null    $sqlDir        directory of the sql file
	 * @param   string  $fieldSetName  fieldset to put all the fields into
	 * @param   string  $application   part of the application to put the forms
	 *
	 * @return $this
	 */
    public function addFormFromSql($sqlFile,  $sqlDir = null, $fieldSetName = 'standard', $application = 'admin')
    {
	    if($this->executeGenerator('addFormFromSql', array($sqlFile, $sqlDir, $fieldSetName, $application)))
	    {
		    return $this;
	    }

	    $baseDir = ($application == 'admin') ? $this->adminDir : $this->siteDir;
	    $lang = new \ExtensionGen\Extension\Utility\Language($baseDir, $this->tmplDir, $this, 'com_'. strtolower($this->componentName));

	    $formPath = $baseDir . '/models/forms';
	    $this->preparePath($formPath);

	    if (empty($sqlDir))
	    {
	    	$sqlDir = $this->adminDir .'/sql';
	    }

	    $sql = new \ExtensionGen\Sql\Base($sqlDir, $sqlFile);
	    $forms = $sql->getFormFields();

	    foreach ($forms AS $formName => $fields)
	    {
		    $form = new \ExtensionGen\Form\Base($formPath, strtolower($formName));
		    $form->addFieldset($fieldSetName, $lang, $fields);
	    }

	    return $this;
    }

    public function getFormsFromSQL($sqlDir, $sqlFile)
    {
	    $sql = new \ExtensionGen\Sql\Base($sqlDir, $sqlFile);
	    return $sql->getFormFields();
    }

	/**
	 * Method to add multiple forms to the component
	 *
	 * @param   array   $forms         associative array of form names and fields
	 * @param   string  $fieldSetName  name of the fieldset to add the fields
	 * @param   string  $application   what part of the application (admin, site)
	 * @param   array   $excluded      forms we should not generate
	 *
	 * @return $this
	 */
    public function addForms($forms, $fieldSetName = 'standard', $application = 'admin', $excluded = array())
    {
	    foreach ($forms AS $formName => $fields)
	    {
		    if (in_array($formName, $excluded))
		    {
			    continue;
		    }

	    	$this->addForm($formName, $fieldSetName, $fields, $application);
	    }
    }

	/**
	 * Method to add a form to the component
	 *
	 * @param   string  $formName      name of the form xml file without extension
	 * @param   string  $fieldSetName  name of the fieldset to add the fields
	 * @param   array   $fields        field names
	 * @param   string  $application   what part of the application (admin, site)
	 *
	 * @return $this
	 */
	public function addForm($formName, $fieldSetName = 'standard', $fields = array(), $application = 'admin')
	{
		if($this->executeGenerator('addForm', array($formName, $fieldSetName, $fields, $application)))
		{
			return $this;
		}

		$baseDir = ($application == 'admin') ? $this->adminDir : $this->siteDir;
		$lang = new \ExtensionGen\Extension\Utility\Language($baseDir, $this->tmplDir, $this, 'com_'. strtolower($this->componentName));

		$formPath = $baseDir . '/models/forms';
		$this->preparePath($formPath);

		$form = new \ExtensionGen\Form\Base($formPath, strtolower($formName));
		$form->addFieldset($fieldSetName, $lang, $fields);

		return $this;
	}

	public function addMenuXml($viewName, $layoutName, $titleValue, $descValue, $addRequest)
	{
		if($this->executeGenerator('addMenuXml', array($viewName, $layoutName, $titleValue, $descValue, $addRequest)))
		{
			return $this;
		}

		$baseDir = $this->siteDir;
		$lang = new \ExtensionGen\Extension\Utility\Language($this->adminDir, $this->tmplDir, $this, 'com_'. strtolower($this->componentName).'.sys');

		$viewPath = $baseDir .'/views/' . strtolower($viewName) . '/tmpl';
		$fileName = strtolower($layoutName);

		$menuXml = new Menu($viewPath, $fileName);

		$menuXml->addLayoutSection($viewName,$layoutName,$titleValue, $descValue, $lang);

		if ($addRequest)
		{
			$menuXml->addRequestSection();
		}
	}

	/**
	 * Method to append a string to language file
	 *
	 * @param   string $fileName Language file name
	 * @param   string $string string to add to the file
	 * @param   string $path path of the language file
	 *
	 * @return bool
	 */
	protected function appendToLanguage($fileName, $string, $path)
	{
		if(!file_exists($path . '/' . $fileName))
		{
			$this->preparePath($path);
			$searchReplace = $this->getSearchReplace();

			$langInit = $this->generateTemplate('lang.ini.txt', $searchReplace['search'], $searchReplace['replace']);
			file_put_contents($path . '/' . $fileName, $langInit);
		}

		return parent::appendToLanguage($fileName, $string, $path);
	}

	/**
	 * Method to add an update sql file to the component update directory using the next version number
	 *
	 * @param $releaseType
	 *
	 * @return void
	 */
	public function addUpdateSql($releaseType)
	{
		$xml = simplexml_load_file($this->baseDir . '/' . strtolower($this->componentName) . '.xml');

		$versionParts = explode('.', (string) $xml->version);

		switch ($releaseType)
		{
			case 'ma':
				$versionParts[0]++;
				$versionParts[1] = 0;
				$versionParts[2] = 0;
				break;
			case 'mi':
				$versionParts[1]++;
				$versionParts[2] = 0;
				break;
			case 'b':
				$versionParts[2]++;
				break;
			default:
				break;
		}

		$updateSQLFileName = implode('.',$versionParts) .'.sql';
		$path = $this->adminDir . '/sql/updates/mysql';
		$this->preparePath($path);
		touch($path . '/' . $updateSQLFileName);
	}
}
