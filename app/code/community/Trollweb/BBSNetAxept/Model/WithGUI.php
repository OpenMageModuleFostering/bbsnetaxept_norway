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


/**
* Our test CC module adapter
*/
class Trollweb_BBSNetAxept_Model_WithGUI extends Mage_Payment_Model_Method_Abstract
{
    protected $_code = 'bbsnetaxept_withgui';
    protected $_formBlockType = 'bbsnetaxept/form';
    protected $_infoBlockType = 'bbsnetaxept/paymentInfo';
    protected $_allowCurrencyCode = array('NOK', 'SEK', 'USD', 'EUR');
    

    //* Options *//
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = false;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    
    // PROD URL
    const WSDL_URL_TEST  = 'https://epayment-test.bbs.no/service.svc?wsdl';
    const QWSDL_URL_TEST = 'https://epayment-test.bbs.no/TokenQuery.svc?wsdl';
    const GW_URL_TEST    = 'https://epayment-test.bbs.no/terminal/default.aspx';

    // TEST URL
    const WSDL_URL_PROD  = 'https://epayment.bbs.no/service.svc?wsdl';
    const QWSDL_URL_PROD = 'https://epayment.bbs.no/TokenQuery.svc?wsdl';
    const GW_URL_PROD    = 'https://epayment.bbs.no/terminal/default.aspx';
    
    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
            
    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Mage_Payment_Model_Info
     */
    public function assignData($data)
    {
        if (!($data instanceof Varien_Object)) {
            $data = new Varien_Object($data);
        }
               
        $info = $this->getInfoInstance();
        $info->setCcType($data->getCcType())
            ->setCcOwner($data->getCcOwner())
            ->setCcLast4(substr($data->getCcNumber(), -4))
            ->setCcNumber($data->getCcNumber())
            ->setCcCid($data->getCcCid())
            ->setCcExpMonth($data->getCcExpMonth())
            ->setCcExpYear($data->getCcExpYear());        
        return $this;
    }
    
    
    public function isAvailable($quote=null)
    {
        if ($this->getConfigData('use_gui') == 1) {
          $this->_formBlockType = 'bbsnetaxept/form';
          $this->_infoBlockType = 'bbsnetaxept/paymentInfo';
          
        }
        else {
          $this->_formBlockType = 'payment/form_cc';
          $this->_infoBlockType = 'payment/info_cc';
          
        }
        return true;
    }
    

    /**
     * Using internal pages for input payment data
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return $this->_canUseInternal;
    }

    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping()
    {
        return $this->_canUseForMultishipping;
    }    
        
    /*validate the currency code is avaialable to use for paypal or not*/
    public function validate()
    {
        parent::validate();
        $info = $this->getInfoInstance();
        if ($info instanceof Mage_Sales_Model_Order_Payment) {
          $currency_code = $info->getOrder()->getBaseCurrencyCode();
        } else {
          $currency_code = $info->getQuote()->getBaseCurrencyCode();
        }
        
        if (!in_array($currency_code,$this->_allowCurrencyCode)) {
            Mage::throwException(Mage::helper('bbsnetaxept')->__('Selected currency code ('.$currency_code.') is not compatible with BBS NetAxept'));
        }
        elseif (!$this->getApi()->setRegCode($this->getConfigData('regcode'))->validate()) {
           Mage::throwException(Mage::helper('bbsnetaxept')->__('This is an unregisted version of BBS NetAxept. Please go to your admin page in Magento and aquire your free registration code. See www.trollweb.no/bbs for details'));
        }
        
        return $this;
    }

	public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
	{
	    return $this;
	}

	public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
	{
	    
	}
	
