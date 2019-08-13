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
                      ->addAttributeToFilter('status',Trollweb_BBSNetAxept_Model_WithGUI::ORDER_STATUS);


      foreach ($collection as $key => $order) {
        $bbs = Mage::getModel('bbsnetaxept/withGUI')->getApi();
      	// Cancel all orders older than X minutes. (change in admin)
      	$timeout = $order->getPayment()->getMethodInstance()->getPendingTimeout();

      	if (($timeout > 0) and (strtotime($order->getUpdatedAt())+($timeout*60) < time()))
        {
          $transid = $order->getPayment()->getAdditionalInformation(Trollweb_BBSNetAxept_Model_WithGUI::TRANSACTION_ID);
          $status = $bbs->checkStatus($transid);
          if ($status == false) {
          	$bbs->doLog('Order number '.$order->getIncrementId().' is automatic canceled due to missing payment (Timeout: '.$timeout.')',true);
            $order->cancel();
          	$order->addStatusToHistory($order->getStatus(),Mage::helper('bbsnetaxept')->__('Automatic canceled due to missing authorization (Timeout: '.$timeout.')'),false);
            $order->save();
          }
          else {
          	// If marked as captured in BBS, lets try to find the correct invoice and mark it as captured here aswell.
            $captured = $bbs->Result()->getAmountCaptured();
            if ($captured > 0) {
            	if ($order->hasInvoices()) {

            		foreach ($order->getInvoiceCollection() as $id => $invoice) {
            			$amount = $invoice->getBaseGrandTotal();
            			if ($invoice->canCapture() and ($amount*100 == $captured)) {
            				$bbs->doLog('Invoice number '.$invoice->getIncrementId().' was marked as captured on Nets and is automaticly marked as paied in magento. (Amount: '.$amount.')',true);
                    $invoice->setIsPaid(true);
            				$invoice->pay()->save();
                    $message = Mage::helper('bbsnetaxept')->__('Registered notification about captured amount of %s.', $amount);
                    $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true, $message);
            				$order->save();
            			}
            		}
            	}
            }
          }
        }
      }

    }
}