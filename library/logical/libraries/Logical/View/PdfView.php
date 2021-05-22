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
use Spipu\Html2Pdf\Html2Pdf;

defined('_JEXEC') or die;

/**
 * Class PdfView
 */
class PdfView extends RecordView
{
    /**
     * @var string page orientation, can be P (portrait) or L (landscape)
     */
    protected $orientation = 'P';

    /**
     * @var string The default page format used for pages @see TCPDF_STATIC::$page_formats
     */
    protected $format = 'A4';

    /**
     * @var string Language to use see html2pdf/src/locale
     */
    protected $lang = 'en';

    /**
     * @var string means that the input HTML string is unicode
     */
    protected $unicode = 'true';

    /**
     * @var string charset encoding of the input HTML string
     */
    protected $encoding = 'UTF-8';

    /**
     * @var array Main margins of the page (left, top, right, bottom) in mm
     */
    protected $margins = array(5,5,5,8);

    /**
     * @var bool If TRUE set the document to PDF/A mode
     */
    protected $pdfa = false;

	/**
	 * Constructor
	 * prepares the base form URL from the given config
	 *
	 * @param   array  $config  configuration array
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		$this->layout = 'pdf_' . $this->layout;
	}

    /**
     * Method to render a template script and return the output.
     *
     * @param   string  $tpl  The name of the template file to parse. Automatically searches through the template paths.
     *
     * @throws ErrorException
     *
     * @throws Exception
     *
     * @return mixed $output A string
     */
    public function render($tpl = null)
    {
        $renderedOutput = parent::render($tpl);
        $pdf = new Html2Pdf($this->orientation,$this->format,$this->lang,$this->unicode,$this->encoding,$this->margins,$this->pdfa);
        $pdf->writeHTML($renderedOutput);

        return $pdf;
    }



    protected function getImgAsbase64($path)
    {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);

        return $base64;
    }
}
