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
use JetBrains\PhpStorm\Language;
use LogicException;
use Random\RandomException;

/**
 * Class Message.
 *
 * @package email
 */
class Message implements \Stringable
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
     * @var array<int,string> The filenames
     */
    protected array $attachments = [];
    /**
     * An associative array of attachments with Content-Disposition equals `inline`.
     *
     * @var array<string,string> The Content-ID's as keys and the filenames as values
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
     * An associative array used in the `To` header.
     *
     * @var array<string,string|null> The email addresses as keys and the optional
     * name as values
     */
    protected array $to = [];
    /**
     * An associative array used in the `Cc` header.
     *
     * @var array<string,string|null> The email addresses as keys and the optional
     * name as values
     */
    protected array $cc = [];
    /**
     * An associative array used in the `Bcc` header.
     *
     * @var array<string,string|null> The email addresses as keys and the optional
     * name as values
     */
    protected array $bcc = [];
    /**
     * An associative array used in the `Reply-To` header.
     *
     * @var array<string,string|null> The email addresses as keys and the optional
     * name as values
     */
    protected array $replyTo = [];
    /**
     * The values used in the `From` header.
     *
     * @var array<int,string|null> The email address as in the index 0 and the
     * optional name in the index 1
     */
    protected array $from = [];
    /**
     * The message Date.
     *
     * @var string|null
     */
    protected ?string $date = null;

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
     * @return array<int,string> The header lines
     */
    public function getHeaderLines() : array
    {
        $lines = [];
        foreach ($this->getHeaders() as $name => $value) {
            $lines[] = Header::getName($name) . ': ' . $value;
        }
        return $lines;
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
     * @return array<int,string> Array of filenames
     */
    public function getAttachments() : array
    {
        return $this->attachments;
    }

    /**
     * Add a filename to be attached.
     *
     * @param string $filename The filename
     *
     * @return static
     */
    public function addAttachment(string $filename) : static
    {
        $this->attachments[] = $filename;
        return $this;
    }

    /**
     * Set a filename to be attached inline (image).
     *
     * @param string $filename The filename
     * @param string $cid The Content-ID
     *
     * @return static
     */
    public function setInlineAttachment(string $filename, string $cid) : static
    {
        $this->inlineAttachments[$cid] = $filename;
        return $this;
    }

    /**
     * Get a lis of inline attachments.
     *
     * @return array<string,string> Content-IDs as keys and filenames as values
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
            if (!\is_file($attachment)) {
                throw new LogicException('Attachment file not found: ' . $attachment);
            }
            $filename = \pathinfo($attachment, \PATHINFO_BASENAME);
            $filename = \htmlspecialchars($filename, \ENT_QUOTES | \ENT_HTML5);
            $contents = \file_get_contents($attachment);
            $contents = \base64_encode($contents); // @phpstan-ignore-line
            $part .= '--mixed-' . $this->getBoundary() . $crlf;
            $part .= 'Content-Type: ' . $this->getContentType($attachment)
                . '; name="' . $filename . '"' . $crlf;
            $part .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $crlf;
            $part .= 'Content-Transfer-Encoding: base64' . $crlf . $crlf;
            $part .= \chunk_split($contents) . $crlf;
        }
        return $part;
    }

    protected function getContentType(string $filename) : string
    {
        return \mime_content_type($filename) ?: 'application/octet-stream';
    }

    protected function renderInlineAttachments() : string
    {
        $part = '';
        $crlf = $this->getCrlf();
        foreach ($this->getInlineAttachments() as $cid => $filename) {
            if (!\is_file($filename)) {
                throw new LogicException('Inline attachment file not found: ' . $filename);
            }
            $contents = \file_get_contents($filename);
            $contents = \base64_encode($contents); // @phpstan-ignore-line
            $part .= '--mixed-' . $this->getBoundary() . $crlf;
            $part .= 'Content-ID: ' . $cid . $crlf;
            $part .= 'Content-Type: ' . $this->getContentType($filename) . $crlf;
            $part .= 'Content-Disposition: inline' . $crlf;
            $part .= 'Content-Transfer-Encoding: base64' . $crlf . $crlf;
            $part .= \chunk_split($contents) . $crlf;
        }
        return $part;
    }

    /**
     * Set the Subject header.
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
     * Get the Subject header.
     *
     * @return string|null The header value or null if not set
     */
    public function getSubject() : ?string
    {
        return $this->getHeader(Header::SUBJECT);
    }

    /**
     * Add address and name in the To header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addTo(string $address, ?string $name = null) : static
    {
        $this->to[$address] = $name;
        $this->setHeader(Header::TO, static::formatAddressList($this->to));
        return $this;
    }

    /**
     * Get items of the To header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getTo() : array
    {
        return $this->to;
    }

    /**
     * Remove all items of the To header.
     *
     * @return static
     */
    public function removeTo() : static
    {
        $this->to = [];
        return $this;
    }

    /**
     * Add address and name in the Cc header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addCc(string $address, ?string $name = null) : static
    {
        $this->cc[$address] = $name;
        $this->setHeader(Header::CC, static::formatAddressList($this->cc));
        return $this;
    }

    /**
     * Get items of the Cc header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getCc() : array
    {
        return $this->cc;
    }

    /**
     * Remove all items of the Cc header.
     *
     * @return static
     */
    public function removeCc() : static
    {
        $this->cc = [];
        return $this;
    }

    /**
     * @return array<int,string>
     */
    public function getRecipients() : array
    {
        $recipients = \array_replace($this->getTo(), $this->getCc());
        return \array_keys($recipients);
    }

    /**
     * Add address and name in the Bcc header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addBcc(string $address, ?string $name = null) : static
    {
        $this->bcc[$address] = $name;
        $this->setHeader(Header::BCC, static::formatAddressList($this->bcc));
        return $this;
    }

    /**
     * Get items of the Bcc header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getBcc() : array
    {
        return $this->bcc;
    }

    /**
     * Remove all items of the Bcc header.
     *
     * @return static
     */
    public function removeBcc() : static
    {
        $this->bcc = [];
        return $this;
    }

    /**
     * Add address and name in the Reply-To header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function addReplyTo(string $address, ?string $name = null) : static
    {
        $this->replyTo[$address] = $name;
        $this->setHeader(Header::REPLY_TO, static::formatAddressList($this->replyTo));
        return $this;
    }

    /**
     * Get items of the Reply-To header.
     *
     * @return array<string,string|null> Emails as keys and names as values
     */
    public function getReplyTo() : array
    {
        return $this->replyTo;
    }

    /**
     * Remove all items of the Reply-To header.
     *
     * @return static
     */
    public function removeReplyTo() : static
    {
        $this->replyTo = [];
        return $this;
    }

    /**
     * Set the From header.
     *
     * @param string $address The email address
     * @param string|null $name The name or null to don't set
     *
     * @return static
     */
    public function setFrom(string $address, ?string $name = null) : static
    {
        $this->from = [$address, $name];
        $this->setHeader(Header::FROM, static::formatAddress($address, $name));
        return $this;
    }

    /**
     * Get the From header items.
     *
     * @return array<int,string|null> email address in key 0 and name in key 1
     */
    public function getFrom() : array
    {
        return $this->from;
    }

    /**
     * Get the email address of the From header.
     *
     * @return string|null The email or null if not set
     */
    public function getFromAddress() : ?string
    {
        return $this->from[0] ?? null;
    }

    /**
     * Get the name of the From header.
     *
     * @return string|null The name or null if not set
     */
    public function getFromName() : ?string
    {
        return $this->from[1] ?? null;
    }

    /**
     * Remove all items of the From header.
     *
     * @return static
     */
    public function removeFrom() : static
    {
        $this->from = [];
        return $this;
    }

    /**
     * Set the Date header.
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
     * Get the Date header.
     *
     * @return string|null The header value or null if not set
     */
    public function getDate() : ?string
    {
        return $this->getHeader(Header::DATE);
    }

    /**
     * Set the X-Priority header.
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
     * Get the X-Priority header.
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
     * Set the X-Mailer header.
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
     * Get the X-Mailer header.
     *
     * @return string|null The X-Mailer header or null
     */
    public function getXMailer() : ?string
    {
        return $this->getHeader(Header::X_MAILER);
    }

    protected static function formatAddress(string $address, ?string $name = null) : string
    {
        return $name !== null ? '"' . $name . '" <' . $address . '>' : $address;
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
