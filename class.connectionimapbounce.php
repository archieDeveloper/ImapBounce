<?php

require_once 'class.settingimapbounce.php';

/**
 * ImapBounce - PHP parse bounced emails.
 * PHP Version 5
 * @package ConnectionImapBounce
 * @link https://github.com/archieDeveloper/ImapBounce/ The ImapBounce GitHub project
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 * @copyright 2015, Arkady Kozhedub
 * @license GPL licensed
 */

class ConnectionImapBounce
{

    /**
     * Setting mailbox
     * @var object
     */
    public $setting;

    /**
     * Resource authorization
     * @var resource
     */
    private $_connection = null;

    /**
     * Information authorization
     * @var object
     */
    private $_connectionCheck = null;

    function __construct()
    {
        $this->setting = new SettingImapBounce();
    }

    /**
     * Authorization in Imap service
     * @param array $options setting authorization
     * @access public
     * @return bool
     * @throws Exception
     */
    public function connect(array $options = array())
    {
        $this->setting->set($options);
        if($this->_connection == null) {
            $this->_connection = $this->_connect();
        }
        if($this->_connection) {
            return true;
        }
        return false;
    }

    /**
     * Get information about authorization
     * @access public
     * @return object|bool
     */
    public function getImapCheck()
    {
        if($this->_connectionCheck != null) {
            return $this->_connectionCheck;
        }
        if($this->isAuth()){
            return imap_check($this->_connection);
        }
        return false;
    }

    /**
     * Is authorization?
     * @access public
     * @return boolean
     */
    public function isAuth()
    {
        try {
            if($this->connect()) {
                return true;
            }
        } catch (Exception $e) {
            echo 'Error: ',  $e->getMessage(), self::CRLF;
            return false;
        }
    }

    /**
     * Disconnect from Imap
     * @access public
     * @return void
     */
    public function disconnect()
    {
        $this->_disconnect();
    }

    /********************
     * Private methods *
     ********************/

    /**
     * Connect to a Imap server
     * @access private
     * @return resource
     */
    private function _connect()
    {
        return imap_open(
            '{'.$this->setting->getMailboxHost().':'.$this->setting->getMailboxPort().'/'.implode('/', $this->setting->getMailboxProtocols()).'}'.$this->setting->getMailboxType(),
            $this->setting->getMailboxUser(),
            $this->setting->getMailboxPassword()
        );
    }

    /**
     * Disconnect to Imap
     * @access private
     * @return boolean
     */
    private function _disconnect()
    {
        if($this->isAuth()){
            return imap_close($this->_connection);
        }
        return false;
    }
}