<?php

/**
 * ImapBounce - PHP parse bounced emails.
 * PHP Version 5
 * @package SettingImapBounce
 * @link https://github.com/archieDeveloper/ImapBounce/ The ImapBounce GitHub project
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 * @copyright 2015, Arkady Kozhedub
 * @license GPL licensed
 */

class SettingImapBounce
{

    /**
     * Mailbox host (default - localhost)
     * @var string
     */
    private $_mailbox_host = 'localhost';

    /**
     * Mailbox port (default - 143)
     * @var integer
     */
    private $_mailbox_port = 143;

    /**
     * Mailbox protocols (default - ['imap'])
     * @var array
     */
    private $_mailbox_protocols = ['imap'];

    /**
     * Mailbox type (default - INBOX)
     * @var string
     */
    private $_mailbox_type = 'INBOX';

    /**
     * Mailbox username
     * @var string
     */
    private $_mailbox_user = '';

    /**
     * Mailbox password
     * @var string
     */
    private $_mailbox_password = '';

    /**
     * SMTP RFC standard line ending.
     */
    const CRLF = "\r\n";

    /**
     * Setting mailbox
     * @param array $options setting authorization
     * @access public
     * @return void
     */
    public function set(array $options)
    {
        if (isset($options) && is_array($options)) {
            foreach ($options as $field => $value) {
                $methodName = 'setMailbox'.ucfirst($field);
                if(method_exists($this, $methodName)) {
                    $this->$methodName($value);
                } else {
                    echo 'Не существующая настройка: '.$field, self::CRLF;
                }
            }
        }
    }

    /**
     * Get mailbox host
     * @access public
     * @return string
     */
    public function getMailboxHost()
    {
        return $this->_mailbox_host;
    }

    /**
     * Get mailbox port
     * @access public
     * @return integer
     */
    public function getMailboxPort()
    {
        return $this->_mailbox_port;
    }

    /**
     * Get mailbox protocols
     * @access public
     * @return array
     */
    public function getMailboxProtocols()
    {
        return $this->_mailbox_protocols;
    }

    /**
     * Get mailbox type
     * @access public
     * @return string
     */
    public function getMailboxType()
    {
        return $this->_mailbox_type;
    }

    /**
     * Get mailbox user
     * @access public
     * @return string
     */
    public function getMailboxUser()
    {
        return $this->_mailbox_user;
    }

    /**
     * Get mailbox password
     * @access public
     * @return string
     */
    public function getMailboxPassword()
    {
        return $this->_mailbox_password;
    }

    /**
     * Set mailbox host
     * @access public
     * @param string $host
     * @return void
     */
    public function setMailboxHost($host)
    {
        $this->_mailbox_host = (string)$host;
    }

    /**
     * Set mailbox port
     * @access public
     * @param integer $port
     * @return void
     */
    public function setMailboxPort($port)
    {
        $this->_mailbox_port = (integer)$port;
    }

    /**
     * Set mailbox protocols
     * @access public
     * @param array $protocols
     * @return void
     */
    public function setMailboxProtocols($protocols)
    {
        $this->_mailbox_protocols = (array)$protocols;
    }

    /**
     * Set mailbox type
     * @access public
     * @param string $type
     * @return void
     */
    public function setMailboxType($type)
    {
        $this->_mailbox_type = (string)$type;
    }

    /**
     * Set mailbox user
     * @access public
     * @param string $user
     * @return void
     */
    public function setMailboxUser($user)
    {
        $this->_mailbox_user = (string)$user;
    }

    /**
     * Set mailbox password
     * @access public
     * @param string $password
     * @return void
     */
    public function setMailboxPassword($password)
    {
        $this->_mailbox_password = (string)$password;
    }
}