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
   public function getTransKey() {
      $result = false;
      
      $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getWsdlUrl());
      
     try {
        $request = array("token" => $this->getMerchantToken(),"merchantId" => $this->getMerchantId(), "request" => $this->getRequest());
        $soapResult = $bbsClient->Setup($request);
        if (preg_match("/VALUE=\"([^\"]+)\"/",$soapResult->SetupResult,$match)) {
          $result = $match[1];
        }
        else {
          $this->setError(true);
          $this->setErrorMessage('Error parsing soap result.');
        }
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
      }
      
      return $result;
   }
   
   /**
     * Process the request from BBS.
     *
     * @param String $BBSTransKey
     * @return String TransactionId
     */
   public function Process($BBSTransKey) {
     $this->setError(false);
     $result = '__';
     $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getWsdlUrl());
     
     $params = array(
                "merchantId"          => $this->getMerchantId(),
                "token"               => $this->getMerchantToken(),
                "transactionString"   => $BBSTransKey,
                    );
                    
     try {
        $soapResult = $bbsClient->ProcessSetup($params);

        /*
         * -- Result object --
object(stdClass)[58]
  public 'ProcessSetupResult' => 
    object(stdClass)[64]
      public 'AuthenticatedStatus' => string 'N' (length=1)
      public 'AuthenticatedWith' => string '3-D Secure' (length=10)
      public 'AuthorizationCode' => null
      public 'AuthorizationId' => null
      public 'ExecutionTime' => string '2008-05-18T17:04:48.578125+02:00' (length=32)
      public 'IssuerCountry' => string 'NO' (length=2)
      public 'IssuerCountryCode' => string '578' (length=3)
      public 'IssuerId' => string '3' (length=1)
      public 'MerchantId' => string '200906' (length=6)
      public 'ResponseCode' => string 'OK' (length=2)
      public 'ResponseSource' => null
      public 'ResponseText' => null
      public 'SessionId' => string '31' (length=2)
      public 'SessionNumber' => null
      public 'TransactionId' => string '100000194_483046b1770f3' (length=23)
         * 
         */
        
        $this->Result()->setAuthenticatedStatus($soapResult->ProcessSetupResult->AuthenticatedStatus);
        $this->Result()->setAuthenticatedWith($soapResult->ProcessSetupResult->AuthenticatedWith);
        $this->Result()->setAuthorizationCode($soapResult->ProcessSetupResult->AuthorizationCode);
        $this->Result()->setAuthorizationId($soapResult->ProcessSetupResult->AuthorizationId);
        $this->Result()->setIssuerCountry($soapResult->ProcessSetupResult->IssuerCountry);
        $this->Result()->setIssuerCountryCode($soapResult->ProcessSetupResult->IssuerCountryCode);
        $this->Result()->setIssuerId($soapResult->ProcessSetupResult->IssuerId);
        $this->Result()->setResponseCode($soapResult->ProcessSetupResult->ResponseCode);
        $this->Result()->setSessionId($soapResult->ProcessSetupResult->SessionId);
        $this->Result()->setTransactionId($soapResult->ProcessSetupResult->TransactionId);
        
        $result = $soapResult->ProcessSetupResult->TransactionId;
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
        $this->setErrorCode(99);
        
        if (preg_match('/Response code: ([0-9]+)/',$e->faultstring,$match)) {
          $this->setErrorCode($match[1]);
        }
       }                    
      
      return $result;
   }

   /**
     * Process the request from BBS.
     *
     * @param String $TransactionId
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function auth($TransactionId) {
   
     $result = '__';
     $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getWsdlUrl());     
     $params = array(
                "merchantId"          => $this->getMerchantId(),
                "token"               => $this->getMerchantToken(),
                "transactionId"       => $TransactionId,
                    );

     try {
        $soapResult = $bbsClient->Auth($params);
        
        /*
         *  --- Result Object --
 object(stdClass)[64]
  public 'AuthResult' => 
    object(stdClass)[60]
      public 'AuthenticatedStatus' => null
      public 'AuthenticatedWith' => null
      public 'AuthorizationCode' => null
      public 'AuthorizationId' => string '000889' (length=6)
      public 'ExecutionTime' => string '2008-05-18T17:04:48.859375+02:00' (length=32)
      public 'IssuerCountry' => null
      public 'IssuerCountryCode' => null
      public 'IssuerId' => string '3' (length=1)
      public 'MerchantId' => string '4' (length=1)
      public 'ResponseCode' => string 'OK' (length=2)
      public 'ResponseSource' => null
      public 'ResponseText' => null
      public 'SessionId' => null
      public 'SessionNumber' => string '837' (length=3)
      public 'TransactionId' => string '100000194_483046b1770f3' (length=23)
         */
        
        $this->Result()->setAuthorizationId($soapResult->AuthResult->AuthorizationId);
        $this->Result()->setIssuerId($soapResult->AuthResult->IssuerId);
        $this->Result()->setResponseCode($soapResult->AuthResult->ResponseCode);
        $this->Result()->setSessionNumber($soapResult->AuthResult->SessionNumber);
        $this->Result()->setTransactionId($soapResult->AuthResult->TransactionId);
        $result = $soapResult->AuthResult->TransactionId;
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
        $this->setErrorCode(99);
        
        if (preg_match('/Response code: ([0-9]+)/',$e->faultstring,$match)) {
          $this->setErrorCode($match[1]);
        }
      }                    
      
      return $result;
   }
  
   /**
     * Capture from BBS.
     *
     * @param String $TransactionId, Integer $amount, [String $description]
     * @return Trollweb_BBSNetAxept_Model_BBSNetterminal_Result
     */
   public function capture($TransactionId, $amount, $description='') {
     $result = '__';
     $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getWsdlUrl());     
     $params = array(
                "merchantId"          => $this->getMerchantId(),
                "token"               => $this->getMerchantToken(),
                "transactionId"       => $TransactionId,
                "transactionAmount"   => $amount,
                "description"		  => $description
                    );

     try {
        $soapResult = $bbsClient->Capture($params);

        /*
         * -- Result Object --
object(stdClass)[850]
  public 'CaptureResult' => 
    object(stdClass)[834]
      public 'AuthenticatedStatus' => null
      public 'AuthenticatedWith' => null
      public 'AuthorizationCode' => null
      public 'AuthorizationId' => null
      public 'ExecutionTime' => string '2008-06-04T19:40:55.34375+02:00' (length=31)
      public 'IssuerCountry' => null
      public 'IssuerCountryCode' => null
      public 'IssuerId' => string '3' (length=1)
      public 'MerchantId' => string '4' (length=1)
      public 'ResponseCode' => string 'OK' (length=2)
      public 'ResponseSource' => null
      public 'ResponseText' => null
      public 'SessionId' => null
      public 'SessionNumber' => string '837' (length=3)
      public 'TransactionId' => string '4846d45c6ea1f' (length=13)
        */
        
        $this->Result()->setIssuerId($soapResult->CaptureResult->IssuerId);
        $result = $soapResult->CaptureResult->TransactionId;
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
        $this->setErrorCode(99);
        
        if (preg_match('/Response code: ([0-9]+)/',$e->faultstring,$match)) {
          $this->setErrorCode($match[1]);
        }
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
   
     $result = '__';
     $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getWsdlUrl());     
     $params = array(
                "merchantId"          => $this->getMerchantId(),
                "token"               => $this->getMerchantToken(),
                "transactionId"       => $TransactionId,
                "transactionAmount"   => $amount,
                "description"         => $description
                    );

     try {
        $soapResult = $bbsClient->Credit($params);

        $result = $soapResult->CreditResult->TransactionId;
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
        $this->setErrorCode(99);
        
        if (preg_match('/Response code: ([0-9]+)/',$e->faultstring,$match)) {
          $this->setErrorCode($match[1]);
        }
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
     $bbsClient = new Trollweb_BBSNetAxept_Model_Api_SoapClient($this->getQueryWsdlUrl());     
     $params = array(
                "merchantId"          => $this->getMerchantId(),
                "token"               => $this->getMerchantToken(),
                "transactionId"       => trim($TransactionId),
                    );

     try {
        $soapResult = $bbsClient->Query($params);
        if (is_object($soapResult->QueryResult)) {
          $result = $soapResult->QueryResult->Summary->Authorized;
        }
      }
      catch (Exception $e) {
        $this->setError(true);
        $this->setErrorMessage($e->faultstring);
        $this->setErrorCode(99);
        $result = false;
      }                    
      
      return $result;
   }
   
   public function Result() {
     if (!is_object($this->_result)) {
       $this->_result = new Trollweb_BBSNetAxept_Model_BBSNetterminal_Result;
     }
     
     return $this->_result;
   }   
   
   public function validate() {
   	$regcode = $this->getRegCode();
   	$carray = explode(".",$_SERVER[base64_decode('U0VSVkVSX05BTUU=')]);
    $d = strtolower($carray[count($carray)-2]);
    
   	return ($this->magic(${base64_decode("ZA==")},$regcode,$d) == ${base64_decode('cmVnY29kZQ==')});
   }
   
   private function getRequest() {
     
     // Set default norwegian language
     if ($this->getLanguage() == false)
       $this->setLanguage('no_NO');
     

     $request = new Trollweb_BBSNetAxept_Model_Api_BbsRequest(
         $this->getAmount(),
         $this->getcurrencyCode(),
         $this->getOrderDescription(),
         $this->getOrderNumber(),
         Mage::getUrl('bbsnetaxept/return/check',array('_secure' => true, '_query' => false, '_nosid' => true)),
         ($this->getInternalGUI() ? 'M' : 'B'),
         $this->getSessionId(),
         $this->getTransactionId()
       );
    
    $request->Language = $this->getLanguage();
    $request->CustomerEmail = $this->getCustomerEmail();
    $request->CustomerPhoneNumber = $this->getCustomerPhoneNumber();
    
    return $request;
       
   }
   
   private function magic($secret,$regcode,$domain) {
      if ($secret == false) {
      	$secret = $_SERVER[base64_decode('VU5JUVVFX0lE')];
      }
      $key = $secret.$regcode.$domain;
      $offset = 0;
      $privkey = rand(1,strlen($domain));
      $offset = (strlen($key)*32)-(strlen($key)*64)+$privkey-$offset+(strlen($key)*32);
      $f = base64_decode("bWQ1");
      return $f(base64_encode(strtolower(substr($secret,0,strlen($domain) % $offset).substr($domain,(strlen($secret) % $offset))).base64_decode("dHJvbGx3ZWJfYmJz")));
   }
}

