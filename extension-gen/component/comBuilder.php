<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

require_once __DIR__ .'/../src/autoloader.php';

$task = $argv[1];
$baseDir = $argv[2];
$tmplDir = $argv[3];
$componentName = strtolower($argv[4]);
$component = new ExtensionGen\Extension\Component($baseDir, $tmplDir, $componentName);

switch ($task)
{
	case 'comGen':
		$component->generate();
		echo 'Component generation complete';

		break;
	case 'comAddView':
		$viewName = ucfirst($argv[5]);
		$formats = $argv[6];
		$addSidebar = ($argv[7] == 'Y');
		$modelType = $argv[8];
		$addForm = ($argv[9] == 'Y');
		$addTable = ($argv[10] == 'Y');
		$application = $argv[11];
		$component->addView($viewName, $formats, $addSidebar, $addForm, $application);

		if($addForm)
		{
			$component->addModel($viewName, $modelType, $addForm, $addTable, $application);
		}

		echo $viewName . ' generation complete';
		break;
	case 'comAddViewMenu':
		$viewName = ucfirst($argv[5]);
		$layoutName = $argv[6];
		$titleValue = $argv[7];
		$descValue = $argv[8];
		$addRequest = ($argv[9] == 'Y');

		$component->addMenuXml($viewName, $layoutName, $titleValue, $descValue, $addRequest);

		break;
	case 'comAddViewsFromSQL':
		$sqlDir = $argv[5];
		$sqlFile = $argv[6];
		$formats = $argv[7];
		$addSidebar = ($argv[8] == 'Y');
		$modelType = $argv[9];
		$addForm = ($argv[10] == 'Y');
		$addTable = ($argv[11] == 'Y');
		$application = $argv[12];
		$excludedViews = empty($argv[13]) ? array() : explode(',', $argv[13]);

		$forms = $component->getFormsFromSQL($sqlDir, $sqlFile);
		$views = array_keys($forms);

		$component->addViews($views, $formats, $addSidebar, $addForm, $application, $excludedViews);

		if ($addForm)
		{
			$component->addModels($views, $modelType, $addForm, $addTable, $application, $excludedViews);
			$component->addForms($forms, 'standard', $application, $excludedViews);
		}

		break;
	case 'comAddModel':
		$modelName = ucfirst($argv[5]);
		$modelType = $argv[6];
		$addForm = ($argv[7] == 'Y');
		$addTable = ($argv[8] == 'Y');
		$application = $argv[9];

		$component->addModel($modelName, $modelType, $addForm, $addTable, $application);

		echo $modelName . ' generation complete';

		break;
	case 'comRelease':
		$buildType = $argv[5];
		$manifest = $component->getManifest();
		echo $manifest->update($buildType);
		break;
    case 'comAddForm':
    	$formName = $argv[5];
    	$fieldSetName = $argv[6];
    	$fieldNames = $argv[7];
    	$application =$argv[8];

    	if(!empty($fieldNames) && !is_array($fieldNames))
	    {
	    	$fieldNames = explode(',', $fieldNames);
	    }

	    $component->addForm($formName, $fieldSetName, $fieldNames, $application);
    	echo count($fieldNames) . ' fields added to ' . $fieldSetName . 'fieldset of ' . $formName;
        break;
	case 'comAddFormFromSql':
		$sqlDir = $argv[5];
		$sqlFile = $argv[6];
		$fieldSetName = $argv[7];
		$application = $argv[8];
		$excluded = empty($argv[9]) ? array() : explode(',', $argv[9]);

		$forms = $component->getFormsFromSQL($sqlDir, $sqlFile);
		$component->addForms($forms, $fieldSetName, $application, $excluded);

	case 'addUpdateSQL':
		$releaseType =  $argv[5];
		$component->addUpdateSql($releaseType);
		break;
}
