<?php

/**
 * ImapBounce - PHP parse bounced emails.
 * PHP Version 5
 * @package ImapBounce
 * @link https://github.com/archieDeveloper/ImapBounce/ The ImapBounce GitHub project
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 * @copyright 2015, Arkady Kozhedub
 * @license GPL licensed
 */

class ImapBounce
{
    public $config = [
        'subject' => 'Delivery Status Notification (Failure)',
        'from' => 'Mail Delivery Subsystem <mailer-daemon@googlemail.com>'
    ];

    /******************
     * Private fields *
     ******************/

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

    /**
     * Headers all messages
     * @var array
     */
    private $_headersMessages = null;

    /**
     * Bounce messages
     * @var array
     */
    private $_bounceMessages = null;

    /**
     * Count messages in one task
     * @var integer
     */
    private $_countMessagesInTask = 500;

    /**
     * Count iteration for cycle
     * @var integer
     */
    private $_countIteration = null;

    /**
     * Count messages for last iteration
     * @var integer
     */
    private $_lastIteration = null;

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

    function __construct()
    {
        # code...
    }

    /**
     * Authorization in Imap service
     * @param array $options setting authorization
     * @access public
     * @return bool
     * @throws Exception
     */
    public function authorization(array $options = array())
    {
        $this->settingMailbox($options);
        if($this->_connection == null) {
            $this->_connection = $this->_connect();
        }
        if($this->_connection) {
            return true;
        }
        return false;
    }

    /**
     * Setting mailbox
     * @param array $options setting authorization
     * @access public
     * @return void
     */
    public function settingMailbox(array $options)
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
     * Get all bounce messages
     * @access public
     * @return array|boolean
     */
    public function getBounceMessages()
    {
        if ($this->_bounceMessages != null) {
            return $this->_bounceMessages;
        }
        $allHeaders = $this->getAllHeaders();
        if($allHeaders) {
            $bounceMessages = [];
            foreach($allHeaders as $messageHeader) {
                if(
                    (isset($messageHeader->subject))
                    && (isset($messageHeader->from))
                    && ($messageHeader->subject == $this->config['subject'])
                    && ($messageHeader->from == $this->config['from'])
                ){
                    $bodyMessage = imap_body($this->_connection,$messageHeader->msgno);

                    $email = $this->_findField($bodyMessage, "Delivery to the following recipient failed permanently:","Technical details of permanent failure:");
                    $message = $this->_findField($bodyMessage, "Technical details of permanent failure:", "----- Original message -----");

                    array_push($bounceMessages, [
                        'email' => $email,
                        'message' => $message
                    ]);
                }
            }
            return $bounceMessages;
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
            '{'.$this->_mailbox_host.':'.$this->_mailbox_port.'/'.implode('/', $this->_mailbox_protocols).'}'.$this->_mailbox_type,
            $this->_mailbox_user,
            $this->_mailbox_password
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
            if($this->authorization()) {
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