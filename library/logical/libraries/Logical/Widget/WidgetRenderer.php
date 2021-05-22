<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Widget;

// No direct access
defined('_JEXEC') or die;

use Logical\Access\AccessInterface;
use Logical\Access\UserAccess;
use Logical\Registry\Registry;

use ErrorException;
use SimpleXMLElement;
use JPath, JText;

/**
 * Class WidgetRenderer
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
class WidgetRenderer implements WidgetRendererInterface
{
	/**
	 * Associative array of paths to search for template files in
	 *
	 * @var array
	 */
	protected static $paths = array();

	/**
	 * Name of the xml template file to load
	 *
	 * @var string
	 */
	protected static $templateFile = 'bootstrap-v2.xml';

	/**
	 * Associative array of tagName => controlHandlerClassNames to send to the factory
	 *
	 * @var array
	 */
	protected static $controls = array();

	/**
	 * @var SimpleXMLElement
	 */
	protected static $templateXml;

	/**
	 * Widget Factory
	 *
	 * @var WidgetFactoryInterface
	 */
	protected $factory;

	/**
	 * @var AccessInterface|UserAccess
	 */
	protected $acl;

	/**
	 * @var array
	 */
	static protected $xmlElements = array();

	/**
	 * @var array
	 */
	static protected $htmlElements = array();

	/**
	 * Constructor
	 *
	 * @param   WidgetFactoryInterface  $factory      Layout factory used to convert xml layouts
	 * @param   bool                    $setControls  Should the render set the controls to the factory?
	 * @param   AccessInterface         $acl          access control object
	 */
	public function __construct(WidgetFactoryInterface $factory = null, $setControls = true, AccessInterface $acl = null)
	{
		if (is_null($factory))
		{
			$factory = new WidgetFactory;
		}

		if ($setControls)
		{
			$factory->setControls(static::$controls);
		}

		$this->factory = $factory;

		if (is_null($acl))
		{
			$acl = new UserAccess;
		}

		$this->acl = $acl;

		$this->loadTemplate();
	}

	/**
	 * Method to load the xml template file
	 *
	 * @return SimpleXMLElement
	 *
	 * @throws ErrorException
	 */
	protected function loadTemplate()
	{
		if ((static::$templateXml instanceof SimpleXMLElement))
		{
			return static::$templateXml;
		}

		if (!$templateLocation = JPath::find(static::$paths, static::$templateFile))
		{
			throw new ErrorException(JText::sprintf('LOGICAL_WIDGET_RENDERER_ERROR_TEMPLATE_ERROR_FILE_NOT_FOUND', static::$templateFile), 500);
		}

		static::$templateXml = simplexml_load_file($templateLocation);

		return static::$templateXml;
	}

	/**
	 * Method to set the paths to search for templates
	 *
	 * @param   mixed  $paths  a string or an array of strings containing base paths to look for widget templates
	 *
	 * @return void
	 */
	public static function setSearchPaths($paths)
	{
		if (!is_array($paths))
		{
			$paths = array($paths);
		}

		foreach ($paths AS $path)
		{
			static::$paths[] = $path;
		}
	}

	/**
	 * Method to get the search paths
	 *
	 * @return array
	 */
	public function getSearchPaths()
	{
		return static::$paths;
	}

	/**
	 * Method to set the name of the template file to use
	 * This allows you to switch between CSS frameworks
	 *
	 * @param   string  $templateFile  the name of the template file including the extension
	 *
	 * @return void
	 */
	public static function setTemplateFile($templateFile)
	{
		self::$templateFile = $templateFile;
	}

	/**
	 * Method to set Widget control object to the render
	 *
	 * @param   array  $controls  array of WidgetControlInterface object
	 *
	 * @return void
	 */
	public static function setControls($controls)
	{
		foreach ($controls as $tagName => $handler)
		{
			if (is_numeric($tagName))
			{
				throw new \InvalidArgumentException('Widget Renderer Error: Numbers cannot be use as control tags');
			}

			static::$controls[$tagName] = $handler;
		}
	}

	/**
	 * Method to convert a widget template into an HtmlElement.
	 *
	 * @param   string           $templateId   Dot separated path to the widget template
	 * @param   array            $displayData  Object which properties are used inside the widget template
	 * @param   AccessInterface  $acl          Used for checking ACL
	 *
	 * @return  WidgetElement  The converted layout object.
	 */
	public function render($templateId, $displayData = array(), AccessInterface $acl = null)
	{
		if (!isset(static::$xmlElements[$templateId]))
		{
			static::$xmlElements[$templateId] = static::$templateXml->xpath('/widgets/template[@id = "' . $templateId . '"]');
		}

		if (is_null($acl))
		{
			$acl = $this->acl;
		}

		return $this->renderElement(static::$xmlElements[$templateId][0], $displayData, $acl);
	}

	/**
	 * Method to convert an SimpleXml Element into an HtmlElement
	 *
	 * @param   SimpleXMLElement  $element      xml template instance
	 * @param   array             $displayData  Object which properties are used inside the widget template
	 * @param   AccessInterface   $acl          Used for checking ACL
	 *
	 * @return array
	 */
	public function renderElement(SimpleXMLElement $element, $displayData, AccessInterface $acl)
	{
		if (!($displayData instanceof Registry))
		{
			$displayData = new Registry($displayData);
		}

		$hash = md5($element->asXML() . (string) $displayData);

		if (empty(static::$htmlElements[$hash]))
		{
			static::$htmlElements[$hash] = $this->factory->convertToElement($element, $acl, $displayData);
		}

		return clone static::$htmlElements[$hash];
	}
}
