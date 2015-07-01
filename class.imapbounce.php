<?php

/**
 * ImapBounce - PHP parse bounced emails.
 * PHP Version 5
 * @package ImapBounce
 * @link https://github.com/archieDeveloper/ImapBounce/ The ImapBounce GitHub project
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 * @copyright 2015 Arkady Kozhedub
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @note This program is distributed in the hope that it will be useful - WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.
 */
/**
 * ImapBounce - PHP parse bounced emails.
 * @package ImapBounce
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 */
class ImapBounce
{
    public $config = [
        'subject' => 'Delivery Status Notification (Failure)',
        'from' => 'Mail Delivery Subsystem <mailer-daemon@googlemail.com>',

        'host' => false,
        'port' => false,
        'protocols' => false,
        'user' => false,
        'password' => false
    ];

    /**
     * Resource authorization
     * @type resource
     */
    private $_connection = null;

    /**
     * Information authorization
     * @type object
     */
    private $_connectionCheck = null;

    /**
     * Headers all messages
     * @type array
     */
    private $_headersMessages = null;

    /**
     * Count messages in one task
     * @type integer
     */
    private $_countMessagesInTask = 500;

    /**
     * Count iteration for cycle
     * @type integer
     */
    private $_countIteration = null;

    /**
     * Count messages for last iteration
     * @type integer
     */
    private $_lastIteration = null;

    /**
     * SMTP RFC standard line ending.
     */
    const CRLF = "\r\n";

    function __construct()
    {
        # code...
    }

    /**
     * Auth in Imap service
     * @param string $host Host
     * @param string $port Port
     * @param array $protocols Protocols for connecting
     * @param string $user User
     * @param string $password Password
     * @access public
     * @return bool
     * @throws Exception
     */
    public function auth($host,$port,$protocols,$user,$password)
    {
        if(!$host || !$port || !$protocols || !$user || !$password) {
            throw new Exception('Not authorized imap!');
        }
        if($this->_connection == null) {
            $this->_connection = $this->_connect($host,$port,$protocols,$user,$password);
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
        if($this->_isAuth()){
            return imap_check($this->_connection);
        }
        return false;
    }

    /**
     * Get count all messages
     * @access public
     * @return integer|boolean
     */
    public function getCountMessages()
    {
        $a = $this->getImapCheck();
        if($a) {
            return $a->Nmsgs;
        }
        if($this->_isAuth()){
            return imap_num_msg($this->_connection);
        }
        return false;
    }

    /**
     * Get all headers messages
     * @access public
     * @return array|boolean
     */
    public function getAllHeaders()
    {
        if ($this->_headersMessages != null) {
            return $this->_headersMessages;
        }
        $this->_calculateTasks();
        $result = [];
        for($i = 0; $i <= $this->_countIteration; $i++) {
            $openCursor = $i*$this->_countMessagesInTask+1;
            $closeCursor = $openCursor-1;
            if ($i != $this->_countIteration) {
                $closeCursor += $this->_countMessagesInTask;
            } else {
                $closeCursor += $this->_lastIteration;
            }
            echo 'Task '.($i+1).': '.$openCursor.' - '.$closeCursor." [".round(($i/$this->_countIteration)*100, 2)."%]", self::CRLF;
            if($this->_isAuth()){
                $result = array_merge($result, imap_fetch_overview($this->_connection,"".($openCursor).":".$closeCursor."",0));
                continue;
            }
            return false;
        }
        $this->_headersMessages = $result;
        return $result;
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

    /****************************************************************
     *                       ПРИВАТНЫЕ МЕТОДЫ                       *
     ****************************************************************/

    /**
     * Connect to a Imap server
     * @access private
     * @return resource
     */
    private function _connect($host,$port,$protocols,$user,$password)
    {
        $this->config['host'] = $host;
        $this->config['port'] = $port;
        $this->config['protocols'] = $protocols;
        $this->config['user'] = $user;
        $this->config['password'] = $password;
        return imap_open(
            '{'.$host.':'.$port.'/'.implode('/', $protocols).'}',
            $user,
            $password
        );
    }

    /**
     * Calculate count iteration and count messages in the last
     * @access private
     * @return void
     */
    private function _calculateTasks()
    {
        $this->_countIteration = round($this->getCountMessages()/$this->_countMessagesInTask,0, PHP_ROUND_HALF_UP);
        $this->_lastIteration = $this->getCountMessages()-($this->_countIteration*$this->_countMessagesInTask);
    }

    /**
     * Is authorization?
     * @access private
     * @return boolean
     */
    private function _isAuth()
    {
        try {
            if($this->auth(
                $this->config['host'],
                $this->config['port'],
                $this->config['protocols'],
                $this->config['user'],
                $this->config['password']
            )) {
                return true;
            }
        } catch (Exception $e) {
            echo 'Error: ',  $e->getMessage(), self::CRLF;
            return false;
        }
    }

    /**
     * Find the field in the message body
     * @param string $message Message
     * @param string $startString Start string for crop
     * @param string $endString End string for crop
     * @access private
     * @return string
     */
    private function _findField($message, $startString, $endString)
    {
        $beginCrop = substr($message,strpos($message, $startString)+strlen($startString));
        $email = substr($beginCrop,0,strpos($beginCrop, $endString));
        $trimEmail = trim($email);
        return $trimEmail;
    }

    /**
     * Disconnect to Imap
     * @access private
     * @return boolean
     */
    private function _disconnect()
    {
        if($this->_isAuth()){
            return imap_close($this->_connection);
        }
        return false;
    }
}