	public function getBBSTransKey()
	{
		
    $order = Mage::getModel('sales/order');
    $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
    if ($order->getPayment()->getStatus() != self::STATUS_APPROVED) {
		
	     $this->getCheckout()->setBBSTransactionId(uniqid());
	     $order->getPayment()->setBbsTransactionId($this->getCheckout()->getBBSTransactionId());     
	     
       $transKey = $this->getApi()->
                          setCurrencyCode($this->getQuote()->getStoreCurrencyCode())->
                          setTransactionId($this->getCheckout()->getBBSTransactionId())->
                          setAmount(sprintf("%0.0f",$this->getQuote()->getGrandTotal()*100))->
                          setOrderNumber($this->getCheckout()->getLastRealOrderId())->
                          setOrderDescription(date("d.m.Y")." - Order ".$this->getCheckout()->getLastRealOrderId())->
                          setCustomerEmail($this->getQuote()->getCustomerEmail())->
                          setCustomerPhoneNumber($this->getQuote()->getBillingAddress()->getTelephone())->
                          setSessionId($this->getCheckout()->getQuoteId())->
                          setInternalGUI($this->useInternalGUI())->
                          getTransKey();
	     	                          
	    if ($transKey == false) {
	      Mage::throwException(Mage::helper('bbsnetaxept')->__('Error receiving key from BBS: '.$this->getApi()->getErrorMessage()));
	    }
	    else {
	      $this->getCheckout()->setBBSTransKey($transKey);
	      if ($this->useInternalGUI()) {
	        $info = $this->getInfoInstance();
	        if (!($info instanceof Varien_Object)) {
	          $info = new Varien_Object($info);
	        }
	        $this->getCheckout()->setCardInfo($info);
	      }
		    $order->addStatusToHistory('pending_bbs',Mage::helper('bbsnetaxept')->__('Redirected to BBS Payment'),false);
		    $order->save();
	    }
	  }
    
    return $this->getCheckout()->getBBSTransKey();
	}

	public function getOrderPlaceRedirectUrl()
	{
	  return Mage::getUrl('bbsnetaxept/return/redirect', array('_secure' => true));
	}

	public function getBBSUrl()
	{
		if ($this->getConfigData('test_mode')) {
      return Trollweb_BBSNetAxept_Model_WithGUI::GW_URL_TEST;			
		}
		else {
      return Trollweb_BBSNetAxept_Model_WithGUI::GW_URL_PROD;			
		}
	}
	
	public function getPendingTimeout()
	{
		return $this->getConfigData('pending_minutes');
	}
	
	public function useInternalGUI()
	{
	  return ($this->getConfigData('use_gui') ? false : true);
	}
	
	public function getCCDate()
	{
	  $info = $this->getInfoInstance();
	  return $info;
	}
	
    /**
     * Get BBS API Model
     *
     * @return Trollweb_BBSNetAxept_Model_Api_Bbs
     */
    public function getApi()
    {
        $bbsClient = Mage::getSingleton('bbsnetaxept/api_bbs');

        // Merchant ID
        $bbsClient->setMerchantId($this->getConfigData('merchant_id'))->setLanguage($this->getConfigData('gui_language'));
        if ($this->getConfigData('test_mode')) {
        	$bbsClient->
              setMerchantToken($this->getConfigData('merchant_test_token'))->
              setWsdlUrl(Trollweb_BBSNetAxept_Model_WithGUI::WSDL_URL_TEST)->
              setQueryWsdlUrl(Trollweb_BBSNetAxept_Model_WithGUI::QWSDL_URL_TEST);
        }
        else {
          $bbsClient->
              setMerchantToken($this->getConfigData('merchant_token'))->
        	    setWsdlUrl(Trollweb_BBSNetAxept_Model_WithGUI::WSDL_URL_PROD)->
              setQueryWsdlUrl(Trollweb_BBSNetAxept_Model_WithGUI::QWSDL_URL_PROD);
        }
        return $bbsClient;
    }
	
