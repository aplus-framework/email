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
use LogicException;

/**
 * Class Message.
 *
 * @package email
 */
class Message
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
     * The message X-Priority.
     *
     * @var int An integer from 1 to 5
     */
    protected int $xPriority;
    /**
     * An associative array of Standard Headers.
     *
     * @var array<string,string> The lowercased names as keys and the Standard
     * Header name as values
     */
    protected static array $standardHeaders = [
        'bcc' => 'Bcc',
        'cc' => 'Cc',
        'content-type' => 'Content-Type',
        'date' => 'Date',
        'from' => 'From',
        'mime-version' => 'MIME-Version',
        'reply-to' => 'Reply-To',
        'subject' => 'Subject',
        'to' => 'To',
        'x-priority' => 'X-Priority',
    ];

    public function __toString() : string
    {
        return $this->renderData();
    }

    /**
     * @param Mailer $mailer The Mailer instance
     *
     * @return static
     */
    public function setMailer(Mailer $mailer) : static
    {
        $this->mailer = $mailer;
        return $this;
    }

    public function setBoundary(string $boundary = null) : static
    {
        $this->boundary = $boundary ?? \bin2hex(\random_bytes(16));
        return $this;
    }

    public function getBoundary() : string
    {
        if ( ! isset($this->boundary)) {
            $this->setBoundary();
        }
        return $this->boundary;
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return static
     */
    public function setHeader(string $name, string $value) : static
    {
        $this->headers[\strtolower($name)] = $value;
        return $this;
    }

    public function getHeader(string $name) : ?string
    {
        return $this->headers[\strtolower($name)] ?? null;
    }

    /**
     * @return array<string,string>
     */
    public function getHeaders() : array
    {
        return $this->headers;
    }

    /**
     * @return array<int,string>
     */
    public function getHeaderLines() : array
    {
        $lines = [];
        foreach ($this->getHeaders() as $name => $value) {
            $lines[] = static::getHeaderName($name) . ': ' . $value;
        }
        return $lines;
    }

    protected function renderHeaders() : string
    {
        return \implode($this->mailer->getCrlf(), $this->getHeaderLines());
    }

    protected function encodeHeader(string $name, string $value = null) : string
    {
        if ($value !== null) {
            $name .= ': ' . $value;
        }
        return \mb_encode_mimeheader($name, 'UTF-8', 'B', $this->mailer->getCrlf());
    }

    protected function prepareHeaders() : void
    {
        if ( ! $this->getHeader('Date')) {
            $this->setHeader('Date', \date('r'));
        }
        $multipart = $this->getInlineAttachments() ? 'related' : 'mixed';
        $this->setHeader(
            'Content-Type',
            'multipart/' . $multipart . '; boundary="mixed-' . $this->getBoundary() . '"'
        );
    }

    protected function renderData() : string
    {
        $this->prepareHeaders();
        $data = '--mixed-' . $this->getBoundary() . $this->mailer->getCrlf();
        $data .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->getBoundary() . '"'
            . $this->mailer->getCrlf() . $this->mailer->getCrlf();
        $data .= $this->renderPlainMessage();
        $data .= $this->renderHtmlMessage();
        $data .= '--alt-' . $this->getBoundary() . '--'
            . $this->mailer->getCrlf() . $this->mailer->getCrlf();
        $data .= $this->renderAttachments();
        $data .= $this->renderInlineAttachments();
        $data .= '--mixed-' . $this->getBoundary() . '--';
        return $this->renderHeaders() . $this->mailer->getCrlf() . $data;
    }

    /**
     * @param string $message
     *
     * @return static
     */
    public function setPlainMessage(string $message) : static
    {
        $this->plainMessage = $message;
        return $this;
    }

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
     * @param string $message
     *
     * @return static
     */
    public function setHtmlMessage(string $message) : static
    {
        $this->htmlMessage = $message;
        return $this;
    }

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
        $part = '--alt-' . $this->getBoundary() . $this->mailer->getCrlf();
        $part .= 'Content-Type: ' . $contentType . '; charset='
            . $this->mailer->getCharset() . $this->mailer->getCrlf();
        $part .= 'Content-Transfer-Encoding: base64'
            . $this->mailer->getCrlf() . $this->mailer->getCrlf();
        $part .= \chunk_split(\base64_encode($message)) . $this->mailer->getCrlf();
        return $part;
    }

    /**
     * @return array<int,string>
     */
    public function getAttachments() : array
    {
        return $this->attachments;
    }

    /**
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
     * @return array<string,string>
     */
    public function getInlineAttachments() : array
    {
        return $this->inlineAttachments;
    }

    protected function renderAttachments() : string
    {
        $part = '';
        foreach ($this->getAttachments() as $attachment) {
            if ( ! \is_file($attachment)) {
                throw new LogicException('Attachment file not found: ' . $attachment);
            }
            $filename = \pathinfo($attachment, \PATHINFO_BASENAME);
            $filename = \htmlspecialchars($filename, \ENT_QUOTES | \ENT_HTML5);
            $contents = (string) \file_get_contents($attachment);
            $part .= '--mixed-' . $this->getBoundary() . $this->mailer->getCrlf();
            $part .= 'Content-Type: application/octet-stream; name="' . $filename . '"'
                . $this->mailer->getCrlf();
            $part .= 'Content-Disposition: attachment; filename="' . $filename . '"'
                . $this->mailer->getCrlf();
            $part .= 'Content-Transfer-Encoding: base64'
                . $this->mailer->getCrlf() . $this->mailer->getCrlf();
            $part .= \chunk_split(\base64_encode($contents)) . $this->mailer->getCrlf();
        }
        return $part;
    }

    protected function renderInlineAttachments() : string
    {
        $part = '';
        foreach ($this->getInlineAttachments() as $cid => $filename) {
            if ( ! \is_file($filename)) {
                throw new LogicException('Inline attachment file not found: ' . $filename);
            }
            $contents = (string) \file_get_contents($filename);
            $part .= '--mixed-' . $this->getBoundary() . $this->mailer->getCrlf();
            $part .= 'Content-ID: ' . $cid . $this->mailer->getCrlf();
            $part .= 'Content-Type: ' . \mime_content_type($filename) . $this->mailer->getCrlf();
            $part .= 'Content-Disposition: inline' . $this->mailer->getCrlf();
            $part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCrlf() . $this->mailer->getCrlf();
            $part .= \chunk_split(\base64_encode($contents)) . $this->mailer->getCrlf();
        }
        return $part;
    }

    /**
     * @param string $subject
     *
     * @return static
     */
    public function setSubject(string $subject) : static
    {
        $this->setHeader('Subject', $subject);
        return $this;
    }

    public function getSubject() : ?string
    {
        return $this->getHeader('Subject');
    }

    /**
     * @param string $address
     * @param string|null $name
     *
     * @return static
     */
    public function addTo(string $address, string $name = null) : static
    {
        $this->to[$address] = $name;
        $this->setHeader('To', static::formatAddressList($this->to));
        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function getTo() : array
    {
        return $this->to;
    }

    /**
     * Add Carbon Copy email address.
     *
     * @param string $address
     * @param string|null $name
     *
     * @return static
     */
    public function addCc(string $address, string $name = null) : static
    {
        $this->cc[$address] = $name;
        $this->setHeader('Cc', static::formatAddressList($this->cc));
        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function getCc() : array
    {
        return $this->cc;
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
     * Add Blind Carbon Copy email address.
     *
     * @param string $address
     * @param string|null $name
     *
     * @return static
     */
    public function addBcc(string $address, string $name = null) : static
    {
        $this->bcc[$address] = $name;
        $this->setHeader('Bcc', static::formatAddressList($this->bcc));
        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function getBcc() : array
    {
        return $this->bcc;
    }

    /**
     * @param string $address
     * @param string|null $name
     *
     * @return static
     */
    public function addReplyTo(string $address, string $name = null) : static
    {
        $this->replyTo[$address] = $name;
        $this->setHeader('Reply-To', static::formatAddressList($this->replyTo));
        return $this;
    }

    /**
     * @return array<string,string|null>
     */
    public function getReplyTo() : array
    {
        return $this->replyTo;
    }

    /**
     * @param string $address
     * @param string|null $name
     *
     * @return static
     */
    public function setFrom(string $address, string $name = null) : static
    {
        $this->from = [$address, $name];
        $this->setHeader('From', static::formatAddress($address, $name));
        return $this;
    }

    /**
     * @return array<int,string|null>
     */
    public function getFrom() : array
    {
        return $this->from;
    }

    public function getFromAddress() : ?string
    {
        return $this->from[0] ?? null;
    }

    public function getFromName() : ?string
    {
        return $this->from[1] ?? null;
    }

    /**
     * @param DateTime|null $datetime
     *
     * @return static
     */
    public function setDate(DateTime $datetime = null) : static
    {
        $date = $datetime ? $datetime->format('r') : \date('r');
        $this->setHeader('Date', $date);
        return $this;
    }

    public function getDate() : ?string
    {
        return $this->getHeader('Date');
    }

    /**
     * @param int $priority from 1 to 5
     *
     * @return static
     */
    public function setXPriority(int $priority) : static
    {
        $this->xPriority = $priority;
        $this->setHeader('X-Priority', (string) $priority);
        return $this;
    }

    public function getXPriority() : ?int
    {
        return $this->xPriority ?? null;
    }

    protected static function getHeaderName(string $header) : string
    {
        return static::$standardHeaders[\strtolower($header)] ?? $header;
    }

    protected static function formatAddress(string $address, string $name = null) : string
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
