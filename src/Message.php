<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Email;

use DateTime;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Language;
use LogicException;
use Random\RandomException;
use Stringable;

/**
 * Class Message.
 *
 * @package email
 */
class Message implements Stringable
{
    /**
     * The Mailer instance.
     *
     * @var Mailer
     */
    protected Mailer $mailer;
    /**
     * The message boundary.
     *
     * @var string
     */
    protected string $boundary;
    /**
     * @var array<string,string>
     */
    protected array $headers = [
        'mime-version' => '1.0',
    ];
    /**
     * A list of attachments with Content-Disposition equals `attachment`.
     *
     * @var array<int,Attachment> The attachments
     */
    protected array $attachments = [];
    /**
     * An associative array of attachments with Content-Disposition equals `inline`.
     *
     * @var array<string,Attachment> The Content-ID's as keys and the Attachments as values
     */
    protected array $inlineAttachments = [];
    /**
     * The plain text message.
     *
     * @var string
     */
    protected string $plainMessage;
    /**
     * The HTML message.
     *
     * @var string
     */
    protected string $htmlMessage;

    /**
     * Render the Message as string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->renderData();
    }

    /**
     * Set the Mailer instance.
     *
     * @param Mailer $mailer The Mailer instance
     *
     * @return static
     */
    public function setMailer(Mailer $mailer) : static
    {
        $this->mailer = $mailer;
        return $this;
    }

    protected function getCrlf() : string
    {
        if (isset($this->mailer)) {
            return $this->mailer->getConfig('crlf');
        }
        return "\r\n";
    }

    protected function getCharset() : string
    {
        if (isset($this->mailer)) {
            return $this->mailer->getConfig('charset');
        }
        return 'utf-8';
    }

    /**
     * Set the boundary.
     *
     * @param string|null $boundary
     *
     * @throws RandomException
     *
     * @return static
     */
    public function setBoundary(?string $boundary = null) : static
    {
        $this->boundary = $boundary ?? \bin2hex(\random_bytes(16));
        return $this;
    }

    /**
     * Get the boundary.
     *
     * @throws RandomException
     *
     * @return string
     */
    public function getBoundary() : string
    {
        if (!isset($this->boundary)) {
            $this->setBoundary();
        }
        return $this->boundary;
    }

    /**
     * Remove a header.
     *
     * @param string $name The header name
     *
     * @return static
     */
    public function removeHeader(string $name) : static
    {
        unset($this->headers[\strtolower($name)]);
        return $this;
    }

    /**
     * Set a header.
     *
     * @param string $name The header name
     * @param string $value The header value
     *
     * @return static
     */
    public function setHeader(string $name, string $value) : static
    {
        $this->headers[\strtolower($name)] = $value;
        return $this;
    }

    /**
     * Get a header.
     *
     * @param string $name The header name
     *
     * @return string|null The header value or null if not set
     */
    public function getHeader(string $name) : ?string
    {
        return $this->headers[\strtolower($name)] ?? null;
    }

    /**
     * Get all headers set.
     *
     * @return array<string,string> The header names, in lowercase, as keys and
     * the values as values
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * Get header lines.
     *
     * @return array<string,string> The header names (lowercase) as keys and
     * lines as values
     */
    public function getHeaderLines() : array
    {
        $lines = [];
        foreach ($this->getHeaders() as $name => $value) {
            $value = $this->sanitizeSpaces($value);
            if ($name === 'subject') {
                $value = $this->encodeSubject($value);
            }
            $lines[$name] = Header::getName($name) . ': ' . $value;
        }
        return $lines;
    }

    protected function encodeSubject(string $subject) : string
    {
        return '=?UTF-8?B?' . \base64_encode($subject) . '?=';
    }

    protected function renderHeaders() : string
    {
        return \implode($this->getCrlf(), $this->getHeaderLines());
    }

    protected function prepareHeaders() : void
    {
        if (!$this->getDate()) {
            $this->setDate();
        }
        $multipart = $this->getInlineAttachments() ? 'related' : 'mixed';
        $this->setHeader(
            Header::CONTENT_TYPE,
            'multipart/' . $multipart . '; boundary="mixed-' . $this->getBoundary() . '"'
        );
    }

    protected function renderData() : string
    {
        $boundary = $this->getBoundary();
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf . $crlf;
        $data .= '--mixed-' . $boundary . $crlf;
        $data .= 'Content-Type: multipart/alternative; boundary="alt-' . $boundary . '"'
            . $crlf . $crlf;
        $data .= $this->renderPlainMessage();
        $data .= $this->renderHtmlMessage();
        $data .= '--alt-' . $boundary . '--' . $crlf . $crlf;
        $data .= $this->renderAttachments();
        $data .= $this->renderInlineAttachments();
        $data .= '--mixed-' . $boundary . '--';
        return $data;
    }

