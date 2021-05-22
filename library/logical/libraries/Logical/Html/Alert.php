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
class Alert extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var string
	 */
	protected $header;

	/**
	 * @var array
	 */
	protected $messages;

	/**
	 * @var bool
	 */
	protected $hasClose = false;

	/**
	 * @var string
	 */
	protected $class;

	/**
	 * @var string
	 */
	protected $closeClass;

    public function __construct(WidgetRendererInterface $widgetRenderer, $type = 'message', $header = '', $messages = array(), $hasClose = false)
    {
        parent::__construct($widgetRenderer);

        $this->type = $type;
        $this->header = $header;
        $this->messages = $messages;
        $this->hasClose = $hasClose;
    }

    public function setAlertClass($class)
    {
    	$this->class = $class;
    }

    public function setCloseClass($class)
    {
    	$this->closeClass = $class;
    }

	/**
	 * Method to render the tab set
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		$html = array();

		if ($this->hasClose)
		{
			$html[] = $this->widgetRenderer->render('logical.alert.close', array('class' => $this->closeClass));
		}

		$html[] = $this->header;
		$html[] = '<ul>';

		foreach ($this->messages AS $message)
		{
			if (empty($message))
			{
				continue;
			}

			$html[] = '<li>' . $message . '</li>';
		}

		$html[] = '</ul>';

		return $this->widgetRenderer->render('logical.alert.'.$this->type, array('class'=> $this->class, 'innerHtml' => implode("\n", $html)));
	}
}
