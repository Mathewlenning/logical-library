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
$modName = strtolower($argv[4]);

$module = new ExtensionGen\Extension\Module($baseDir, $tmplDir, $modName);
switch ($task)
{
	case 'modGen':
		echo "\n Generating " . $modName .' module';
		$module->generate();
		break;
	case 'modRelease':
		$releaseType = $argv[5];
		$manifest = $module->getManifest();

		echo $manifest->update($releaseType);
		break;
}