    /**
     * Check the result from the BBS NetAxept
     *
     * @param   none
     * @return  bool
     */
    public function checkResult($bbskey) {
        $isOK = false;

        // Load order.
		    $order = Mage::getModel('sales/order');
		    $order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        
        if ($this->getApi()->Process($bbskey) == $this->getCheckout()->getBBSTransactionId()) {
          
          if ($this->getApi()->Auth($this->getApi()->Result()->getTransactionId()) == $this->getCheckout()->getBBSTransactionId()) {

            
	          $this->getCheckout()->getQuote()->setIsActive(false)->save();

			      $order->getPayment()->setBbsTransactionId($this->getCheckout()->getBBSTransactionId())->
			                            setBbsAuthenticatedStatus($this->getApi()->Result()->getAuthenticatedStatus())->
			                            setBbsAuthenticatedWith($this->getApi()->Result()->getAuthenticatedWith())->
			                            setBbsIssuerCountry($this->getApi()->Result()->getIssuerCountry())->
			                            setBbsIssuerId($this->getApi()->Result()->getIssuerId())->
			                            setBbsAuthorizationId($this->getApi()->Result()->getAuthorizationId())->
			                            setBbsSessionNumber($this->getApi()->Result()->getSessionNumber());

            if ($this->getApi()->Result()->getResponseCode() == "OK") {
              $order->getPayment()->setStatus(self::STATUS_APPROVED);
              //Set new orderstatus
              $newOrderStatus = $this->getConfigData('auth_order_status');
              if (empty($newOrderStatus)) {
                $newOrderStatus = $order->getStatus();
              }
              $order->addStatusToHistory($newOrderStatus,'BBS Authorization successful',true);
              
		          /**
		           * send confirmation email to customer
		           */
		          if($order->getId()){
		              $order->sendNewOrderEmail();
		          }
              
              $isOK = true;
            }
    	        
              
            $order->save();	    
            if ($this->getConfigData('payment_action') == 'sale') {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();
            }      
	      }
	      else {
	        $order->getPayment()->setBbsAuthenticatedStatus('Error')->setBbsAuthenticatedWith($this->getApi()->getErrorMessage());
	        $order->cancel()->save();
	        $this->getCheckout()->addError($this->getErrorMessage($this->getApi()->getErrorCode()).' ('.$this->getApi()->getErrorCode().') (auth)');          
	      }
        }
        else {
	        $order->getPayment()->setBbsAuthenticatedStatus('Error')->setBbsAuthenticatedWith($this->getApi()->getErrorMessage());
          $order->cancel()->save();
          $this->getCheckout()->addError($this->getErrorMessage($this->getApi()->getErrorCode()).' ('.$this->getApi()->getErrorCode().') (proc)');
        }


        return $isOK;
    }
    
    
    public function capture(Varien_Object $payment, $amount) {
       $error = false;

        if (!$payment->getBbsTransactionId()) {
          Mage::throwException(Mage::helper('bbsnetaxept')->__('Could not find transaction id.'));
        }

        $order = $payment->getOrder();

        $InvoiceId = ($order->getIncrementId() ? $order->getIncrementId() : 'Unknown');
        
        $bbs_amount = sprintf("%0.0f",$amount*100);
        if ($this->getApi()->capture($payment->getBbsTransactionId(),$bbs_amount,$InvoiceId) == $payment->getBbsTransactionId()) {
            $payment->setStatus(self::STATUS_APPROVED);
        }
        else {
          $error = Mage::helper('bbsnetaxept')->__('Error capturing the payment: %s', $this->getApi()->getErrorMessage());
        }
        
        if ($error !== false) {
            Mage::throwException($error);
        }

        return $this;    
    }
    
   /**
     * refund the amount with transaction id
     *
     * @access public
     * @param string $payment Varien_Object object
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {
       $error = false;

        if (!$payment->getBbsTransactionId()) {
          Mage::throwException(Mage::helper('bbsnetaxept')->__('Could not find transaction id.'));
        }


        $order = $payment->getOrder();        
        $InvoiceId = ($order->getIncrementId() ? $order->getIncrementId() : 'Unknown');
        
        $bbs_amount = sprintf("%0.0f",$amount*100);
        if ($this->getApi()->refund($payment->getBbsTransactionId(),$bbs_amount, $InvoiceId) == $payment->getBbsTransactionId()) {
             $payment->setStatus(self::STATUS_SUCCESS);
          
        }
        else {
          $error = Mage::helper('bbsnetaxept')->__('Error refunding the payment: %s', $this->getApi()->getErrorMessage());
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        return $this;
    }
    
    public function getLogoMethods() {
         $codes = array(0 => Mage::helper('bbsnetaxept')->__('Ingen logo'),
//                        1 => Mage::helper('bbsnetaxept')->__('BBS logo'),
                        2 => Mage::helper('bbsnetaxept')->__('BBS Technology logo')
                       );
 	       return $codes;
    }
    
    public function getLogoUrl() {
    	$logotype = $this->getConfigData('logo');
    	switch($logotype) {
    		case 1: 
    			$url = 'images/bbsnetaxept/logo.png';
    		  break;
    		case 2:
    			$url = 'images/bbsnetaxept/technology_logo.png';
    			break;
    		case 0:
    		default:
    			$url = '';
    			break;
    	}
    	return $url;
    }
    
    public function getRedirectText() {
    	return $this->getConfigData('redirect_text');
    }
    
    
    private function getErrorMessage($errorcode=99) {
      switch ($errorcode) {
        case '00': 
                    $message = '';
                    break;
        case '17':
                    $message = Mage::helper('bbsnetaxept')->__('Payment cancelled by user.'); 
                    break;
        case '99':
        default:
                    $message = Mage::helper('bbsnetaxept')->__('Error processing transaction from BBS. Try again or contact your bank'); 
                    break;
      }
      
      return $message;
    }

	  private function dolog($logline)
	  {
	    $logDir = Mage::getBaseDir('log');
	    $fh = fopen($logDir."/bbsnexaxept.log","a");
	    if ($fh) {
	      fwrite($fh,"[".date("d.m.Y h:i:s")."] ".$logline."\n");
	      fclose($fh);
	    }
	  }
    
    
}
