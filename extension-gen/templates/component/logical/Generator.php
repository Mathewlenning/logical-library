<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

use ExtensionGen\Extension\Custom, ExtensionGen\Utility\Inflector, ExtensionGen\Xml\Menu;

class LogicalComponent extends Custom
{
	/**
	 * @var ExtensionGen\Extension\Component
	 */
	protected $baseGenerator;

	/**
	 * Method to generate a new component
	 *
	 * @param   ExtensionGen\Manifest\Component  $manifest       a manifest generator
	 * @param   array                            $searchReplace  array of search replace values array('search' => array, 'replace'=> array)
	 *
	 * @return bool
	 */
	public function generate($manifest, $searchReplace)
	{
		$this->baseGenerator->generateCommon($manifest, $searchReplace);

		$adminDir = $this->baseGenerator->getAdminDir();

		$this->preparePath($adminDir . '/controller');
		$dispatcher = $this->generateTemplate('dispatcher.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/controller/dispatcher.php', $dispatcher);

		$searchReplace['search'][] = '%viewName%';
		$searchReplace['replace'][] = 'Shared';

		$this->preparePath($adminDir . '/view/shared/tmpl');

		$sharedView = $this->generateTemplate('sharedView.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/view/shared/html.php', $sharedView);

		$sidebarTmpl = $this->generateTemplate('sidebarTmpl.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/view/shared/tmpl/sidebar.php', $sidebarTmpl);

		$sidebarXml = simplexml_load_file($this->tmplDir .'/sidebar.xml');
		$sidebarXml->saveXML($adminDir . '/view/shared/tmpl/sidebar.xml');

		$sqlDir = $adminDir .'/sql';
		$this->preparePath($sqlDir .'/updates/mysql');
		touch($sqlDir .'/uninstall.mysql.utf8.sql');
		touch($sqlDir .'/updates/mysql/0.0.1.sql');

		return true;
	}

	/**
	 * Method to prepare the manifest before saving it for the first time.
	 *
	 * @param \SimpleXMLElement $xml
	 */
	public function onBeforeCreateManifest($xml)
	{
		$dependencies = $xml->addChild('dependencies');
		$dependencies->addAttribute('folder', 'dependencies');

		$dependencies->addChild('file', 'logical-library/library/logical');
		$dependencies->addChild('file', 'logical-library/plugin/system/logical');
	}

	/**
	 * Method to add one or more Views to a component
	 *
	 * @param   string   $viewName       Name of the view
	 * @param   array    $formats        Formats to make
	 * @param   boolean  $addSidebar     Should we add this view to the sidebar?
	 * @param   boolean  $addForm        Should we add a form view?
	 * @param   array    $searchReplace  Array of search/replace keys/values
	 * @param   string   $application    Should this view be placed in site or admin
	 *
	 * @return boolean
	 */
	public function addView($viewName, $formats, $addSidebar, $addForm, $searchReplace, $application = 'admin')
	{
		$adminDir = $this->baseGenerator->getAdminDir();
		$componentName = $this->baseGenerator->getComponentName();
		$searchReplace['search'][] = '%format%';

		$location = $adminDir;

		if($application == 'site')
		{
			$location =  $this->baseGenerator->getSiteDir();
		}

		$this->preparePath($location . '/view/' . strtolower($viewName) . '/tmpl');
		$lang = new ExtensionGen\Extension\Utility\Language($adminDir, $this->tmplDir, $this, 'com_'. strtolower($componentName));
		$VIEWNAME = strtoupper($viewName);

		foreach ($formats AS $format)
		{
			$formatSearch = array_merge($searchReplace['replace'], array(ucfirst(strtolower($format))));

			if (strtolower($format) != 'html')
			{
				$view = $this->generateTemplate('view.php.txt', $searchReplace['search'], $formatSearch);
				file_put_contents($location . '/view/' . strtolower($viewName) . '/' . strtolower($format) . '.php', $view);

				continue;
			}

			$defaultTmpl = $this->generateTemplate('defaultTmpl.php.txt', $searchReplace['search'], $formatSearch);
			file_put_contents($location . '/view/' . strtolower($viewName) . '/tmpl/default.php', $defaultTmpl);

			$defaultTmplDataTable = $this->generateTemplate('defaultTmplDataTable.php.txt', $searchReplace['search'], $formatSearch);
			file_put_contents($location . '/view/' . strtolower($viewName) . '/tmpl/default_data_table.php', $defaultTmplDataTable);

			if  ($application == 'admin' && $addSidebar)
			{
				$this->addSidebarLink($viewName);
			}

			$lang->addTranslation(
				$lang->getTranslationKey('HEADER_' . $VIEWNAME . '_DEFAULT'),
				ucfirst($viewName) .' Manager',
				$VIEWNAME . ' DEFAULT'
			);

			if ($addForm)
			{
				$formTmpl = $this->generateTemplate('formTmpl.php.txt', $searchReplace['search'], $formatSearch);
				file_put_contents($location . '/view/' . strtolower($viewName) . '/tmpl/form.php', $formTmpl);

				$formFieldTmpl = $this->generateTemplate('formFieldTmpl.php.txt', $searchReplace['search'], $formatSearch);
				file_put_contents($location . '/view/' . strtolower($viewName) . '/tmpl/form_field.php', $formFieldTmpl);

				$lang->addTranslation(
					$lang->getTranslationKey('HEADER_' . $VIEWNAME . '_FORM'),
					ucfirst($viewName) .' Manager [Add/Edit]',
					$VIEWNAME . ' FORM'
				);
			}

			$view = $this->generateTemplate('view.html.php.txt', $searchReplace['search'], $formatSearch);
			file_put_contents($location . '/view/' . strtolower($viewName) . '/' . strtolower($format) . '.php', $view);
		}

		$lang->save();
		return true;
	}

