# Email Library *documentation*

Emailing usually happens in logic similar to this:

```php
use Framework\Email\SMTP;
use Framework\Email\Message;

$mailer = new SMTP('user@mail.com', 'password');
$message = new Message($mailer);
$message->addTo('joe@mail.com', 'Joe');
$message->setPlainMessage('Hello, Bro!');
$sent = $mailer->send($message);
if ($sent){
    echo 'Message successful sent';
} else {
    echo 'Message could not be sent:';
    print_r($mailer->getLogs());
}
```

In just a few lines of code, the message can be sent.

## Mailer

The Mailer class must be loaded passing configuration data:

```php
$mailer = new \Framework\Email\SMTP([
    'username' => 'user@mail.com',
    'password' => 'password',
    'server' => 'smtp.mail.com',
    'port' => 587,
]);
```

And the message is sent with the `send` method:

```php
$sent = $mailer->send($message);
```

## Message

The message must be instantiated with Mailer as a dependency.

Then, in it, the recipients, the plain and the HTML messages, and other data:

```php
$message = new \Framework\Email\Message($mailer);
$message->setFrom('user@mail.com');
$message->setPlainMessage('Hello, Joe!');
$message->setHTMLMessage('Hello, <b>Joe</b>!');
$sent = $mailer->send($message); // Send the message
```

A message can have multiple attachments, sent separately or inline for images.

```php
$message->addAttachment('path/to/file');
$message->setInlineAttachment(__DIR__ . '/image.png', 'abc123');
```
