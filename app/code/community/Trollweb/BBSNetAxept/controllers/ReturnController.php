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

class Trollweb_BBSNetAxept_ReturnController extends Mage_Core_Controller_Front_Action
{

    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * Get singleton with bbs strandard order transaction information
     *
     * @return Trollweb_BBSNetAxept_Model_WithGUI
     */
    public function getStandard()
    {
        return Mage::getSingleton('BBSNetAxept/WithGUI');
    }

    /**
     * When a customer chooses BBS on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $session = Mage::getSingleton('checkout/session');
        $session->setBBSNetterminalStandardQuoteId($session->getQuoteId());
        $this->getResponse()->setBody($this->getLayout()->createBlock('BBSNetAxept/withGUI_redirect')->toHtml());
    }

    /**
     * When a customer cancel payment from paypal.
     */
    public function checkAction()
    {
        $redirect = 'checkout/cart';
        
        $session = Mage::getSingleton('checkout/session');
        $session->setQuoteId($session->getBBSNetterminalStandardQuoteId(true));
        
        if ($this->getStandard()->checkResult($this->getRequest()->getQuery("BBSePay_transaction"))) {
	        $redirect = 'checkout/onepage/success';
        }
 
	    $this->_redirect($redirect, array('_secure'=>true));
    }
}
  