<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Html;

// No direct access
defined('_JEXEC') or die;

use Logical\Oauth\Utility;
use Logical\Widget\WidgetRendererInterface;

/**
 * Class Tabs
 *
 * @package  Logical\Html
 * @since    0.0.170
 */
class Rating extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

    /**
     * @var int Value of the rating
     */
	protected $value;

    /**
     * @var int Largest value
     */
	protected $scaleSize;

    /**
     * @var string  Class to be applied to the wrapper class
     */
	protected $wrapperClass;

    /**
     * @var string  Class to be applied to the background element
     */
	protected $bgClass;

    /**
     * @var string Class to be applied to the foreground element
     */
	protected $fgClass;

    /**
     * Constructor.
     *
     * @param   WidgetRendererInterface  $widgetRenderer  widget render
     * @param   int                      $value           the rating value
     * @param   int                      $scaleSize       largest scale value
     */
    public function __construct(WidgetRendererInterface $widgetRenderer, $value = 0, $scaleSize = 5)
    {
        parent::__construct($widgetRenderer);

        $this->value = $value;
        $this->scaleSize = $scaleSize;
    }

    /**
     * Method to set class values for the rating elements
     *
     * @param   string  $class    Classes to add to the element
     * @param   string  $element  Element to add the class to (bg,fg,wrapper)
     *
     * @return $this
     */
    public function setClass($class, $element)
    {
        if(is_array($class))
        {
            $class = implode(' ', $class);
        }

        if (!in_array($element, array('bg', 'fg', 'wrapper')))
        {
            return $this;
        }

        $paramName = strtolower($element) .'Class';


        $this->{$paramName} = $class;

        return $this;
    }

	/**
	 * Method to render the tab set
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		$document = \JFactory::getDocument();
		$document->addStylesheet(\JUri::root(true) . '/media/logical/css/rating.css', 'text/css');

	    $style = 'width:' . \Logical\Utility\Math::getPercentage($this->value, $this->scaleSize) . '%';

		return $this->widgetRenderer->render('logical.ratings.bar', array('class'=> $this->wrapperClass, 'bg-class' => $this->bgClass, 'fg-class' => $this->fgClass, 'style' => $style));
	}
}
