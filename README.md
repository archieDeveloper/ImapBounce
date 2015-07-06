# ImapBounce
PHP parse bounced emails.

## A Simple Example

```php
<?php
require_once 'class.imapbounce.php';

// Create object ImapBounce
$imap = new ImapBounce;

// Changing default the settings
$imap->setting->set([
    'host' => 'localhost',
    'port' => 143,
    'protocols' => ['imap'],
    'user' => '',
    'password' => ''
]);

// Connection Imap server (You can transfer the settings directly here)
$imap->connection->connect();

// You can get some field settings
echo $imap->setting->getMailboxHost();

// And can get information about messages
echo $imap->messages->getCount();
print_r($imap->messages->getBounces());
```