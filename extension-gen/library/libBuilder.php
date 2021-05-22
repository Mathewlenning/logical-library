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
$libName = strtolower($argv[4]);

$extension = new ExtensionGen\Extension\Library($baseDir, $tmplDir, $libName);

switch ($task)
{
	case 'libGen':
		$extension->generate();

		echo "\n ". $libName . ' library generation complete';
		break;
	case 'libRelease':

		$buildType = $argv[5];
		$manifest = $extension->getManifest();
		echo $manifest->update($buildType);
		break;
}
