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
* BBS Api
*/
class Trollweb_BBSNetAxept_Model_Api_Bbs extends Varien_Object
{

   protected $_result;

   /**
     * Auth with BBS and return the key.
     *
     * @param none
     * @return array
     */
   public function getTransKey($storeId = null) {
      $result = false;

      $response = $this->doClientRequest('Register',$this->getRequest($storeId));
      if ($response && isset($response->TransactionId)) {
        $result = (string)$response->TransactionId;
      }

      if ($this->getError()) {
        $this->doLog('ERROR: [TransKey] '.$this->getErrorMessage());
      }

      return $result;
   }

   /**
     * Authorize the request from BBS.
     *
     * @param String $TransactionId
     * @return string|false
     */
   public function auth($TransactionId) {

     $result = false;
     $params = array(
       'operation'     => 'AUTH',
       'transactionId' => $TransactionId,
                    );


     $response = $this->doClientRequest('Process',$params);

     if ($response) {
       $this->Result()->setResponseCode((string)$response->ResponseCode);
       $this->Result()->setIssuerId((string)$response->IssuerId);
       $this->Result()->setAuthorizationId((string)$response->AuthorizationId);
       $this->Result()->setTransactionId((string)$response->TransactionId);
       $this->Result()->setAuthenticatedWith('');
       $this->Result()->setAuthenticatedStatus('');

       if (isset($response->ResponseCode) && ($response->ResponseCode == "OK")) {
         $this->query($TransactionId); // Fetch additional information.

         $result = (string)$response->TransactionId;
       }
       else {
         $this->setError(true);
         $this->setErrorMessage('('.(string)$response->ResponseSource.') '.(string)$response->ResponseText);
       }
     }

     if ($this->getError()) {
       $this->doLog('ERROR: [Auth] '.$this->getErrorMessage());
     }

     return $result;
   }


   /**
     * Sale transaction to Nets.
     *
     * @param String $TransactionId, [String $description]
     * @return string|false
     */
   public function sale($TransactionId, $description='') {
     $result = false;
     $params = array(
       'operation'           => 'SALE',
       'transactionId'       => $TransactionId,
       'description'         => $description,
       'transactionReconRef' => $description,
                    );

     $response = $this->doClientRequest('Process',$params);

     if ($response) {
      $this->Result()->setResponseCode((string)$response->ResponseCode);
      $this->Result()->setAuthorizationId((string)$response->AuthorizationId);
      $this->Result()->setTransactionId((string)$response->TransactionId);
      $this->Result()->setAuthenticatedWith('');
      $this->Result()->setAuthenticatedStatus('');

      if (isset($response->ResponseCode) && ($response->ResponseCode == "OK")) {
        $this->query($TransactionId); // Fetch additional information.

        $result = (string)$response->TransactionId;
      }
      else {
       $this->setError(true);
       $this->setErrorMessage('('.(string)$response->ResponseSource.') '.(string)$response->ResponseText);
      }
     }

     if ($this->getError()) {
      $this->doLog('ERROR: [Sales] '.$this->getErrorMessage());
     }

     return $result;
   }


