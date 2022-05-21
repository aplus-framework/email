Email
=====

.. image:: image.png
    :alt: Aplus Framework Email Library

Aplus Framework Email Library.

- `Installation`_
- `Sending Emails`_
- `Plain Message`_
- `HTML Message`_
- `Attachments`_
- `Headers`_
- `Mailer Connection`_
- `Conclusion`_

Installation
------------

The installation of this library can be done with Composer:

.. code-block::

    composer require aplus/email

Sending Emails
--------------

The process of sending messages by email follows the example code below.

.. code-block:: php

    use Framework\Email\Mailers\SMTPMailer;
    use Framework\Email\Message;

    // Set the mailer that will send the messages
    $mailer = new SMTPMailer('johndoe', 'p$$word');

    // The message is created
    $message = new Message();
    $message->setFrom('johndoe@domain.tld')
            ->addTo('mary@domain.tld')
            ->setPlainMessage('Hello, Mary! How are you?');

    // Try to send the message
    $sent = $mailer->send($message); // false or true

    if ($sent) {
        echo 'Message sent.';
    } else {
        echo 'The message was not sent.';
        print_r($mailer->getLogs()); // Show logs for debugging
    }

Plain Message
-------------

It is possible to set the plain text version of the message:

.. code-block:: php

    $message->setPlainMessage('Hello, John Doe!');

HTML Message
------------

It is also possible to set the message as HTML:

.. code-block:: php

    $message->setHtmlMessage('Hello, <b>John Doe</b>!');

Or both versions:

.. code-block:: php

    $message->setPlainMessage('Hello, John Doe!')
            ->setHtmlMessage('Hello, <b>John Doe</b>!');

Embed Images
############

When sending HTML messages it may be necessary to place images in the body of
the message.

This is done through an inline attachment with the **cid** in the *src*
attribute of the image:

.. code-block:: php

    $message->setHtmlMessage('Hello, <b>John Doe</b>!<br>
    See how beautiful the sky was today:
    <img src="cid:sky">');
    $message->setInlineAttachment(__DIR__ . '/blue-sky.png', 'sky')

Attachments
-----------

The other attachments can be added with the ``addAttachment`` method:

.. code-block:: php

    $message->addAttachment(__DIR__ . '/storage/invoice-1001.pdf');

Headers
-------

Message header fields can be set directly using the ``setHeader`` method:

.. code-block:: php

    $message->setHeader('Subject', 'How are you?')
            ->setHeader('From', 'johndoe@domain.tld')
            ->setHeader('To', 'mary@domain.tld');

.. code-block:: php

    use Framework\Email\Header;

    $message->setHeader(Header::SUBJECT, 'How are you?')
            ->setHeader(Header::FROM, 'johndoe@domain.tld')
            ->setHeader(Header::TO, 'mary@domain.tld');

Or through setters of the most used headers:

.. code-block:: php

    $message->setSubject('How are you?')
            ->setFrom('johndoe@domain.tld')
            ->addTo('mary@domain.tld');

X-Priority
##########

The X-Priority can be set as below:

.. code-block:: php

    use Framework\Email\XPriority

    $message->setXPriority(XPriority::HIGH);

Mailer Connection
-----------------

The default configs for connecting to the mail server are as follows:

.. code-block:: php

    use Framework\Email\Mailer\SMTPMailer;

    $config = [
        'host' => 'localhost',
        'port' => 587,
        'tls' => true,
        'username' => null,
        'password' => null,
        'charset' => 'utf-8',
        'crlf' => "\r\n",
        'connection_timeout' => 10,
        'response_timeout' => 5,
        'hostname' => gethostname(),
        'keep_alive' => false,
    ];

    $mailer = new SMTPMailer($config);

The **username** and **password** must be set.

The **port** is normally 25, 465 or 587. Check with your postmaster.

Keep Alive
##########

If you are going to send more than one message on the same connection, set
**keep_alive** to ``true``. 
This will use the same connection for all submissions.

Conclusion
----------

Aplus Email Library is an easy-to-use tool for, beginners and experienced, PHP developers. 
It is perfect for sending emails via SMTP in a very practical way. 
The more you use it, the more you will learn.

.. note::
    Did you find something wrong? 
    Be sure to let us know about it with an
    `issue <https://gitlab.com/aplus-framework/libraries/email/issues>`_. 
    Thank you!
