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
 * Class CsvView
 *
 * @package  Logical\View
 * @since    0.0.189
 */
class CsvView extends RecordView
{
	/**
	 * Constructor
	 * prepares the base form URL from the given config
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->layout = 'csv_' . $this->layout;
	}

	/**
	 * Method to get the csv headers from the table fields
	 *
	 * @param   array  $ignore  array of fields to ignore
	 *
	 * @return array
	 */
	protected function getHeaders($ignore = array())
	{
		/** @var DataModel $model */
		$model = $this->getModel();

		return $model->getFields($ignore);
	}

	/**
	 * Method to clean an array of items to insure it processes properly for CSV format
	 *
	 * @param   array  $item            to clean
	 * @param   array  $includedFields  fields to include in the CSV
	 *
	 * @return array
	 */
	protected function cleanCSV($item = array(), $includedFields = array())
	{
		foreach ($item as $name => $property)
		{
			if (!in_array($name, $includedFields))
			{
				unset($item[$name]);

				continue;
			}

			$item[$name] = '"' . str_replace('"', '&quot;', str_replace(array("\n", "\r", "\r\n", "\n\r"), '', trim($property))) . '"';
		}

		return $item;
	}
}
