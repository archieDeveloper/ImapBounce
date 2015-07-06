<?php

require_once 'class.connectionimapbounce.php';

/**
 * ImapBounce - PHP parse bounced emails.
 * PHP Version 5
 * @package ImapBounce
 * @link https://github.com/archieDeveloper/ImapBounce/ The ImapBounce GitHub project
 * @author Arkady Kozhedub (archie) <arkadij.ok@gmail.com>
 * @copyright 2015, Arkady Kozhedub
 * @license GPL licensed
 */

class MessagesImapBounce
{

    public $config = [
        'subject' => 'Delivery Status Notification (Failure)',
        'from' => 'Mail Delivery Subsystem <mailer-daemon@googlemail.com>'
    ];

    /**
     * Connection mailbox
     * @var object
     */
    public $connection;

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
     * SMTP RFC standard line ending.
     */
    const CRLF = "\r\n";

    function __construct()
    {
        $this->connection = new ConnectionImapBounce();
    }

    /**
     * Get count all messages
     * @access public
     * @return integer|boolean
     */
    public function getCount()
    {
        $a = $this->connection->getImapCheck();
        if($a) {
            return $a->Nmsgs;
        }
        if($this->connection->isAuth()){
            return imap_num_msg($this->connection->getResource());
        }
        return false;
    }

    /**
     * Get all headers messages
     * @access public
     * @return array|boolean
     */
    public function getHeaders()
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
            if($this->connection->isAuth()){
                $result = array_merge($result, imap_fetch_overview($this->connection->getResource(),"".($openCursor).":".$closeCursor."",0));
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
    public function getBounces()
    {
        if ($this->_bounceMessages != null) {
            return $this->_bounceMessages;
        }
        $allHeaders = $this->getHeaders();
        if($allHeaders) {
            $bounceMessages = [];
            foreach($allHeaders as $messageHeader) {
                if(
                    (isset($messageHeader->subject))
                    && (isset($messageHeader->from))
                    && ($messageHeader->subject == $this->config['subject'])
                    && ($messageHeader->from == $this->config['from'])
                ){
                    $bodyMessage = imap_body($this->connection->getResource(),$messageHeader->msgno);

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
     * Calculate count iteration and count messages in the last
     * @access private
     * @return void
     */
    private function _calculateTasks()
    {
        $this->_countIteration = round($this->getCount()/$this->_countMessagesInTask,0, PHP_ROUND_HALF_UP);
        $this->_lastIteration = $this->getCount()-($this->_countIteration*$this->_countMessagesInTask);
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
        $field = substr($beginCrop,0,strpos($beginCrop, $endString));
        $trimField = trim($field);
        return $trimField;
    }
}