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

class Trollweb_BBSNetAxept_Model_Api_BbsRequest {
  
 public $Amount;
 public $CurrencyCode;
 public $CustomerEmail;
 public $CustomerPhoneNumber;
 public $Description;
 public $Language;
 public $OrderDescription;
 public $OrderNumber;
 public $PanHash;
 public $RecurringExpiryDate;
 public $RecurringFrequency;
 public $RecurringType;
 public $RedirectUrl;
 public $ServiceType;
 public $SessionId;
 public $TransactionId;
 
 function Trollweb_BBSNetAxept_Model_Api_BbsRequest
   (
     $Amount,
     $CurrencyCode,
     $OrderDescription,
     $OrderNumber,
     $RedirectUrl,
     $ServiceType,                /* B : BBS Hosted UI ; M : Merchant Hosted UI ; C : Call Center Solution */ 
     $SessionId,
     $TransactionId
   )
 {
  $this->Amount                   = $Amount;
  $this->CurrencyCode             = $CurrencyCode;
  $this->OrderDescription         = $OrderDescription;
  $this->OrderNumber              = $OrderNumber;
  $this->RedirectUrl              = $RedirectUrl;
  $this->ServiceType              = $ServiceType;
  $this->SessionId                = $SessionId;
  $this->TransactionId            = $TransactionId;
 }
 
};

