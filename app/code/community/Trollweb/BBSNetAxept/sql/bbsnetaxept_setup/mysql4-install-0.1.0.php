<?
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


$installer = $this;

$installer->startSetup();

$installer->addAttribute('order_payment', 'bbs_transaction_id', array());
$installer->addAttribute('order_payment', 'bbs_authenticated_status', array());
$installer->addAttribute('order_payment', 'bbs_authenticated_with', array());
$installer->addAttribute('order_payment', 'bbs_issuer_country', array());
$installer->addAttribute('order_payment', 'bbs_issuer_id', array());
$installer->addAttribute('order_payment', 'bbs_authorization_id', array());
$installer->addAttribute('order_payment', 'bbs_session_number', array());

$installer->endSetup();
