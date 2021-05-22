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

use ErrorException;


/**
 * Class Display
 *
 * @package  Logical\Controller
 *
 * @since    0.0.1
 */
class RenderController extends DisplayController
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
	 * Display $this->view.
	 *
	 * If this->view is not set, load it and set the default model.
	 *
	 * @return bool
	 *
	 * @throws ErrorException
	 */
	public function execute()
	{
		parent::execute();

		if($this->getConfig()->viewType == 'html')
        {
            $scripts = $this->postRender();
            echo $scripts;
        }
		echo $this->output;

		$this->getApplication()->close();
	}

	public function postRender()
    {
        /** @var \JDocumentHtml $document */
        $document = \JFactory::getDocument();

        if(!method_exists($document, 'getHeadData'))
        {
            return '';
        }

        $documentHeader = $document->getHeadData();

        if (empty($documentHeader['scriptOptions']))
        {
            return '';
        }

        $script =  '<script type="text/javascript">';
        $jsonOptions = json_encode($documentHeader['scriptOptions']);
        $jsonOptions = $jsonOptions ? $jsonOptions : '{}';

        $config = $this->getConfig();
        $script .= 'window.'. $config->view .'_'. $config->layout .' = ' .$jsonOptions .';';
        $script .= '</script>';

        if (empty($documentHeader['scripts']))
        {
            return $script;
        }


        $mediaVersion = $document->getMediaVersion();

        foreach ($documentHeader['scripts'] AS $src => $attribs)
        {
            // Check if script uses IE conditional statements.
            $conditional = (!empty($attribs['options']['conditional'])) ? $attribs['options']['conditional'] : null;

            // Check if script uses media version.
            if (isset($attribs['options']['version'])
                && $attribs['options']['version']
                && strpos($src, '?') === false
                && ($mediaVersion || $attribs['options']['version'] !== 'auto'))
            {
                $src .= '?' . ($attribs['options']['version'] === 'auto' ? $mediaVersion : $attribs['options']['version']);
            }

            // This is for IE conditional statements support.
            if (!is_null($conditional))
            {
                $script .= '<!--[if ' . $conditional . ']>';
            }

            $script .= '<script src="' . $src . '"';

            // Add script tag attributes.
            foreach ($attribs as $attrib => $value)
            {
                // Don't add the 'options' attribute. This attribute is for internal use (version, conditional, etc).
                if ($attrib === 'options')
                {
                    continue;
                }

                // Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
                if (in_array($attrib, array('type', 'mime')) && $document->isHtml5() && in_array($value, $defaultJsMimes))
                {
                    continue;
                }

                // B/C: If defer and async is false or empty don't render the attribute.
                if (in_array($attrib, array('defer', 'async')) && !$value)
                {
                    continue;
                }

                // Don't add type attribute if document is HTML5 and it's a default mime type. 'mime' is for B/C.
                if ($attrib === 'mime')
                {
                    $attrib = 'type';
                }
                // B/C defer and async can be set to yes when using the old method.
                elseif (in_array($attrib, array('defer', 'async')) && $value === true)
                {
                    $value = $attrib;
                }

                // Add attribute to script tag output.
                $script .= ' ' . htmlspecialchars($attrib, ENT_COMPAT, 'UTF-8');

                if (!($document->isHtml5() && in_array($attrib, array('defer', 'async'))))
                {
                    // Json encode value if it's an array.
                    $value = !is_scalar($value) ? json_encode($value) : $value;

                    $script .= '="' . htmlspecialchars($value, ENT_COMPAT, 'UTF-8') . '"';
                }
            }

            $script .= '></script>';

            // This is for IE conditional statements support.
            if (!is_null($conditional))
            {
                $script .= '<![endif]-->';
            }

        }

        if(empty($documentHeader['script']))
        {
            return $script;
        }

        // Generate script declarations
        foreach ($documentHeader['script'] as $type => $content)
        {
            $script .=  '<script';

            if (!is_null($type) && (!$document->isHtml5() || !in_array($type, $defaultJsMimes)))
            {
                $script .= ' type="' . $type . '"';
            }

            $script .= '>';

            // This is for full XHTML support.
            if ($document->_mime != 'text/html')
            {
                $script .= '//<![CDATA[';
            }

            $script .= $content ;

            // See above note
            if ($document->_mime != 'text/html')
            {
                $script .= '//]]>';
            }

            $script .= '</script>';
        }

        return $script;
    }
}
