<?php
/**
 * BBS NetAxept, Norge
 *
 * LICENSE AND USAGE INFORMATION
 * It is NOT allowed to modify, copy or re-sell this file or any 
 * part of it. Please contact us by email at post@trollweb.no or 
 * visit us at www.trollweb.no/bbs if you have any questions about this.
 * Trollweb is not responsible for any problems caused by this file.
 *
 * Visit us at http://www.trollweb.no today!
 * 
 * @category   Trollweb
 * @package    Trollweb_BBSNetAxept
 * @copyright  Copyright (c) 2009 Trollweb (http://www.trollweb.no)
 * @license    Single-site License
 * 
 */

class Trollweb_BBSNetAxept_Block_Redirect extends Mage_Core_Block_Abstract
{
    protected function _toHtml()
    {

        $url = $this->getNetsUrl();

        $html = '<html>';
        $html .= '<head>';
        $html .= '<title>'.$this->getNetsMessage().'</title>';
        $html .= '</head>';
        $html .= '<body>';
        $html .= $this->getNetsMessage().'<br />';
        $html .= '<a href="'.$url.'">'.$this->__('Click here if you are not redirected within 10 seconds.').'</a>';
        $html .= '<script type="text/javascript">';
        $html .= '  setTimeout(function(){ window.location="'.$url.'"; },100);';
        $html .= '</script>';
        $html .= '</body>';
        $html .= '</html>';

        return $html;
    }
}