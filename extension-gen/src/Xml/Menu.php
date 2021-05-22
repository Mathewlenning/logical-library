<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace ExtensionGen\Xml;

use SimpleXMLElement;
use ExtensionGen\Extension\Utility\Language;

class Menu extends Base
{
	/**
	 * @var string
	 */
	protected $viewTmplDir;

	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var string
	 */
	protected $fullPath;

	/**
	 * @var \SimpleXMLElement
	 */
	protected $xml;

	/**
	 * Manifest Base constructor.
	 *
	 * @param   string  $viewTmplDir  dir to the menu root directory
	 * @param   string  $fileName Name of the xml file
	 */
	public function __construct($viewTmplDir, $fileName)
	{
		$this->viewTmplDir = $viewTmplDir;

		if(!file_exists($viewTmplDir))
		{
			mkdir($viewTmplDir, '0777', true);
		}

		$this->fileName = $fileName;
		$this->fullPath = $viewTmplDir . '/' . $fileName .'.xml';

		if (!file_exists($this->fullPath))
		{
			$xml = new \SimpleXMLElement('<metadata></metadata>');
			$this->save($xml, $this->fullPath);
		}

		$this->xml = simplexml_load_file($this->fullPath);
	}

	public function addLayoutSection($viewName, $layoutName, $titleValue, $descValue, Language $lang)
	{
		$layoutTitleTag = $lang->getTranslationKey('MENU_'.strtoupper($viewName) . '_' . strtoupper($layoutName) .'_TITLE');
		$layoutDescTag = $lang->getTranslationKey('MENU_'.strtoupper($viewName) . '_' . strtoupper($layoutName) .'_DESC');

		$layout = $this->addChild($this->xml, 'layout', array('title' => $layoutTitleTag));
		$this->createCdataSection($layout, 'message', $layoutDescTag);

		$lang->addTranslation($layoutTitleTag, $titleValue, 'MENU');
		$lang->addTranslation($layoutDescTag, $descValue, 'MENU');

		$lang->save();

		$this->save($this->xml, $this->fullPath);
	}

	public function addRequestSection()
	{
		$fields = $this->addChild($this->xml, 'fields', array('name' => 'request'));
		$this->addChild($fields, 'fieldset', array('name' => 'request'));

		$this->save($this->xml, $this->fullPath);
	}
}
