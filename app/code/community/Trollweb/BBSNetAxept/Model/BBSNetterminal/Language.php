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

class Trollweb_BBSNetAxept_Model_BBSNetterminal_Language 
{
	public function toOptionArray()
	{
	  return array( 
	            array('value' => 'no_NO', 'label' => 'Norsk'),
	            array('value' => 'sv_SE', 'label' => 'Svenska'),
	            array('value' => 'en_GB', 'label' => 'English')
	              );
	}
	
}