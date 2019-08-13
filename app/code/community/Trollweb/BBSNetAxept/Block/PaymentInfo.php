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

class Trollweb_BBSNetAxept_Block_PaymentInfo extends Mage_Payment_Block_Info
{
    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('bbsnetaxept/paymentinfo.phtml');
    }
	
    protected function getLogo()
    {
      return $this->getMethod()->getLogoUrl();
    }
	
    public function isMobileDevice()
    {
        return $this->getInfo()->getAdditionalInformation(Trollweb_BBSNetAxept_Model_WithGUI::MOBILE_CLIENT);
    }

    protected function _prepareSpecificInformation($transport = null)
    {
        $transport = parent::_prepareSpecificInformation($transport);
        $payment = $this->getInfo();
        $bbsInfo = Mage::getModel('bbsnetaxept/info');
        if (!$this->getIsSecureMode()) {
        	$info = $bbsInfo->getPaymentInfo($payment);
        } else {
          $info = $bbsInfo->getPublicPaymentInfo($payment);
        }
        return $transport->addData($info);
    }
	
}