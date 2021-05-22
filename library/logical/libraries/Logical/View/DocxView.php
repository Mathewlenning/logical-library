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
 * Class DocxView
 */
class DocxView extends RecordView
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

		$this->layout = 'docx_' . $this->layout;
	}

    /**
     * Method to render a template script and return the output.
     *
     * @param   string  $tpl  The name of the template file to parse. Automatically searches through the template paths.
     *
     * @throws ErrorException
     * @throws Exception
     *
     * @return mixed $output A string
     */
    public function render($tpl = null)
    {
        $renderedOutput = parent::render($tpl);

        $output = array();
        $output[] = '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">';

        // Start Header content
        $output[] = "<head>";
        if(!empty($this->documentTitle))
        {
            $output[] = "<title>$this->documentTitle</title>";
        }

        // Styles
        $styles = $this->getStyles();

        if(!empty($styles))
        {
            $output[] = implode("\n", $styles);
        }

        $output[] = "</head>";

        //Body
        $output[] = '<body>';
        $output[] ='<div clas="Section1">' . $renderedOutput . '</div>';
        $output[] = '</body>';
        $output[] = '</html>';

        return implode("\n", $output);
    }

    /**
     * Method to get an array of css styles
     *
     * @return array
     */
    protected function getStyles()
    {
        $output = array();
        $output[] = "<style type='text/css'>";
        $output[] = "<!-- /* Style Definitions */";
        $output[] = " @page Section1 {size:8.5in 11.0in;  margin:1.0in 1.25in 1.0in 1.25in;  mso-header-margin:.5in; mso-footer-margin:.5in; mso-paper-source:0;}";
        $output[] = "div.Section1 {page:Section1;}";
        $output[] = $this->getTemplateStyles();

        $output[] = "-->";
        $output[] = "</style>";

        return $output;
    }

    protected function getTemplateStyles()
    {
        $app           = \JFactory::getApplication();
        $template_path = JPATH_THEMES . '/' . $app->getTemplate() . '/css';

        $files = scandir($template_path);

        $css = array();
        foreach ($files AS $file)
        {
            if(is_dir($file))
            {
                continue;
            }

            if(strpos($file, '.rtl.css') !== false)
            {
                continue;
            }

            $css[] = file_get_contents($template_path.'/' . $file);

        }

        return implode("\n", $css);
    }
}
