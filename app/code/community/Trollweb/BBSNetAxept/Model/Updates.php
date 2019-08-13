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

class Trollweb_BBSNetAxept_Model_Updates
{
    // Config paths
    const FEED_URL_PATH = 'trollweb/updates/url';
    const FEED_ENABLED = 'trollweb/updates/enabled';
    const FEED_CHECK_FREQUENCY = 'trollweb/updates/check_frequencey';

    const CACHE_PREFIX = 'trollweb_notification';

    protected $_feedUrl;
    protected $_module;

    function check(Varien_Event_Observer $observer) {
          if (Mage::getSingleton('admin/session')->isLoggedIn()) {
              $this->getModule();
              $this->checkUpdate();
          }
    }


    function checkUpdate()
    {
       if (($this->getFrequency() + $this->getLastUpdate()) > time()) {
  //          return $this;
        }

        $feedData = array();

        $feedXml = $this->getFeedData();
        if ($feedXml && $feedXml->channel && $feedXml->channel->item) {

          foreach ($feedXml->channel->item as $item) {
                $feedData[] = array(
                    'severity'      => (int)$item->severity,
                    'date_added'    => $this->getDate((string)$item->pubDate),
                    'title'         => (string)$item->title,
                    'description'   => (string)$item->description,
                    'url'           => (string)$item->link,
                );
            }

            if ($feedData) {
                Mage::getModel('adminnotification/inbox')->parse(array_reverse($feedData));
            }

        }
        $this->setLastUpdate();
        return $this;
    }

    public function getModule()
    {
      if (!isset($this->_module)) {
        $tags = explode("_",get_class($this));
        // Check if this is a trollweb module.
        if (strtolower($tags[0]) == "trollweb") {
          $this->_module = strtolower($tags[1]);
        }
      }
      return $this->_module;
    }

    /**
     * Retrieve DB date from RSS date
     *
     * @param string $rssDate
     * @return string YYYY-MM-DD YY:HH:SS
     */
    public function getDate($rssDate)
    {
        return gmdate('Y-m-d H:i:s', strtotime($rssDate));
    }

    /**
     * Retrieve Update Frequency
     *
     * @return int
     */
    public function getFrequency()
    {
        return Mage::getStoreConfig(self::FEED_CHECK_FREQUENCY);
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function getLastUpdate()
    {
        return Mage::app()->loadCache(self::CACHE_PREFIX."_".$this->getModule().'_lastcheck');
    }

    /**
     * Retrieve Last update time
     *
     * @return int
     */
    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), self::CACHE_PREFIX."_".$this->getModule().'_lastcheck');
    }

    public function getVersion()
    {
      $v = (array)Mage::getConfig()->getNode('modules')->children();
      $module = implode("_",array_slice(explode("_",get_class($this)),0,2));
      $ver = (string)$v[$module]->version;

      return ($ver ? $ver : '0.0.1');
    }


    /**
     * Retrieve feed url
     *
     * @return string
     */
    public function getFeedUrl()
    {
        if (is_null($this->_feedUrl)) {
            $this->_feedUrl = 'http://' . Mage::getStoreConfig(self::FEED_URL_PATH).'?module='.$this->getModule().'&ver='.$this->getVersion();
        }
        return $this->_feedUrl;
    }

    /**
     * Retrieve feed data as XML element
     *
     * @return SimpleXMLElement
     */
    public function getFeedData()
    {
        $curl = new Varien_Http_Adapter_Curl();
        $curl->setConfig(array(
            'timeout'   => 2
        ));
        $curl->write(Zend_Http_Client::GET, $this->getFeedUrl(), '1.0');
        $data = $curl->read();
        if ($data === false) {
            return false;
        }
        $data = preg_split('/^\r?$/m', $data, 2);
        $data = trim($data[1]);
        $curl->close();

        try {
            $xml  = new SimpleXMLElement($data);
        }
        catch (Exception $e) {
            return false;
        }

        return $xml;
    }

}