   /**
     * Capture from Nets.
     *
     * @param String $TransactionId, Integer $amount, [String $description]
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function capture($TransactionId, $amount, $description='') {
     $result = false;
     $params = array(
       'operation'           => 'CAPTURE',
       'transactionId'       => $TransactionId,
       'transactionAmount'   => $amount,
       'description'         => $description,
       'transactionReconRef' => $description,
                    );

     $response = $this->doClientRequest('Process',$params);

     if ($response) {
      $this->Result()->setResponseCode((string)$response->ResponseCode);
      $this->Result()->setIssuerId((string)$response->IssuerId);
      $this->Result()->setAuthorizationId((string)$response->AuthorizationId);
      $this->Result()->setTransactionId((string)$response->TransactionId);
      $this->Result()->setAuthenticatedWith('');
      $this->Result()->setAuthenticatedStatus('');

      if (isset($response->ResponseCode) && ($response->ResponseCode == "OK")) {

       $result = true;
      }
      else {
       $this->setError(true);
       $this->setErrorMessage('('.(string)$response->ResponseSource.') '.(string)$response->ResponseText);
      }
     }

     if ($this->getError()) {
      $this->doLog('ERROR: [Capture] '.$this->getErrorMessage());
     }

     return $result;
   }

   /**
     * Refund with BBS.
     *
     * @param String $TransactionId, Integer $amount, [String $description]
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function refund($TransactionId, $amount, $description='') {

     $result = false;
     $params = array(
       'operation'           => 'CREDIT',
       'transactionId'       => $TransactionId,
       'transactionAmount'   => $amount,
       'description'         => $description,
       'transactionReconRef' => $description,
                    );

     $response = $this->doClientRequest('Process',$params);

     if ($response) {
      $this->Result()->setResponseCode((string)$response->ResponseCode);
      $this->Result()->setIssuerId((string)$response->IssuerId);
      $this->Result()->setAuthorizationId((string)$response->AuthorizationId);
      $this->Result()->setTransactionId((string)$response->TransactionId);
      $this->Result()->setAuthenticatedWith('');
      $this->Result()->setAuthenticatedStatus('');

      if (isset($response->ResponseCode) && ($response->ResponseCode == "OK")) {

       $result = (string)$response->TransactionId;
      }
      else {
       $this->setError(true);
       $this->setErrorMessage('('.(string)$response->ResponseSource.') '.(string)$response->ResponseText);
      }
     }

     if ($this->getError()) {
      $this->doLog('ERROR: [Refund] '.$this->getErrorMessage());
     }

     return $result;
   }

   /**
     * Void on BBS.
     *
     * @param String $TransactionId, [String $description]
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function void($TransactionId, $description='') {

    $result = false;
    $params = array(
      'operation'           => 'ANNUL',
      'transactionId'       => $TransactionId,
    );

    $response = $this->doClientRequest('Process',$params);

    if ($response) {
     $this->Result()->setResponseCode((string)$response->ResponseCode);
     $this->Result()->setIssuerId((string)$response->IssuerId);
     $this->Result()->setAuthorizationId((string)$response->AuthorizationId);
     $this->Result()->setTransactionId((string)$response->TransactionId);
     $this->Result()->setAuthenticatedWith('');
     $this->Result()->setAuthenticatedStatus('');

     if (isset($response->ResponseCode) && ($response->ResponseCode == "OK")) {

      $result = (string)$response->TransactionId;
     }
     else {
      $this->setError(true);
      $this->setErrorMessage('('.(string)$response->ResponseSource.') '.(string)$response->ResponseText);
     }
    }

    if ($this->getError()) {
     $this->doLog('ERROR: [Void] '.$this->getErrorMessage());
    }

    return $result;
   }

   public function query($TransactionId)
   {
     $result = false;
     $params = array(
       'transactionId' => $TransactionId,
                    );

     $this->Result()->setChildTransactionId('');


     $response = $this->doClientRequest('Query',$params);

     if ($response) {

       $this->addResult('issuer',$response->CardInformation->Issuer);

       $this->Result()->setIssuer((string)$response->CardInformation->Issuer);
       $this->Result()->setIssuerCountry((string)$response->CardInformation->IssuerCountry);
       $this->Result()->setPaymentMethod((string)$response->CardInformation->PaymentMethod);
       $this->Result()->setMaskedPAN((string)$response->CardInformation->MaskedPAN);

       $this->Result()->setAuthorized((((string)$response->Summary->Authorized) == "true"));
       $this->Result()->setAuthorizationId((string)$response->Summary->AuthorizationId);

       $this->Result()->setTransactionId((string)$response->TransactionId);

       $this->Result()->setAnulled(((string)$response->Summary->Annulled) == "true");
       $this->Result()->setAmountCaptured((float)$response->Summary->AmountCaptured);
       $this->Result()->setChildTransactionId((string)$response->ChildTransactionId);

       if (isset($response->AuthenticationInformation->AuthenticatedWith)) {
         $this->Result()->setAuthenticatedWith((string)$response->AuthenticationInformation->AuthenticatedWith);
       }
       if (isset($response->AuthenticationInformation->AuthenticatedStatus)) {
         $this->Result()->setAuthenticatedStatus((string)$response->AuthenticationInformation->AuthenticatedStatus);
       }

       $result = true;
     }

     if ($this->getError()) {
       $this->doLog('ERROR: [Query] '.$this->getErrorMessage());
     }

     return $result;
   }

   /**
     * Check BBS Transaction
     *
     * @param String $TransactionId
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function checkStatus($TransactionId) {

     $result = false;
     if ($this->query($TransactionId)) {
        $result = $this->Result()->getAuthorized();
     }

     return $result;
   }

   public function Result() {
     if (!is_object($this->_result)) {
       $this->_result = new Varien_Object;
     }

     return $this->_result;
   }

   protected function addResult($field,$object)
   {
     if (isset($object)) {
       $this->Result()->setData($field,(string)$object);
     }
   }

   private function doClientRequest($function,$params)
   {

     $netsResult = false;
     $url = 'https://'.$this->getHost().'/Netaxept/'.$function.'.aspx';
     $bbsClient = new Zend_Http_Client($url);
     $bbsClient->setParameterGet('merchantId',$this->getMerchantId());
     $bbsClient->setParameterGet('token',$this->getMerchantToken());

     foreach ($params as $key => $value)
     {
       $bbsClient->setParameterGet($key,$value);
     }

     try {
        $response = $bbsClient->request();
        //$this->doLog($bbsClient->getLastRequest());
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->getMessage());
        $response = false;
      }

      if ($response) {
        $netsResult = $this->parseResult($response->getBody());
      }

      if ($this->getError()) {
        $this->doLog('ERROR: ['.$function.'] '.$this->getErrorMessage());
      }

      return $netsResult;
   }

   private function parseResult($xml)
   {
     $dom = simplexml_load_string($xml);

     if (isset($dom->Error)) {
       $this->setErrorMessage((string)$dom->Error->Message);
       $this->setError(true);
       if (isset($dom->Error->Result)) {
         return $dom->Error->Result;
       }
       else {
         return false;
       }
     }

     return $dom;
   }


   private function getRequest($storeId) {

     // Set default norwegian language
     if ($this->getLanguage() == false) {
       $this->setLanguage('no_NO');
     }

     if (Mage::getStoreConfig('payment/bbsnetaxept_withgui/alternate_redirect', $storeId)) {
        $redirectUrl = Mage::getStoreConfig('payment/bbsnetaxept_withgui/alternate_redirect', $storeId);
     }
     else {
     	$redirectUrlConfig = 'bbsnetaxept/return/check';
      $redirectUrl = Mage::getUrl($redirectUrlConfig, array('_secure' => true, '_query' => false, '_nosid' => true));
     }

     $_singlePage = 'false';
     if (Mage::getStoreConfig('payment/bbsnetaxept_withgui/singlepage', $storeId)) {
       $_singlePage = 'true';
     }

     $request = array(
       'serviceType'          => ($this->getInternalGUI() ? 'M' : 'B'),
       'transactionId'        => $this->getTransactionId(),
       'orderNumber'		      => $this->getOrderNumber(),
       'currencyCode'         => $this->getCurrencyCode(),
       'amount'					      => $this->getAmount(),
       'orderDescription'		  => $this->getOrderDescription(),
       'language'						  => $this->getLanguage(),
       'redirectUrl'				  => $redirectUrl,
       'customerEmail'			  => $this->getCustomerEmail(),
       'customerPhoneNumber'  => $this->getCustomerPhoneNumber(),
       'terminalSinglePage'   => $_singlePage,
//       'paymentMethodList'  => $this->getPaymentMethodList(), // TODO: implement this
                     );


    return $request;

   }

   public function doLog($logline,$force=false) {
    $logDir = Mage::getBaseDir('log');

    if ($force or $this->getLogactive()) {
	    $fh = fopen($logDir."/trollweb_bbsnetaxept.log","a");
	    if ($fh) {
	      fwrite($fh,"[".date("d.m.Y h:i:s")."] ".$logline."\n");
	      fclose($fh);
	    }
    }
   }
}

