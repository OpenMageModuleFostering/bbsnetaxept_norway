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
    protected function getNets()
    {
        return Mage::getSingleton('bbsnetaxept/withGUI');
    }

    /**
     * When a customer chooses BBS on Checkout/Payment page
     *
     */
    public function redirectAction()
    {
        $nets = $this->getNets();
        $nets->getCheckout()->setNetsQuoteId($nets->getCheckout()->getQuoteId());

        $htmlBlock = $this->
            getLayout()->
            createBlock('bbsnetaxept/redirect')->
            setNetsUrl(Mage::getUrl('checkout/cart', array('_secure'=>true)))->
            setNetsMessage($this->__('We are bringing you back to you cart.'));

        if ($nets->getCheckout()->getNetsAllowRedirect()) {
            $nets->getCheckout()->setNetsAllowRedirect(false);

            $_transKey = $nets->getBBSTransKey();

            if ($_transKey) {
              $htmlBlock->setNetsUrl($nets->getBBSUrl().'?merchantId='.$nets->getMerchantId().'&transactionId='.$_transKey);
              $htmlBlock->setNetsMessage($this->__('You will be redirected to Nets NetAxept in a few seconds.'));
            }
        }
        else {
            $nets->cancelledByUser();
        }


        $this->getResponse()->setBody($htmlBlock->toHtml());
        return $this;
    }

    /**
     * When a customer returns from BBS Checkout page.
     */
    public function checkAction()
    {
        $redirect = 'checkout/cart';
        $this->getNets()->getCheckout()->setQuoteId($this->getNets()->getCheckout()->getNetsQuoteId(true));


        if ($this->getRequest()->getQuery('responseCode','Fail') == 'OK')
        {
          if ($this->getNets()->checkResult($this->getRequest()->getQuery('transactionId')))
          {
            $redirect = 'checkout/onepage/success';
          }
        } else {
          $this->getNets()->cancelledByUser();
        }
        $this->_redirect($redirect, array('_secure'=>true));
    }
}
