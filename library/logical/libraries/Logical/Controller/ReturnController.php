<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\Controller;

// No direct access
defined('_JEXEC') or die;

use Logical\View\DataView;
use Logical\Registry\Registry;

use ErrorException;
use Joomla\Application\AbstractApplication;
use JFactory;
use JInput;
use JText;
use JUri;
use Spipu\Html2Pdf\Html2Pdf;

/**
 * Class Return controller
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class ReturnController extends DisplayController
{
	/**
	 * View to display
	 *
	 * @var mixed view object
	 *
	 * @since 0.0.1
	 */
	protected $view;

	/**
	 * Output buffer content from the views.
	 * @var
	 */
	public $output;

	/**
	 * Should we echo the output or not?
	 * @var bool
	 */
	public $echoOutput = false;

	/**
	 * This controller is used to render a view and return the result to the caller
	 * intended use case is when rendering a non HTML view like PDF or Docx view
     *
	 * @return mixed
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		parent::execute();

		return $this->output;
	}
}
