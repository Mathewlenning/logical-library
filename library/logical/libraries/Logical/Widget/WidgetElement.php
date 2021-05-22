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

/**
 * Class Widget
 *
 * @package  Logical\Widget
 * @since    0.0.25
 */
class WidgetElement extends HtmlElement
{
	/**
	 * Method to check if this is an instance of a widget element
	 *
	 * @return bool
	 */
	public function isWidget()
	{
		return ($this->tagName == 'widget');
	}

	/**
	 * Method to add to the innerHtml
	 *
	 * @param   string|HtmlElement  $innerHtml  This can be a string value or a HtmlElement object
	 * @param   bool                $before     Should $innerHtml be placed at the beginning or the end of $this->innerHtml?
	 *
	 * @return $this to allow chaining
	 */
	public function addInnerHtml($innerHtml, $before = false)
	{
		if (($innerHtml instanceof WidgetElement) && $innerHtml->isWidget())
		{
			$innerHtml = $innerHtml->getInnerHtml(false);
		}

		return parent::addInnerHtml($innerHtml, $before);
	}

	/**
	 * Overridden to return the rendered content elements as a string
	 *
	 * @return string
	 */
	public function __toString()
	{
		if (!$this->isWidget())
		{
			return $this->renderHtml();
		}

		$html = '';

		foreach ($this->innerHtml AS $innerHtml)
		{
			$html .= (string) $innerHtml;
		}

		return $html;
	}

	/**
	 * Method to clone innerHtml elements, so changes to
	 * widgets don't propagate.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$contents = $this->getInnerHtml(false);
		$this->clearInnerHtml();

		foreach ($contents AS $content)
		{
			if (($content instanceof HtmlElement))
			{
				$content = clone $content;
			}

			$this->addInnerHtml($content);
		}
	}
}
