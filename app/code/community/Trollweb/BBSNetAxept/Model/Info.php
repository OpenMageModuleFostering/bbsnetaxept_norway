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

class Trollweb_BBSNetAxept_Model_Info
{
    protected $_publicMap = array();

    protected $_secureMap = array('transaction_id',
                                  'authorization_id',
                                  'authenticated_status',
                                  'authenticated_with',
                                  'issuer_id',
                                  'issuer_country',
                                  'giftcard_amount',                                  
                                 );

  public function getPublicPaymentInfo($payment) {
    return $this->_makeMap($this->_publicMap,$payment);
  }

  public function getPaymentInfo($payment) {
    return $this->_makeMap($this->_secureMap,$payment);
  }

  protected function _makeMap($map,$payment) {
    $result = array();
    foreach ($map as $key) {
        $value = $this->_getValue($key,$payment);
        if ($value !== false) {
            $result[$this->_getLabel($key)] = $this->_getValue($key,$payment);
        }
    }

    return $result;
  }

    protected function _getLabel($key) {
        switch ($key) {
            case 'transaction_id':
                return Mage::helper('bbsnetaxept')->__('Transaction id');
            case 'authorization_id':
                return Mage::helper('bbsnetaxept')->__('Authorization id');
            case 'authenticated_status':
                return Mage::helper('bbsnetaxept')->__('Authenticated status');
            case 'authenticated_with':
                return Mage::helper('bbsnetaxept')->__('Authenticated with');
            case 'issuer_id':
                return Mage::helper('bbsnetaxept')->__('Issuer');
            case 'issuer_country':
                return Mage::helper('bbsnetaxept')->__('Issuer country');
            case 'payment_method':
                return Mage::helper('bbsnetaxept')->__('Payment method');
            case 'giftcard_amount':
                return Mage::helper('bbsnetaxept')->__('Giftcard amount');

        }
    }

    protected function _getValue($key,$payment) {
        $value = $payment->getAdditionalInformation('bbs_'.$key);
        if ($value == null) {
            switch ($key) {
                case 'transaction_id':
                    $value = $payment->getBbsTransactionId();
                    break;
                case 'authorization_id':
                    $value = $payment->getBbsAuthorizationId();
                    break;
                case 'authenticated_status':
                    $value = $payment->getBbsAuthenticatedStatus();
                    break;
                case 'authenticated_with':
                    $value = $payment->getBbsAuthenticatedWith();
                    break;
                case 'issuer_id':
                    $value = $payment->getBbsIssuerId();
                    break;
                case 'issuer_country':
                    $value = $payment->getBbsIssuerCountry();
                    break;
            }

            if ($key == "issuer_id") {
              $additional = $this->_getValue('issuer', $payment);
              if ($additional) {
                $value .= ' '.$additional;
              }
            }

            if (!$value) {
                $value = '';
            }
        }

        if ($key == "giftcard_amount") {
            if ($payment->getAdditionalInformation(Trollweb_BBSNetAxept_Model_WithGUI::IS_GIFTCARD)) {
                $value = number_format($value/100,2);
            }
            else {
                $value = false;
            }
        }

        return $value;
    }

}