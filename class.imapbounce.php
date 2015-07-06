<?php

require_once 'class.messagesimapbounce.php';

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
    /**
     * Connection class
     * @var object
     */
    public $connection;

    /**
     * Setting class
     * @var object
     */
    public $setting;

    /**
     * Messages class
     * @var object
     */
    public $messages;

    /******************
     * Private fields *
     ******************/

    /**
     * SMTP RFC standard line ending.
     */
    const CRLF = "\r\n";

    function __construct()
    {
        $this->messages = new MessagesImapBounce();
        $this->connection = $this->messages->connection;
        $this->setting = $this->connection->setting;
    }

}