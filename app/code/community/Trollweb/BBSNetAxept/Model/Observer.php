<?php

class Trollweb_BBSNetAxept_Model_Observer
{
  
  public function voidPayment($observer)
  {

    $payment = $observer->getEvent()->getPayment();
    if ($payment->getMethod() == "bbsnetaxept_withgui") {
      $methodInstance = $payment->getMethodInstance();
      $methodInstance->void($payment);
    }

  }
  
    public function core_config_data_save_after($observer) {
        $configData = $observer->getEvent()->getConfigData();
        
        if ($configData && $configData->getPath() == 'payment/bbsnetaxept_withgui/active' && $configData->getValue() == 1) {
            Mage::register('bbsnetaxept_licens_status', true);
        }
        
        if ($configData && $configData->getPath() == 'payment/bbsnetaxept_withgui/test_mode' && $configData->getValue() == 0) {
            Mage::register('bbsnetaxept_licens_mode', true);
        }
        
        if ($configData && $configData->getPath() == 'payment/bbsnetaxept_withgui/merchant_id' && $configData->getValue() != '') {
            Mage::register('bbsnetaxept_licens_merchant', true);
        }
        
        if (Mage::registry('bbsnetaxept_licens_status') AND Mage::registry('bbsnetaxept_licens_mode') AND Mage::registry('bbsnetaxept_licens_merchant')) {
            if (Mage::registry('bbsnetaxept_licens_updated')) {
                return $this;
            }
            else {
                Mage::register('bbsnetaxept_licens_updated', true);
            }
            Mage::getModel('bbsnetaxept/withGUI')->saveConfigData();
        }
        
        return $this;
    }

}