	/**
	 * Method to add xml definition to the shared sidebar xml file
	 *
	 * @param  string  $viewName name of the view
	 *
	 * @return void
	 */
	protected function addSidebarLink($viewName)
	{
		$adminDir = $this->baseGenerator->getAdminDir();
		if(!file_exists($adminDir . '/view/shared/tmpl/sidebar.xml'))
		{
			return;
		}

		$VIEWNAME = strtoupper($viewName);
		$COMPONENTNAME = strtoupper($this->baseGenerator->getComponentName());
		$viewName = strtolower($viewName);

		$sidebar = simplexml_load_file($adminDir . '/view/shared/tmpl/sidebar.xml');

		$link = $sidebar->addChild('link');
		$link->addAttribute('href', 'index.php?option=com_'. strtolower($COMPONENTNAME) . '&view=' . $viewName);
		$link->addAttribute('view', $viewName);
		$link->addAttribute('text', 'COM_' . $COMPONENTNAME . '_HEADER_' . $VIEWNAME .'_DEFAULT');

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($sidebar->asXML());
		$dom->save($adminDir . '/view/shared/tmpl/sidebar.xml');
	}

	public function addModel($modelName, $modelType, $addForm, $addTable, $application = 'admin', $searchReplace = array())
	{
		$adminDir = $this->baseGenerator->getAdminDir();
		$this->preparePath($adminDir . '/model');

		// Create the Model
		$model = $this->generateTemplate('model.php.txt', $searchReplace['search'], $searchReplace['replace']);
		file_put_contents($adminDir . '/model/' . strtolower($modelName) . '.php', $model);

		$default_id = strtolower(Inflector::toSingular($modelName)) . '_id';

		if ($addForm)
		{
			$fields = array();

			if (in_array($modelType,array('data', 'collection', 'record')))
			{
				$fields[] = $default_id;
			}

			$this->addForm($modelName, 'standard', $fields, $application);
		}

		if ($addTable)
		{
			$this->preparePath($adminDir . '/table');

			$searchReplace['search'][] = '%primary_key%';
			$searchReplace['replace'][] = $default_id;

			$table = $this->generateTemplate('table.php.txt', $searchReplace['search'], $searchReplace['replace']);
			file_put_contents($adminDir . '/table/' . strtolower($modelName) . '.php', $table);
		}


		return true;
	}

    /**
     * Method to add a form to the component
     *
     * @param   string  $formName      name of the form xml file without extension
     * @param   string  $fieldSetName  name of the fieldset to add the fields
     * @param   array   $fields        field names
     * @param   string  $application   what part of the application (admin, site)
     *
     * @return boolean
     */
    public function addForm($formName, $fieldSetName = 'standard', $fields = array(), $application = 'admin')
    {
        // We only store forms in the admin area
        $baseDir = $this->baseGenerator->getAdminDir();
        $componentName = $this->baseGenerator->getComponentName();
        $lang = new \ExtensionGen\Extension\Utility\Language($baseDir, $this->tmplDir, $this, 'com_'. strtolower($componentName));

        $formPath =  $this->baseGenerator->getAdminDir() . '/model/forms';
        $this->preparePath($formPath);

        $form = new \ExtensionGen\Form\Base($formPath, strtolower($formName));

        $form->addFieldset($fieldSetName, $lang, $fields);

        return true;
    }

	public function addFormFromSql($sqlFile, $sqlDir = null, $fieldSetName = 'standard', $application = 'admin')
	{
		// We only store forms in the admin area
		$baseDir = $this->baseGenerator->getAdminDir();
		$componentName = $this->baseGenerator->getComponentName();
		$lang = new \ExtensionGen\Extension\Utility\Language($baseDir, $this->tmplDir, $this, 'com_'. strtolower($componentName));

		$formPath =  $this->baseGenerator->getAdminDir() . '/model/forms';
		$this->preparePath($formPath);

		if (empty($sqlDir))
		{
			$sqlDir = $baseDir .'/sql/updates/mysql';
		}

		$sql = new \ExtensionGen\Sql\Base($sqlDir, $sqlFile);

		$forms = $sql->getFormFields();

		foreach ($forms AS $formName => $fields)
		{
			$form = new \ExtensionGen\Form\Base($formPath, strtolower($formName));
			$form->addFieldset($fieldSetName, $lang, $fields);
		}

		return true;
	}

	public function addMenuXml($viewName, $layoutName, $titleValue, $descValue, $addRequest)
	{
		$baseDir = $this->baseGenerator->getSiteDir();
		$lang = new \ExtensionGen\Extension\Utility\Language($this->baseGenerator->getAdminDir(), $this->tmplDir, $this, 'com_'. strtolower($this->baseGenerator->getComponentName()).'.sys');

		$viewPath = $baseDir .'/view/' . strtolower($viewName) . '/tmpl';

		echo "\n" . $viewPath . "\n";
		$fileName = strtolower($layoutName);

		$menuXml = new Menu($viewPath, $fileName);

		$menuXml->addLayoutSection($viewName,$layoutName,$titleValue, $descValue, $lang);

		if ($addRequest)
		{
			$menuXml->addRequestSection();
		}

		return true;
	}
}

$className = 'LogicalComponent';
