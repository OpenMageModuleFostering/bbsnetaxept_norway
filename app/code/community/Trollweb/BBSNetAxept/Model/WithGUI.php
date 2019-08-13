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


    //* Options *//
    protected $_isGateway               = true;
    protected $_canAuthorize            = true;
    protected $_canCapture              = true;
    protected $_canCapturePartial       = true;
    protected $_canRefund               = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid                 = false;
    protected $_canUseInternal          = true;
    protected $_canUseCheckout          = true;
    protected $_canUseForMultishipping  = false;
    protected $_canSaveCc               = false;
    protected $_isInitializeNeeded      = true;

    // Direct payment methods
    protected $_directPaymentMethods = array(
            'SwedishBankHandelsbanken',
            'SwedishBankNordea',
            'SwedishBankSwedbank',
            'SwedishBankSEB',
    );

    // Gift card methods
    protected $_giftCardMethods = array(
        'GiftCard',
        'GiftCardCenter'
    );

    // Internal Flags
    protected $_useMobile;

    // TEST HOST
    const TEST_HOST       = 'epayment-test.bbs.no';
    const PROD_HOST       = 'epayment.bbs.no';

    // Attributes
    const TRANSACTION_ID        = 'bbs_transaction_id';
    const AUTHENTICATED_STATUS  = 'bbs_authenticated_status';
    const AUTHENTICATED_WITH    = 'bbs_authenticated_with';
    const ISSUER_COUNTRY        = 'bbs_issuer_country';
    const ISSUER                = 'bbs_issuer';
    const ISSUER_ID             = 'bbs_issuer_id';
    const AUTHORIZATION_ID      = 'bbs_authorization_id';
    const SESSION_NUMBER        = 'bbs_session_number';
    const PAYMENTMETHOD         = 'bbs_payment_method';
    const MOBILE_CLIENT         = 'bbs_mobile_client';
    const IS_GIFTCARD           = 'bbs_is_giftcard';
    const GIFTCARD_AMOUNT       = 'bbs_giftcard_amount';
    const CHILD_TRANSACTION     = 'bbs_child_transaction';

    const ORDER_STATUS          = 'pending_nets';

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

        if ($this->getConfigData('mobile_active') && $data->getNetsMobile()) {
            $this->getCheckout()->setNetsMobile(true);
        }
        else {
            $this->getCheckout()->setNetsMobile(false);
        }
        return $this;
    }

    /**
     * Checks if the user's cart is under the limit set in
     * the admin config, the option isn't set or the value
     * of the option is set to ' ' or '0'.
     *
     * @param Mage_Sales_Model_Quote $quote Shopping cart quote
     */
    protected function cartOverLimit($quote) {
        $_maxAmountSet = $this->getConfigData('max_amount');
        $_maxAmountValue = $this->getConfigData('max_amount_value');

        // If setting disabled:
        if($_maxAmountSet == 0) {
                return false;
        }
        // If setting enabled and no value:
        else if($_maxAmountSet == 1 && empty($_maxAmountValue)) { // empty() is equivalent to !isset(var) || var == false
                return false;
        }
        // If setting enabled and value > cart value
        else if($quote->getBaseGrandTotal() < $_maxAmountValue) {
                return false;
        }
        
        return true; // Option enabled and cart over limit

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

        if($this->cartOverLimit($quote)) {
                return false;
        }
        return $this->getConfigData('active');
    }

    /**
     * Retrieve payment method title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->getConfigData('title');
    }

    /**
     * Method that will be executed instead of authorize or capture
     * if flag isInitilizeNeeded set to true
     *
     * @param   string $paymentAction
     * @param   Varien_Object $stateObject
     * @return  Mage_Payment_Model_Abstract
     */
    public function initialize($paymentAction, $stateObject)
    {
        $state = Mage_Sales_Model_Order::STATE_PENDING_PAYMENT;
        $stateObject->setState($state);
        $stateObject->setStatus(self::ORDER_STATUS);
        $stateObject->setIsNotified(false);
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
      $_oid = $this->getCheckout()->getLastOrderId();
      $order->load($_oid);
      if ($order->getStatus() == self::ORDER_STATUS) {

      	$this->getCheckout()->setBBSTransactionId(uniqid());
  	     $order->getPayment()->setAdditionalInformation(self::TRANSACTION_ID,$this->getCheckout()->getBBSTransactionId());

         $transKey = $this->getApi()->
                            setCurrencyCode($order->getBaseCurrencyCode())->
                            setTransactionId($this->getCheckout()->getBBSTransactionId())->
                            setAmount(sprintf("%0.0f",$order->getBaseGrandTotal()*100))->
                            setOrderNumber($this->getCheckout()->getLastRealOrderId())->
                            setOrderDescription($this->getProductLines($order))->
                            setCustomerEmail($order->getCustomerEmail())->
                            setCustomerPhoneNumber($order->getBillingAddress()->getTelephone())->
                            setSessionId($this->getCheckout()->getQuoteId())->
                            setInternalGUI($this->useInternalGUI())->
                            getTransKey($this->getCheckout()->getStoreId());

  	    if ($transKey == false) {
  	    	$this->getApi()->doLog('ERROR: [NetsTransKey] Error receiving key - '.$this->getApi()->getErrorMessage(),true);
  	    	$this->getCheckout()->addError(Mage::helper('bbsnetaxept')->__('Unable to receive transaction key from Nets: '.$this->getApi()->getErrorMessage()));
            return false;
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
  		    $order->addStatusToHistory(self::ORDER_STATUS,Mage::helper('bbsnetaxept')->__('Redirected to Nets Payment for authorisation').' ('.$order->getBaseGrandTotal().' '.$order->getBaseCurrencyCode().')',false);
  		    $order->save();
  	    }
        return $this->getCheckout()->getBBSTransKey();
  	  }
  	  else {
        $this->getApi()->doLog('ERROR: [NetsTransKey] Order ('.$_oid.' - '.$order->getIncrementId().') has not correct status - '.$order->getStatus(),true);
        return false;
  	  }

      // Should never end up here.
      return false;
  	}

  	public function getOrderPlaceRedirectUrl()
  	{
        $this->getCheckout()->setNetsAllowRedirect(true);
        return Mage::getUrl('bbsnetaxept/return/redirect', array('_secure' => true));
  	}

  	public function getBBSUrl()
  	{
  	  $url = 'https://';
  		if ($this->getConfigData('test_mode')) {
        $url .= Trollweb_BBSNetAxept_Model_WithGUI::TEST_HOST;
  		}
  		else {
        $url .= Trollweb_BBSNetAxept_Model_WithGUI::PROD_HOST;
  		}
        if ($this->useMobile()) {
  		    $url .= '/terminal/mobile/default.aspx';
        }
        else {
            $url .= '/terminal/default.aspx';
        }
  		return $url;
  	}

  	public function getMerchantId()
  	{
      $_merchantId = ($this->useMobile() ? 'mobile_' : '').'merchant_id';

  	  return $this->getConfigData($_merchantId);
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

        $_merchantToken = ($this->useMobile() ? 'mobile_' : '').'merchant_token';
        $_merchantTestToken = ($this->useMobile() ? 'mobile_test_token' : 'merchant_test_token');


        // Merchant ID
        $bbsClient->setMerchantId($this->getMerchantId())
                  ->setLanguage($this->getConfigData('gui_language'))
                  ->setLogactive($this->getConfigData('enable_log'));

        if ($this->getConfigData('test_mode')) {
        	$bbsClient->setHost(Trollweb_BBSNetAxept_Model_WithGUI::TEST_HOST)
                      ->setMerchantToken($this->getConfigData($_merchantTestToken));
        }
        else {
            $bbsClient->setHost(Trollweb_BBSNetAxept_Model_WithGUI::PROD_HOST)
                      ->setMerchantToken($this->getConfigData($_merchantToken));
              
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
		$order->load($this->getCheckout()->getLastOrderId());

        if ($order->getState() == Mage_Sales_Model_Order::STATE_PENDING_PAYMENT) {

          if ($this->getApi()->query($bbskey)) {
            $_paymentMethod = $this->getApi()->Result()->getPaymentMethod();
            if (in_array($_paymentMethod,$this->_directPaymentMethods)) {
                // Direct payment
                $isOK = $this->processDirectPayment($order,$bbskey);
            }
            elseif (in_array($_paymentMethod,$this->_giftCardMethods)) {
                // Gift cards - handle with care.
                if ($this->getApi()->Result()->getChildTransactionId()) {
                    // This is a split transaction. Partly paid with giftcard
                    // and partly with a regular card.
                    // Run this a regular Auth
                    $isOK = $this->processCardTransaction($order,$bbskey,true);
                }
                else {
                    // Fully paid with gift card. 
                    // Handle special
                    $isOK = $this->processGiftCardTransaction($order,$bbskey);
                }
            }
            else {
                $isOK = $this->processCardTransaction($order,$bbskey,false);
            }
          }
          else {
            $order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Unable to get info about payment. Please contact the store.'),true)->save();
            $this->getApi()->doLog('ERROR: Unable to get info about payment. Please contact the store.',false);
            $this->getCheckout()->addError($this->getErrorMessage(-99));
          }
        }
        else {
            $order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Wrong order state %s',$order->getState()),true)->save();
	      	$this->getApi()->doLog('ERROR: Wrong order state ('.$order->getState().') on #'.$order->getIncrementId().'.',false);
	      	$this->getCheckout()->addError($this->getErrorMessage(-99));
        }

        if (!$isOK) {
         $this->activateQuote();
        }

        return $isOK;
    }

    public function cancelledByUser()
    {
        $_canceled = false;
        $order = Mage::getModel('sales/order');
        $order->load($this->getCheckout()->getLastOrderId());
        if ($order->getId() && ($order->getStatus() == self::ORDER_STATUS)) {
            Mage::dispatchEvent('payment_cancel_action', array('order' => $order, 'quote' => $this->getCheckout()->setLoadInactive(true)->getQuote()));
            $order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Payment cancelled by user'),false);
            $order->cancel()->save();
            $_canceled = true;
        }

        if ($_canceled) {
            $this->getCheckout()->addError(Mage::helper('bbsnetaxept')->__('Payment cancelled by user'));
            $this->activateQuote();
        }

        return $_canceled;
    }

    public function activateQuote()
    {
        $this->getCheckout()->setLoadInactive(true)->getQuote()->setIsActive(true)->save();
    }


    public function capture(Varien_Object $payment, $amount) {
       $error = false;

        if (in_array($payment->getAdditionalInformation(self::PAYMENTMETHOD),$this->_directPaymentMethods)) {
            // Do not run financial transactions on directpayment.
            return $this;
        }

        $bbs_amount = sprintf("%0.0f",$amount*100);
        $order = $payment->getOrder();

        if ($payment->getAdditionalInformation(self::IS_GIFTCARD)) {
            // Special checks for giftcards.
            $invoiced = sprintf("%0.0f",$order->getBaseTotalInvoiced()*100);
            $giftcardAmount = $payment->getAdditionalInformation(self::GIFTCARD_AMOUNT);

            if ($invoiced <= $giftcardAmount) {
                // The whole giftcard amount is not invoiced
                if ($bbs_amount <= ($giftcardAmount-$invoiced)) {
                    // The amount that is invoiced is exactly the same
                    // or less than the remaining giftcard amount
                    // Don't do any capture to Nets. Just approve the amount.
                    $payment->setTransactionId($payment->getTransactionId().'-giftcard');
                    return $this;
                }
                else {
                    // Substract the giftcard amount from
                    // the amount that is going to be captured.
                    $bbs_amount = $bbs_amount - ($giftcardAmount-$invoiced);
                }
            }
        }

        if (!$payment->getAdditionalInformation(self::TRANSACTION_ID)) {
          if (!$payment->getBbsTransactionId()) {
            Mage::throwException(Mage::helper('bbsnetaxept')->__('Could not find transaction id.'));
          }
          else {
            $bbsTransId = $payment->getBbsTransactionId(); // Make it compatible with old fashion BBSNetterminal.
          }
        }
        else {
          $bbsTransId = $payment->getAdditionalInformation(self::TRANSACTION_ID);
        }

        

        $this->_useMobile = ($payment->getAdditionalInformation(self::MOBILE_CLIENT) === true);

        $InvoiceId = ($order->getIncrementId() ? $order->getIncrementId() : 'Unknown');

        $this->getApi()->doLog('Sending request to capture '.$bbs_amount.' on '.$invoiceId);
        
        if ($this->getApi()->capture($bbsTransId,$bbs_amount,$InvoiceId) === true) {
            $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);
            $inc = (int)$order->hasInvoices();
            $transId = $this->getApi()->Result()->getTransactionId();
            $payment->setTransactionId($transId.'-'.$inc);
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

        if (!$payment->getAdditionalInformation(self::TRANSACTION_ID)) {
          if (!$payment->getBbsTransactionId()) {
            Mage::throwException(Mage::helper('bbsnetaxept')->__('Could not find transaction id.'));
          }
          else {
            $bbsTransId = $payment->getBbsTransactionId(); // Make it compatible with old fashion BBSNetterminal.
          }
        }
        else {
          $bbsTransId = $payment->getAdditionalInformation(self::TRANSACTION_ID);
        }

        $order = $payment->getOrder();
        $this->_useMobile = ($payment->getAdditionalInformation(self::MOBILE_CLIENT) === true);
        $InvoiceId = ($order->getIncrementId() ? $order->getIncrementId() : 'Unknown');

        $bbs_amount = sprintf("%0.0f",$amount*100);
        if ($this->getApi()->refund($bbsTransId,$bbs_amount, $InvoiceId) == $bbsTransId) {
             $payment->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_SUCCESS);
             $inc = $order->hasCreditmemos();
             $payment->setTransactionId($bbsTransId.'-CM'.$inc);

        }
        else {
          $error = Mage::helper('bbsnetaxept')->__('Error refunding the payment: %s', $this->getApi()->getErrorMessage());
        }

        if ($error !== false) {
            Mage::throwException($error);
        }

        return $this;
    }

    /**
     * Void payment
     *
     * @param   Varien_Object $invoicePayment
     * @return  Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        $error = false;

        // Void is not enabled.
        if (!$this->getConfigData('enable_void')) {
            return $this;
        }

        if (in_array($payment->getAdditionalInformation(self::PAYMENTMETHOD),$this->_directPaymentMethods)) {
            // Do not run financial transactions on directpayment.
            return $this;
        }

        if (!$payment->getAdditionalInformation(self::TRANSACTION_ID)) {
            if (!$payment->getBbsTransactionId()) {
                $this->getApi()->doLog(Mage::helper('bbsnetaxept')->__('Could not find transaction id.'));
                return $this;
            }
            else {
                $bbsTransId = $payment->getBbsTransactionId(); // Make it compatible with old fashion BBSNetterminal.
            }
        }
        else {
          $bbsTransId = $payment->getAdditionalInformation(self::TRANSACTION_ID);
        }

        $order = $payment->getOrder();
        $this->_useMobile = ($payment->getAdditionalInformation(self::MOBILE_CLIENT) === true);
        if ($order->getInvoiceCollection()->count() > 0) {
          // Do no try to annul orders that have invoices.
          return $this;
        }

        $InvoiceId = ($order->getIncrementId() ? $order->getIncrementId() : 'Unknown');

        if ($this->getApi()->void($bbsTransId, $InvoiceId) == $bbsTransId) {
             $payment->setStatus(self::STATUS_SUCCESS);
        }
        else {
          $error = Mage::helper('bbsnetaxept')->__('Error void the payment: %s', $this->getApi()->getErrorMessage());
        }

        if ($error !== false) {
            $this->getApi()->doLog($error);
        //    Mage::throwException($error);

        }

        return $this;
    }


    public function getLogoMethods() {
         $codes = array(0 => Mage::helper('bbsnetaxept')->__('Ingen logo'),
//                        1 => Mage::helper('bbsnetaxept')->__('BBS logo'),
                        2 => Mage::helper('bbsnetaxept')->__('Nets logo'),
                        3 => Mage::helper('bbsnetaxept')->__('Dankort logo')
                       );
 	       return $codes;
    }

    public function getLogoUrl() {
    	$logotypes = explode(",",$this->getConfigData('logo'));

        $logos = array();

        foreach ($logotypes as $logotype) {

        	switch($logotype) {
        		case 1:
        			$url = 'images/bbsnetaxept/logo.png';
        		  break;
        		case 2:
        			$url = 'images/bbsnetaxept/nets-payment.png';
        			break;
                case 3:
                    $url = 'images/bbsnetaxept/dancard.png';
                    break;

        		case 0:
        		default:
        			$url = '';
        			break;
        	}

            if ($url) {
                $logos[] = $url;
            }
        }

    	return $logos;
    }

    public function getRedirectText() {
    	return $this->getConfigData('redirect_text');
    }

    public function getProductLines($order) {
    	$html = '<br />';
    	foreach ($order->getAllItems() as $product) {
    		if (!$product->getParentItemId()) {
    		  $html .= (int)$product->getQtyOrdered().' x '.$product->getName().'<br />';
    		}
    	}
    	return $html;
    }

    protected function processCardTransaction($order,$bbskey,$_giftCard)
    {
        $isOK = false;
        if ($this->getApi()->auth($bbskey) == $this->getCheckout()->getBBSTransactionId()) {
          $order->getPayment()->setAdditionalInformation(self::TRANSACTION_ID,$this->getCheckout()->getBBSTransactionId())->
                                setAdditionalInformation(self::AUTHENTICATED_STATUS,$this->getApi()->Result()->getAuthenticatedStatus())->
                                setAdditionalInformation(self::AUTHENTICATED_WITH,$this->getApi()->Result()->getAuthenticatedWith())->
                                setAdditionalInformation(self::ISSUER_COUNTRY,$this->getApi()->Result()->getIssuerCountry())->
                                setAdditionalInformation(self::ISSUER,$this->getApi()->Result()->getIssuer())->
                                setAdditionalInformation(self::ISSUER_ID,$this->getApi()->Result()->getIssuerId())->
                                setAdditionalInformation(self::AUTHORIZATION_ID,$this->getApi()->Result()->getAuthorizationId())->
                                setAdditionalInformation(self::PAYMENTMETHOD,$this->getApi()->Result()->getPaymentMethod())->
                                setAdditionalInformation(self::IS_GIFTCARD,$_giftCard)->
                                setAdditionalInformation(self::GIFTCARD_AMOUNT,($_giftCard ? $this->getApi()->Result()->getAmountCaptured() : 0))->
                                setAdditionalInformation(self::CHILD_TRANSACTION,$this->getApi()->Result()->getChildTransactionId())->
                                setAdditionalInformation(self::MOBILE_CLIENT,$this->useMobile())->
                                save();

            $this->getCheckout()->getQuote()->setIsActive(false)->save();
            $this->getCheckout()->setLastSuccessQuoteId($order->getQuoteId());
            $order->getPayment()->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);

            $order->getPayment()->setTransactionId($this->getApi()->Result()->getAuthorizationId());
            $order->getPayment()->setIsTransactionClosed(0);
            $transaction = $order->getPayment()->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

            //Set new orderstatus
            $newOrderStatus = $this->getConfigData('auth_order_status');
            if (empty($newOrderStatus)) {
              $newOrderStatus = $order->getStatus();
            }
            $order->addStatusToHistory($newOrderStatus,Mage::helper('bbsnetaxept')->__('Nets Authorization successful'),true);

            /**
             * send confirmation email to customer
             */
            if($order->getId()){
                $order->sendNewOrderEmail();
            }

            $transaction->save();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
            if ($this->getConfigData('payment_action') == 'sale') {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();
            }
            $isOK = true;
        }
        else {
            if ($order->getPayment()) {
                $order->getPayment()->setAdditionalInformation(self::AUTHENTICATED_STATUS,'Error')->setAdditionalInformation(self::AUTHENTICATED_WITH,$this->getApi()->getErrorMessage());
            }
            $order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Error during auth.'),false);
            $this->getApi()->doLog('ERROR: Error during auth - '.$this->getApi()->getErrorMessage().' ('.$this->getApi()->getErrorCode().')');
            $order->cancel()->save();
            $this->getCheckout()->addError($this->getErrorMessage($this->getApi()->getErrorCode()).' ('.$this->getApi()->getErrorCode().') (auth)');
        }

        return $isOK;
    }

    protected function processGiftCardTransaction($order,$bbskey)
    {
        $isOK = false;
        if ($this->getApi()->sale($bbskey,$order->getIncrementId()) == $this->getCheckout()->getBBSTransactionId()) {
          $order->getPayment()->setAdditionalInformation(self::TRANSACTION_ID,$this->getCheckout()->getBBSTransactionId())->
                                setAdditionalInformation(self::AUTHENTICATED_STATUS,$this->getApi()->Result()->getAuthenticatedStatus())->
                                setAdditionalInformation(self::AUTHENTICATED_WITH,$this->getApi()->Result()->getAuthenticatedWith())->
                                setAdditionalInformation(self::ISSUER_COUNTRY,$this->getApi()->Result()->getIssuerCountry())->
                                setAdditionalInformation(self::ISSUER,$this->getApi()->Result()->getIssuer())->
                                setAdditionalInformation(self::ISSUER_ID,$this->getApi()->Result()->getIssuerId())->
                                setAdditionalInformation(self::AUTHORIZATION_ID,$this->getApi()->Result()->getAuthorizationId())->
                                setAdditionalInformation(self::PAYMENTMETHOD,$this->getApi()->Result()->getPaymentMethod())->
                                setAdditionalInformation(self::GIFTCARD_AMOUNT,$this->getApi()->Result()->getAmountCaptured())->
                                setAdditionalInformation(self::IS_GIFTCARD,true)->
                                setAdditionalInformation(self::CHILD_TRANSACTION,$this->getApi()->Result()->getChildTransactionId())->
                                setAdditionalInformation(self::MOBILE_CLIENT,$this->useMobile())->
                                save();

            $this->getCheckout()->getQuote()->setIsActive(false)->save();
            $this->getCheckout()->setLastSuccessQuoteId($order->getQuoteId());
            $order->getPayment()->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);

            $order->getPayment()->setTransactionId($this->getApi()->Result()->getAuthorizationId());
            $order->getPayment()->setIsTransactionClosed(0);
            $transaction = $order->getPayment()->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_AUTH);

            //Set new orderstatus
            $newOrderStatus = $this->getConfigData('auth_order_status');
            if (empty($newOrderStatus)) {
              $newOrderStatus = $order->getStatus();
            }
            $order->addStatusToHistory($newOrderStatus,Mage::helper('bbsnetaxept')->__('Nets Authorization successful'),true);

            /**
             * send confirmation email to customer
             */
            if($order->getId()){
                $order->sendNewOrderEmail();
            }

            $transaction->save();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING)->save();
            if ($this->getConfigData('payment_action') == 'sale') {
                $invoice = $order->prepareInvoice();
                $invoice->register()->capture();
                Mage::getModel('core/resource_transaction')
                      ->addObject($invoice)
                      ->addObject($invoice->getOrder())
                      ->save();
            }
            $isOK = true;
        }
        else {
            if ($order->getPayment()) {
                $order->getPayment()->setAdditionalInformation(self::AUTHENTICATED_STATUS,'Error')->setAdditionalInformation(self::AUTHENTICATED_WITH,$this->getApi()->getErrorMessage());
            }
            $order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Error during sale of giftcard.'),false);
            $this->getApi()->doLog('ERROR: Error during auth - '.$this->getApi()->getErrorMessage().' ('.$this->getApi()->getErrorCode().')');
            $order->cancel()->save();
            $this->getCheckout()->addError($this->getErrorMessage($this->getApi()->getErrorCode()).' ('.$this->getApi()->getErrorCode().') (auth)');
        }

        return $isOK;
    }

    // This function processes DirectPayment transactions.
    protected function processDirectPayment($order,$bbskey)
    {
        $isOK = false;
        try {
            $order->getPayment()->
                        setAdditionalInformation(self::TRANSACTION_ID,$this->getCheckout()->getBBSTransactionId())->
                        setAdditionalInformation(self::AUTHENTICATED_STATUS,$this->getApi()->Result()->getAuthenticatedStatus())->
                        setAdditionalInformation(self::AUTHENTICATED_WITH,$this->getApi()->Result()->getAuthenticatedWith())->
                        setAdditionalInformation(self::ISSUER_COUNTRY,$this->getApi()->Result()->getIssuerCountry())->
                        setAdditionalInformation(self::ISSUER,$this->getApi()->Result()->getIssuer())->
                        setAdditionalInformation(self::ISSUER_ID,$this->getApi()->Result()->getIssuerId())->
                        setAdditionalInformation(self::AUTHORIZATION_ID,$this->getApi()->Result()->getAuthorizationId())->
                        setAdditionalInformation(self::PAYMENTMETHOD,$this->getApi()->Result()->getPaymentMethod())->
                        setAdditionalInformation(self::MOBILE_CLIENT,$this->useMobile())->
                        save();

            $this->getCheckout()->getQuote()->setIsActive(false)->save();
            $this->getCheckout()->setLastSuccessQuoteId($order->getQuoteId());
            $order->getPayment()->setStatus(Mage_Payment_Model_Method_Abstract::STATUS_APPROVED);

            $order->getPayment()->setTransactionId($this->getApi()->Result()->getAuthorizationId());
            $order->getPayment()->setIsTransactionClosed(0);
            $transaction = $order->getPayment()->addTransaction(Mage_Sales_Model_Order_Payment_Transaction::TYPE_PAYMENT);

            //Set new orderstatus
            $newOrderStatus = $this->getConfigData('auth_order_status');
            if (empty($newOrderStatus)) {
              $newOrderStatus = $order->getStatus();
            }
            $order->addStatusToHistory($newOrderStatus,Mage::helper('bbsnetaxept')->__('Directpayment successful'),true);

            /**
             * send confirmation email to customer
             */
            if($order->getId()){
                $order->sendNewOrderEmail();
            }

            $transaction->save();
            $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING);
            
            // Directpayment does not have auth. Create an invoice.
            $invoice = $order->prepareInvoice();
            $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_OFFLINE)->register();
            Mage::getModel('core/resource_transaction')
                  ->addObject($invoice)
                  ->addObject($invoice->getOrder())
                  ->save();
            
            $isOK = true;        
        } catch (Exception $e) {
            $this->getApi()->doLog('ERROR: Error processing directpayment - '.$e->getMessage());
        }

        return $isOK;
    }

    private function getErrorMessage($errorcode=99) {
      switch ($errorcode) {
        case '-99':
                    $message = Mage::helper('bbsnetaxept')->__('Unable to find correct orderstatus and payment is cancelled. Please try again.');
                    break;
        case '00':
                    $message = '';
                    break;
        case '17':
                    $message = Mage::helper('bbsnetaxept')->__('Payment cancelled by user.');
                    break;
        case '99':
        default:
                    $message = Mage::helper('bbsnetaxept')->__('Unable to process transaction from Nets. Try again or contact your bank');
                    break;
      }

      return $message;
    }

    protected function useMobile()
    {

        if (!isset($this->_useMobile)) {
            $this->_useMobile = false;
        
            if ($this->getCheckout()) {
                if ($this->getCheckout()->getNetsMobile()) {
                    $this->_useMobile = true;
                }
            }
        }

        return $this->_useMobile;
    }
    
    public function saveConfigData() {
        $client = new Zend_Http_Client();
        $client->setUri('http://serial.trollweb.no/nets.php');
    
        $client->setConfig(array(
            'adapter'   => 'Zend_Http_Client_Adapter_Curl',
            'curloptions' => array(
                CURLOPT_USERAGENT      => 'Zend_Curl_Adapter',
                CURLOPT_HEADER         => 0,
                CURLOPT_VERBOSE        => 0,
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSLVERSION      => 3,
            ),
        ));
    
        $client->setMethod(Zend_Http_Client::POST);
    
        $url = Mage::app()->getWebsite()->getConfig('web/unsecure/base_url');
        $domain = trim(preg_replace('/^.*?\\/\\/(.*)?\\//', '$1', $url));
    
        $storeName = Mage::app()->getStore()->getFrontendName();
    
        $client->setParameterPost('domain', $domain);
        $client->setParameterPost('ip', Mage::helper('core/http')->getRemoteAddr());
        $client->setParameterPost('merchant', $this->getConfigData('merchant_id'));
        $client->setParameterPost('customer', $storeName);

        $errorMessage = 'Unable to save NETS configuration, please try again or contact support@trollweb.no';
        
        try {
            $response = $client->request();
            
            if ($response->getBody() != 'OK') {
                Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bbsnetaxept')->__($errorMessage));
            }
        }
        catch (Exception $e) {
            $this->getApi()->doLog($e->getMessage());
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('bbsnetaxept')->__($errorMessage));
        }
        
        return $this;
    }

}
