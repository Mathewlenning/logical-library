<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Widget\Control;

// No direct access
defined('_JEXEC') or die;

use Logical\Widget\WidgetControlInterface;
use Logical\Widget\WidgetFactoryInterface;
use \SimpleXMLElement;
use JFactory;
use JEditor;

/**
 * Class EditorControl
 *
 * @package  Logical\Widget\Control
 * @since    0.0.25
 */
class EditorControl implements WidgetControlInterface
{
	protected $editor = null;
	/**
	 * Method to execute an inline var control
	 *
	 * @param   WidgetFactoryInterface  $factory      Calling factory
	 * @param   SimpleXMLElement        $element      <var data-source="@variableName"/>
	 * @param   array                   $displayData  Array which properties are used inside the layout file to build displayed output
	 *
	 * @return array
	 */
	public function execute(WidgetFactoryInterface $factory, SimpleXMLElement $element, $displayData = array())
	{
		$attributes = $factory->getAttributes($element, $displayData);

		$editor = $this->getEditor();
		$result = $editor->display(
			$attributes['name'],
			$attributes['value'],
			$attributes['width'],
			$attributes['height'],
			'',
			'',
			true,
			$attributes['id']
		);

		return array($result);
	}

	/**
	 * Method to get a JEditor instance
	 *
	 * @param   string  $editorType  type of editor
	 *
	 * @return JEditor|null
	 */
	protected function getEditor($editorType = null)
	{
		$editor = $this->editor;

		// Only create the editor if it is not already created.
		if (empty($this->editor))
		{
			if ($editorType)
			{
				// Get the list of editor types.
				$types = $editorType;

				// Get the database object.
				$db = JFactory::getDbo();

				// Iterate over teh types looking for an existing editor.
				foreach ($types as $element)
				{
					// Build the query.
					$query = $db->getQuery(true)
						->select('element')
						->from('#__extensions')
						->where('element = ' . $db->quote($element))
						->where('folder = ' . $db->quote('editors'))
						->where('enabled = 1');

					// Check of the editor exists.
					$db->setQuery($query, 0, 1);
					$editor = $db->loadResult();

					// If an editor was found stop looking.
					if ($editor)
					{
						break;
					}
				}
			}

			// Create the JEditor instance based on the given editor.
			if (is_null($editor))
			{
				$conf = JFactory::getConfig();
				$editor = $conf->get('editor');
			}

			$this->editor = JEditor::getInstance($editor);
		}

		return $this->editor;
	}
}
