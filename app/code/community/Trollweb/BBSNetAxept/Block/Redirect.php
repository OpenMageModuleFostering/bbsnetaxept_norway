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

        $standard = Mage::getModel('bbsnetaxept/withGUI');
        
        $bbsTransKey = $standard->getBBSTransKey();
        
        $form = new Varien_Data_Form();
        $form->setAction($standard->getBBSUrl())
            ->setId('BBS_WithGUI_checkout')
            ->setName('BBS_WithGUI_checkout')
            ->setMethod('POST')
            ->setUseContainer(true);

            
        if ($standard->useInternalGUI()) {
          $ccInfo = $standard->getCheckout()->getCardInfo();
          if (!($ccInfo instanceof Varien_Object)) {
            $ccInfo = new  Varien_Object($ccInfo);
          }
          
          switch ($ccInfo->getCcType()) {
            case 'VI': $prefix = 'v'; break;
            case 'MC': $prefix = 'm'; break;
            case 'AE': $prefix = 'a'; break;
            case 'DI': $prefix = 'd'; break;
            default:   $prefix = 'v'; break;
          }
          
          $form->addField($prefix.'a','hidden', array('name' => $prefix.'a', "value" => $ccInfo->getCcNumber()));
          $form->addField($prefix.'m','hidden', array('name' => $prefix.'m', "value" => sprintf("%02u",(int)$ccInfo->getCcExpMonth())));
          $form->addField($prefix.'y','hidden', array('name' => $prefix.'y', "value" => substr($ccInfo->getCcExpYear(),-2)));
          $form->addField($prefix.'c','hidden', array('name' => $prefix.'c', "value" => $ccInfo->getCcCid()));          
        }

        $form->addField('BBSePay_transaction','hidden', array("name"=>'BBSePay_transaction', "value"=>$bbsTransKey));
        $html = '<html><body>';
        $html.= $this->__('You will be redirected to BBS NetAxept in a few seconds.');
        $html.= $form->toHtml();
//        $html.= '<script type="text/javascript">document.getElementById("BBS_WithGUI_checkout").submit();</script>';
        $html.= '</body></html>';

        return $html;
    }
}