    /**
     * Set the text/plain message.
     *
     * @param string $message The text/plain message
     *
     * @return static
     */
    public function setPlainMessage(string $message) : static
    {
        $this->plainMessage = $message;
        return $this;
    }

    /**
     * Get the text/plain message.
     *
     * @return string|null The message or null if not set
     */
    public function getPlainMessage() : ?string
    {
        return $this->plainMessage ?? null;
    }

    protected function renderPlainMessage() : ?string
    {
        $message = $this->getPlainMessage();
        return $message !== null ? $this->renderMessage($message, 'text/plain') : null;
    }

    /**
     * Alias of {@see Framework\Email\Message::setHtmlMessage()}.
     *
     * @param string $body The text/html message
     *
     * @return static
     */
    public function setBody(#[Language('HTML')] string $body) : static
    {
        return $this->setHtmlMessage($body);
    }

    /**
     * Alias of {@see Framework\Email\Message::getHtmlMessage()}.
     *
     * @return string|null The text/html message or null if not set
     */
    public function getBody() : ?string
    {
        return $this->getHtmlMessage();
    }

    /**
     * Set the text/html message.
     *
     * @param string $message The text/html message
     *
     * @return static
     */
    public function setHtmlMessage(#[Language('HTML')] string $message) : static
    {
        $this->htmlMessage = $message;
        return $this;
    }

    /**
     * Get the text/html message.
     *
     * @return string|null The text/html message or null if not set
     */
    public function getHtmlMessage() : ?string
    {
        return $this->htmlMessage ?? null;
    }

    protected function renderHtmlMessage() : ?string
    {
        $message = $this->getHtmlMessage();
        return $message !== null ? $this->renderMessage($message) : null;
    }

    protected function renderMessage(
        string $message,
        string $contentType = 'text/html'
    ) : string {
        $message = \base64_encode($message);
        $crlf = $this->getCrlf();
        $part = '--alt-' . $this->getBoundary() . $crlf;
        $part .= 'Content-Type: ' . $contentType . '; charset='
            . $this->getCharset() . $crlf;
        $part .= 'Content-Transfer-Encoding: base64' . $crlf . $crlf;
        $part .= \chunk_split($message) . $crlf;
        return $part;
    }

    /**
     * Get a lis of attachments.
     *
     * @return array<int,Attachment> Array of Attachments
     */
    public function getAttachments() : array
    {
        return $this->attachments;
    }

    /**
     * Add an attachment.
     *
     * @param string $filename The filename
     * @param string|null $name The name
     * @param string|null $mimeType The MIME type
     *
     * @return static
     */
    public function addAttachment(
        string $filename,
        ?string $name = null,
        ?string $mimeType = null
    ) : static {
        $this->attachments[] = new Attachment($filename, $name, $mimeType);
        return $this;
    }

    /**
     * Set a filename to be attached inline (image).
     *
     * @param string $filename The filename
     * @param string $cid The Content-ID
     * @param string|null $mimeType The MIME type
     *
     * @return static
     */
    public function setInlineAttachment(
        string $filename,
        string $cid,
        ?string $mimeType = null
    ) : static {
        $this->inlineAttachments[$cid] = new Attachment($filename, mimeType: $mimeType);
        return $this;
    }

    /**
     * Get a lis of inline attachments.
     *
     * @return array<string,Attachment> Content-IDs as keys and Attachments as values
     */
    public function getInlineAttachments() : array
    {
        return $this->inlineAttachments;
    }

    protected function renderAttachments() : string
    {
        $part = '';
        $crlf = $this->getCrlf();
        foreach ($this->getAttachments() as $attachment) {
            $part .= '--mixed-' . $this->getBoundary() . $crlf;
            $part .= 'Content-Type: ' . $attachment->getMimeType()
                . '; name="' . $attachment->getName() . '"' . $crlf;
            $part .= 'Content-Disposition: attachment; filename="' . $attachment->getName() . '"' . $crlf;
            $part .= 'Content-Transfer-Encoding: base64' . $crlf . $crlf;
            $part .= $attachment->getBase64SplitContents() . $crlf;
        }
        return $part;
    }

    protected function renderInlineAttachments() : string
    {
        $part = '';
        $crlf = $this->getCrlf();
        foreach ($this->getInlineAttachments() as $cid => $attachment) {
            $part .= '--mixed-' . $this->getBoundary() . $crlf;
            $part .= 'Content-ID: ' . $cid . $crlf;
            $part .= 'Content-Type: ' . $attachment->getMimeType() . $crlf;
            $part .= 'Content-Disposition: inline' . $crlf;
            $part .= 'Content-Transfer-Encoding: base64' . $crlf . $crlf;
            $part .= $attachment->getBase64SplitContents() . $crlf;
        }
        return $part;
    }

    /**
     * Set the 'Subject' header.
     *
     * @param string $subject The header value
     *
     * @return static
     */
    public function setSubject(string $subject) : static
    {
        $this->setHeader(Header::SUBJECT, $subject);
        return $this;
    }

    /**
     * Get the 'Subject' header.
     *
     * @return string|null The header value or null if not set
     */
    public function getSubject() : ?string
    {
        return $this->getHeader(Header::SUBJECT);
    }

    /**
     * Add address and name in the 'To' header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addTo(string $address, ?string $name = null) : static
    {
        $list = $this->getTo();
        $list[$address] = $name;
        $this->setHeader(Header::TO, static::formatAddressList($list));
        return $this;
    }

    /**
     * Get items of the 'To' header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getTo() : array
    {
        return $this->extractEmails($this->getHeader(Header::TO));
    }

    /**
     * Remove all items of the 'To' header.
     *
     * @return static
     */
    public function removeTo() : static
    {
        $this->removeHeader(Header::TO);
        return $this;
    }

    /**
     * Add address and name in the 'Cc' header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addCc(string $address, ?string $name = null) : static
    {
        $list = $this->getCc();
        $list[$address] = $name;
        $this->setHeader(Header::CC, static::formatAddressList($list));
        return $this;
    }

    /**
     * Get items of the 'Cc' header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getCc() : array
    {
        return $this->extractEmails($this->getHeader(Header::CC));
    }

    /**
     * Remove all items of the 'Cc' header.
     *
     * @return static
     */
    public function removeCc() : static
    {
        $this->removeHeader(Header::CC);
        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getRecipients() : array
    {
        $recipients = \array_replace($this->getTo(), $this->getCc(), $this->getBcc());
        return \array_keys($recipients);
    }

    /**
     * Add address and name in the 'Bcc' header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addBcc(string $address, ?string $name = null) : static
    {
        $list = $this->getBcc();
        $list[$address] = $name;
        $this->setHeader(Header::BCC, static::formatAddressList($list));
        return $this;
    }

    /**
     * Get items of the 'Bcc' header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getBcc() : array
    {
        return $this->extractEmails($this->getHeader(Header::BCC));
    }

    /**
     * Remove all items of the 'Bcc' header.
     *
     * @return static
     */
    public function removeBcc() : static
    {
        $this->removeHeader(Header::BCC);
        return $this;
    }

    /**
     * Add address and name in the 'Reply-To' header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addReplyTo(string $address, ?string $name = null) : static
    {
        $list = $this->getReplyTo();
        $list[$address] = $name;
        $this->setHeader(Header::REPLY_TO, static::formatAddressList($list));
        return $this;
    }

    /**
     * Get items of the 'Reply-To' header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getReplyTo() : array
    {
        return $this->extractEmails($this->getHeader(Header::REPLY_TO));
    }

    /**
     * Remove all items of the 'Reply-To' header.
     *
     * @return static
     */
    public function removeReplyTo() : static
    {
        $this->removeHeader(Header::REPLY_TO);
        return $this;
    }

    /**
     * Set the 'From' header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function setFrom(string $address, ?string $name = null) : static
    {
        $this->setHeader(Header::FROM, static::formatAddress($address, $name));
        return $this;
    }

    /**
     * Get the 'From' header items.
     *
     * @return array<string,string|null> Two keys: address and name
     */
    #[ArrayShape(['address' => 'string', 'name' => 'string|null'])]
    public function getFrom() : array
    {
        $from = $this->extractEmails($this->getHeader(Header::FROM));
        if (empty($from)) {
            return [];
        }
        return [
            'address' => \array_key_first($from),
            'name' => \array_first($from),
        ];
    }

    /**
     * Get the email address of the 'From' header.
     *
     * @return string|null The email or null if not set
     */
    public function getFromAddress() : ?string
    {
        return $this->getFrom()['address'] ?? null;
    }

    /**
     * Get the name of the 'From' header.
     *
     * @return string|null The name or null if not set
     */
    public function getFromName() : ?string
    {
        return $this->getFrom()['name'] ?? null;
    }

    /**
     * Remove all items of the 'From' header.
     *
     * @return static
     */
    public function removeFrom() : static
    {
        $this->removeHeader(Header::FROM);
        return $this;
    }

    /**
     * Set the 'Date' header.
     *
     * @param DateTime|null $datetime A custom DateTime or null to set the
     * current datetime
     *
     * @return static
     */
    public function setDate(?DateTime $datetime = null) : static
    {
        $date = $datetime ? $datetime->format('r') : \date('r');
        $this->setHeader(Header::DATE, $date);
        return $this;
    }

    /**
     * Get the 'Date' header.
     *
     * @return string|null The header value or null if not set
     */
    public function getDate() : ?string
    {
        return $this->getHeader(Header::DATE);
    }

    /**
     * Set the 'X-Priority' header.
     *
     * @param XPriority $priority The {@see XPriority} case
     *
     * @return static
     */
    public function setXPriority(XPriority $priority) : static
    {
        $this->setHeader(Header::X_PRIORITY, (string) $priority->value);
        return $this;
    }

    /**
     * Get the 'X-Priority' header.
     *
     * @return XPriority|null The {@see XPriority} case or null
     */
    public function getXPriority() : ?XPriority
    {
        $header = $this->getHeader(Header::X_PRIORITY);
        if ($header === null) {
            return null;
        }
        return XPriority::from((int) $header);
    }

    /**
     * Set the 'X-Mailer' header.
     *
     * @param string|null $xMailer The X-Mailer header or null to set the default
     *
     * @return static
     */
    public function setXMailer(?string $xMailer = null) : static
    {
        $xMailer ??= 'Aplus Mailer';
        $this->setHeader(Header::X_MAILER, $xMailer);
        return $this;
    }

    /**
     * Get the 'X-Mailer' header.
     *
     * @return string|null The X-Mailer header or null
     */
    public function getXMailer() : ?string
    {
        return $this->getHeader(Header::X_MAILER);
    }

    public function validate() : void
    {
        $from = $this->getFromAddress();
        if ($this->isNullOrEmptyString($from)) {
            throw new LogicException("The message 'From' address is empty");
        }
        if (!\filter_var($from, \FILTER_VALIDATE_EMAIL)) {
            throw new LogicException("The message 'From' address '{$from}' is not a valid email");
        }
        if (empty($this->getTo())) {
            throw new LogicException("The message 'To' address is empty");
        }
        if ($this->isNullOrEmptyString($this->getSubject())) {
            throw new LogicException("The message 'Subject' is empty");
        }
        if ($this->isNullOrEmptyString($this->getPlainMessage())
            && $this->isNullOrEmptyString($this->getHtmlMessage())
        ) {
            throw new LogicException('The message body is empty');
        }
    }

    protected function isNullOrEmptyString(?string $value) : bool
    {
        if ($value === null) {
            return true;
        }
        if ($value === '') {
            return true;
        }
        return false;
    }

    /**
     * Extract emails (addresses and names) from an emails header string.
     *
     * @param string|null $header An header like: `foo@bar, "Baz" <foo@baz>`
     *
     * @return array<string,string|null> Addresses as keys and names
     * (string or null) as values
     */
    protected function extractEmails(?string $header) : array
    {
        if ($header === null) {
            return [];
        }
        $exploded = \explode(',', $header);
        foreach ($exploded as &$part) {
            $part = \trim($part);
        }
        unset($part);
        $emails = [];
        foreach ($exploded as $part) {
            if (\str_starts_with($part, '"')) {
                $extracted = $this->extractAddressAndName($part);
                $emails[$extracted['address']] = $extracted['name'];
                continue;
            }
            $part = $this->sanitizeSpaces($part);
            $part = $this->removeSpaces($part);
            $emails[$part] = null;
        }
        return $emails;
    }

    /**
     * Replace whitespaces with one space.
     *
     * @param string $string
     *
     * @return string
     */
    protected function sanitizeSpaces(string $string) : string
    {
        $string = \preg_replace('/\s+/', ' ', $string);
        return \trim($string);
    }

    /**
     * Remove spaces.
     *
     * @param string $string
     *
     * @return string
     */
    protected function removeSpaces(string $string) : string
    {
        return \strtr($string, [' ' => '']);
    }

    /**
     * Extract address and name from a header part.
     *
     * @param string $headerPart A header part like: `"Baz" <foo@baz>`
     *
     * @return array<string,string>
     */
    #[ArrayShape(['address' => 'string', 'name' => 'string'])]
    protected function extractAddressAndName(string $headerPart) : array
    {
        $headerPart = $this->sanitizeSpaces($headerPart);
        $headerPart = \strtr($headerPart, ['"<' => '" <']);
        \preg_match_all('#\"(.*?)\" <(.*?)>#', $headerPart, $matches);
        $name = \trim($matches[1][0]);
        $address = \trim($matches[2][0]);
        $address = $this->removeSpaces($address);
        return [
            'address' => $address,
            'name' => $name,
        ];
    }

    protected static function formatAddress(string $address, ?string $name = null) : string
    {
        return $name !== null
            ? '"' . $name . '" <' . $address . '>'
            : $address;
    }

    /**
     * @param array<string,string|null> $addresses
     *
     * @return string
     */
    protected static function formatAddressList(array $addresses) : string
    {
        $data = [];
        foreach ($addresses as $address => $name) {
            $data[] = static::formatAddress($address, $name);
        }
        return \implode(', ', $data);
    }
}
