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
$plgName = strtolower($argv[4]);
$plgType = $argv[5];

$plugin = new ExtensionGen\Extension\Plugin($baseDir, $tmplDir, $plgName, $plgType);

switch ($task)
{
	case 'plgGen':
		echo "\n Generating " . $plgType .' plugin';
		$plugin->generate();
		break;
	case 'plgRelease':
		$releaseType = $argv[6];

		$manifest = $plugin->getManifest();
		echo $manifest->update($releaseType);
		break;
}
