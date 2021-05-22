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

class Carousel extends Base
{
	/**
	 * @var WidgetRendererInterface
	 */
	protected $widgetRenderer;

	/**
	 * @var string the carousel id
	 */
	protected $id;

	/**
	 * @var array Items to be rendered
	 */
	protected $items = array();

	/**
	 * @var int|string The number amount of time to delay between automatically cycling an item. If "false", carousel will not automatically cycle.
	 */
	protected $interval = null;

	/**
	 * @var string Pauses the cycling of the carousel on mouseenter and resumes the cycling of the carousel on mouseleave.
	 */
	protected $pause = null;

	/**
	 * @var bool
	 */
	protected $hasControls = true;

	/**
	 * @var bool
	 */
	protected $hasIndicators = true;

	/**
	 * Constructor.
	 *
	 * @param   WidgetRendererInterface  $widgetRenderer  widget render
	 * @param   string                   $id              carousel id
	 * @param   string|int               $interval        The number amount of time to delay between automatically cycling an item
	 * @param   string                   $pause           Pauses the cycling of the carousel on
	 */
	public function __construct(WidgetRendererInterface $widgetRenderer, $id, $interval = 'false', $pause = null)
	{
		parent::__construct($widgetRenderer);

		$this->id = (string) $id;
		$this->interval = $interval;
		$this->pause = $pause;
	}

	public function toggleControls()
	{
		$this->hasControls = ($this->hasControls != true);

		return $this;
	}

	public function toggleIndicators()
	{
		$this->hasIndicators = ($this->hasIndicators != true);

		return $this;
	}

	/**
	 * Method to add an carousel item
	 *
	 * @param   string  $image         image src value
	 * @param   string  $caption       content of the caption
	 * @param   string  $captionClass  classes to add to the caption element
	 * @return $this
	 */
	public function addItem($image, $caption = null, $captionClass = '')
	{
		$this->items[] = array(
			'src' => $image,
			'caption' => array('content' =>$caption, 'class' => $captionClass),
			'target' => '#' . $this->id);

		return $this;
	}

	/**
	 * Method to render the carousel
	 *
	 * @return \Logical\Widget\WidgetElement
	 */
	public function render()
	{
		if (empty($this->items))
		{
			return null;
		}

		$this->items[0]['class'] = 'active';

		$content = new \Logical\Widget\WidgetElement('widget');

		foreach ($this->items AS $index => &$item)
		{
			$item['slide-to'] = $index;

			if (!empty($item['caption']['content']))
			{
				$item['caption'] = $this->widgetRenderer
					->render('logical.html.carousel.caption', $item['caption']);
			}
		}

		$content->addInnerHtml($this->widgetRenderer->render(
			'logical.html.carousel.items',
			array(
				'items' => $this->items,
			))
		);

		if ($this->hasControls)
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.html.carousel.controls',
					array(
						'href' => '#'. $this->id
					)
				)
			);
		}

		if ($this->hasIndicators)
		{
			$content->addInnerHtml(
				$this->widgetRenderer->render(
					'logical.html.carousel.indicators',
					array(
						'items' => $this->items
					)
				),
				true
			);
		}

		return $this->widgetRenderer->render(
			'logical.html.carousel',
			array(
				'id' => $this->id,
				'content' => $content
			)
		);
	}
}
