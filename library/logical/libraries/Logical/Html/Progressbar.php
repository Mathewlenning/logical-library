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

use Logical\Widget\WidgetRendererInterface;

/**
 * Class Tabs
 *
 * @package  Logical\Html
 * @since    0.0.170
 */
class Progressbar extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

    /**
     * @var string progress bar wrapper CSS class name
     */
	protected $class;

	/**
	 * @var array of bar definitions
	 */
	protected $bars = array();

    /**
     * Constructor.
     *
     * @param   WidgetRendererInterface  $widgetRenderer  widget render
     */
    public function __construct(WidgetRendererInterface $widgetRenderer, $class = '', $bars = array())
    {
        parent::__construct($widgetRenderer);

        $this->class = $class;

        if (!empty($bars))
        {
            $this->bars = $bars;
        }
    }

	public function addBar($barClass = '', $style = '', $value = 0, $total = 0)
	{
	    if (empty($style))
        {
            $widthPercentage = \Logical\Utility\Math::getPercentage($value, $total);

            $style="width:" . $widthPercentage .'%;';
        }

		$this->bars[] = array('bar-class' => $barClass, 'style' => $style);
		return $this;
	}


	/**
	 * Method to render the tab set
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if (count($this->bars) <= 0)
		{
			return null;
		}

		return $this->widgetRenderer->render('logical.progress.bar', array('class'=> $this->class, 'bars' => $this->bars));
	}
}
