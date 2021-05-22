<?php
/**
 * @author     Mathew Lenning <mathew.lenning@gmail.com>
 * @authorUrl  http://mathewlenning.com
 * @copyright  Copyright (C) 2015 Mathew Lenning. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

namespace Logical\View;

// No direct access
use Logical\Model\DataModel;

defined('_JEXEC') or die;

/**
 * Class JsonView
 */
class JsonView extends RecordView
{
    protected $documentTitle = '';

	/**
	 * Constructor
	 * prepares the base form URL from the given config
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
    {
        parent::__construct($config);

		$this->layout = 'json_' . $this->layout;

		switch ($this->layout)
        {
            case 'json_default':
                $this->addListLayout($this->layout);
                break;
            case 'json_item':
            case 'json_details':
            case 'json_form':
                $this->addItemLayout($this->layout);
                break;
        }
	}
}
