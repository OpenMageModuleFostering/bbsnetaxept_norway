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

class Trollweb_BBSNetAxept_Model_Cron
{
	
    public function checkOrders($schedule)
    {
    	$collection = Mage::getResourceModel('sales/order_collection')
                      ->addAttributeToSelect('*')
                      ->addAttributeToFilter('status','pending_bbs');

                      
      foreach ($collection as $key => $order) {
        $bbs = Mage::getModel('bbsnetaxept/withGUI')->getApi();
      	// Cancel all orders older than X minutes. (change in config)
      	$timeout = $order->getPayment()->getMethodInstance()->getPendingTimeout();
      	var_dump($timeout);
        if (($timeout > 0) and (strtotime($order->getUpdatedAt())+($timeout*60) < time()))
        {
          $transid = $order->getPayment()->getBbsTransactionId()."\n";
          if ($bbs->checkStatus($transid) == false) {
            $order->cancel()->save();
          }
        }
      }
    	
    }
}