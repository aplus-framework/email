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
use RuntimeException;
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
     * The plain text content.
     *
     * @var string
     */
    protected string $plainContent;
    /**
     * The HTML content.
     *
     * @var string
     */
    protected string $htmlContent;

    /**
     * Render the Message as string.
     *
     * @return string
     */
    public function __toString() : string
    {
        return $this->toString();
    }

    public function toString() : string
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
        $this->boundary = $boundary ?? $this->makeBoundary();
        return $this;
    }

    protected function makeBoundary() : string
    {
        return \bin2hex(\random_bytes(16));
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
    }

    /**
     * Encode string with base64 and split into smaller chunks.
     *
     * @param string $string
     *
     * @return string
     */
    protected function encodeSplit(string $string) : string
    {
        $string = \base64_encode($string);
        return \chunk_split($string);
    }

    protected function renderData() : string
    {
        if ($this->isHtmlOnly()) {
            return $this->renderHtmlOnly();
        }
        if ($this->isPlainOnly()) {
            return $this->renderPlainOnly();
        }
        if ($this->isAlternative()) {
            return $this->renderAlternative();
        }
        if ($this->isMixedAndInline()) {
            return $this->renderMixedAndInline();
        }
        if ($this->isMixed()) {
            return $this->renderMixed();
        }
        if ($this->isInline()) {
            return $this->renderInline();
        }
        throw new RuntimeException('No method found to render data');
    }

    protected function isHtmlOnly() : bool
    {
        return $this->getHtmlContent() !== null
            && $this->getPlainContent() === null
            && $this->getAttachments() === []
            && $this->getInlineAttachments() === [];
    }

    protected function renderHtmlOnly() : string
    {
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: text/html; charset="utf-8"' . $crlf;
        $data .= 'Content-Transfer-Encoding: base64' . $crlf;
        $data .= $crlf;
        $data .= $this->encodeSplit($this->getHtmlContent());
        return $data;
    }

    protected function isPlainOnly() : bool
    {
        return $this->getHtmlContent() === null
            && $this->getPlainContent() !== null
            && $this->getAttachments() === []
            && $this->getInlineAttachments() === [];
    }

    protected function renderPlainOnly() : string
    {
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: text/plain; charset="utf-8"' . $crlf;
        $data .= 'Content-Transfer-Encoding: base64' . $crlf;
        $data .= $crlf;
        $data .= $this->encodeSplit($this->getPlainContent());
        return $data;
    }

    protected function isAlternative() : bool
    {
        return $this->getHtmlContent() !== null
            && $this->getPlainContent() !== null
            && $this->getAttachments() === []
            && $this->getInlineAttachments() === [];
    }

    protected function renderAlternative() : string
    {
        $boundary = $this->getBoundary();
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: multipart/alternative; boundary="' . $boundary . '"' . $crlf;
        $data .= $crlf;
        $data .= $this->makeHtmlBlock($this->getHtmlContent()) . $crlf;
        $data .= $this->makePlainBlock($this->getPlainContent()) . $crlf;
        $data .= '--' . $boundary . '--';
        return $data;
    }

    protected function isMixed() : bool
    {
        return $this->getAttachments() !== []
            && $this->getInlineAttachments() === [];
    }

    protected function renderMixed() : string
    {
        $boundary = $this->getBoundary();
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $crlf;
        $data .= $crlf;

        $hasAlternative = $this->getHtmlContent() !== null || $this->getPlainContent() !== null;
        if ($hasAlternative) {
            $boundary2 = $this->makeBoundary();
            $data .= '--' . $boundary . $crlf;
            $data .= 'Content-Type: multipart/alternative; boundary="' . $boundary2 . '"' . $crlf;
            $data .= $crlf;

            $content = $this->getHtmlContent();
            if ($content !== null) {
                $data .= $this->makeHtmlBlock($content, $boundary2) . $crlf;
            }

            $content = $this->getPlainContent();
            if ($content !== null) {
                $data .= $this->makePlainBlock($content, $boundary2) . $crlf;
            }
            $data .= '--' . $boundary2 . '--' . $crlf . $crlf;
        }

        $part = '';
        foreach ($this->getAttachments() as $attachment) {
            $part .= $this->makeAttachmentBlock($attachment, $boundary) . $crlf;
        }
        $data .= $part;

        $data .= '--' . $boundary . '--';
        return $data;
    }

    protected function isInline() : bool
    {
        return  $this->getInlineAttachments() !== [];
    }

    protected function renderInline() : string
    {
        $boundary = $this->getBoundary();
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: multipart/related; boundary="' . $boundary . '"' . $crlf;
        $data .= $crlf;

        $hasAlternative = $this->getHtmlContent() !== null || $this->getPlainContent() !== null;
        if ($hasAlternative) {
            $boundary2 = $this->makeBoundary();
            $data .= '--' . $boundary . $crlf;
            $data .= 'Content-Type: multipart/alternative; boundary="' . $boundary2 . '"' . $crlf;
            $data .= $crlf;

            $content = $this->getHtmlContent();
            if ($content !== null) {
                $data .= $this->makeHtmlBlock($content, $boundary2) . $crlf;
            }

            $content = $this->getPlainContent();
            if ($content !== null) {
                $data .= $this->makePlainBlock($content, $boundary2) . $crlf;
            }
            $data .= '--' . $boundary2 . '--' . $crlf . $crlf;
        }

        $part = '';
        foreach ($this->getInlineAttachments() as $cid => $attachment) {
            $part .= $this->makeInlineAttachmentBlock($cid, $attachment, $boundary) . $crlf;
        }
        $data .= $part;

        $data .= '--' . $boundary . '--';
        return $data;
    }

    protected function isMixedAndInline() : bool
    {
        return $this->getAttachments() !== []
            && $this->getInlineAttachments() !== [];
    }

    protected function renderMixedAndInline() : string
    {
        $boundary = $this->getBoundary();
        //$boundary = 'mixed_raiz_aaa';
        $crlf = $this->getCrlf();
        $this->prepareHeaders();
        $data = $this->renderHeaders() . $crlf;
        $data .= 'Content-Type: multipart/mixed; boundary="' . $boundary . '"' . $crlf;
        $data .= $crlf;

        $boundary2 = $this->makeBoundary();
        //$boundary2 = 'alternative_nivel2_bbb';
        $data .= '--' . $boundary . $crlf;
        $data .= 'Content-Type: multipart/alternative; boundary="' . $boundary2 . '"' . $crlf;
        $data .= $crlf;

        $content = $this->getPlainContent();
        if ($content !== null) {
            $data .= $this->makePlainBlock($content, $boundary2) . $crlf;
        }

        $boundary3 = $this->makeBoundary();
        //$boundary3 = 'related_nivel3_ccc';
        $data .= '--' . $boundary2 . $crlf;
        $data .= 'Content-Type: multipart/related; boundary="' . $boundary3 . '"' . $crlf;
        $data .= $crlf;

        $content = $this->getHtmlContent();
        if ($content !== null) {
            $data .= $this->makeHtmlBlock($content, $boundary3) . $crlf;
        }

        $part = '';
        foreach ($this->getInlineAttachments() as $cid => $attachment) {
            $part .= $this->makeInlineAttachmentBlock($cid, $attachment, $boundary3) . $crlf;
        }
        $data .= $part;

        $data .= '--' . $boundary3 . '--' . $crlf . $crlf;
        $data .= '--' . $boundary2 . '--' . $crlf . $crlf;

        $part = '';
        foreach ($this->getAttachments() as $attachment) {
            $part .= $this->makeAttachmentBlock($attachment, $boundary) . $crlf;
        }
        $data .= $part;

        $data .= '--' . $boundary . '--';
        return $data;
    }

    protected function makeBlock(string $content, string $type, ?string $boundary = null) : string
    {
            $boundary ??= $this->getBoundary();
            $crlf = $this->getCrlf();
            $data = '--' . $boundary . $crlf;
            $data .= 'Content-Type: ' . $type . '; charset="utf-8"' . $crlf;
            $data .= 'Content-Transfer-Encoding: base64' . $crlf;
            $data .= $crlf;
            $data .= $this->encodeSplit($content);
            return $data;
    }

    protected function makePlainBlock(string $content, ?string $boundary = null) : string
    {
        return $this->makeBlock($content, 'text/plain', $boundary);
    }

    protected function makeHtmlBlock(string $content, ?string $boundary = null) : string
    {
        return $this->makeBlock($content, 'text/html', $boundary);
    }

    protected function makeAttachmentBlock(Attachment $attachment, ?string $boundary = null) : string
    {
        $boundary ??= $this->getBoundary();
        $crlf = $this->getCrlf();
        $data = '--' . $boundary . $crlf;
        $data .= 'Content-Type: ' . $attachment->getMimeType() . '; name="' . $attachment->getName() . '"' . $crlf;
        $data .= 'Content-Disposition: attachment; filename="' . $attachment->getName() . '"' . $crlf;
        $data .= 'Content-Transfer-Encoding: base64' . $crlf;
        $data .= $crlf;
        $data .= $attachment->getBase64SplitContents();
        return $data;
    }

    protected function makeInlineAttachmentBlock(string $cid, Attachment $attachment, ?string $boundary = null) : string
    {
        $boundary ??= $this->getBoundary();
        $crlf = $this->getCrlf();
        $data = '--' . $boundary . $crlf;
        $data .= 'Content-ID: <' . $cid . '>' . $crlf;
        $data .= 'Content-Type: ' . $attachment->getMimeType() . $crlf;
        $data .= 'Content-Disposition: inline' . $crlf;
        $data .= 'Content-Transfer-Encoding: base64' . $crlf;
        $data .= $crlf;
        $data .= $attachment->getBase64SplitContents();
        return $data;
    }

    /**
     * Set the text/plain content.
     *
     * @param string $content The text/plain content
     *
     * @return static
     */
    public function setPlainContent(string $content) : static
    {
        $this->plainContent = $content;
        return $this;
    }

    /**
     * Get the text/plain content.
     *
     * @return string|null The content or null if not set
     */
    public function getPlainContent() : ?string
    {
        return $this->plainContent ?? null;
    }

    /**
     * Alias of {@see Framework\Email\Message::setHtmlContent()}.
     *
     * @param string $body The text/html content
     *
     * @return static
     */
    public function setBody(#[Language('HTML')] string $body) : static
    {
        return $this->setHtmlContent($body);
    }

    /**
     * Alias of {@see Framework\Email\Message::getHtmlContent()}.
     *
     * @return string|null The text/html content or null if not set
     */
    public function getBody() : ?string
    {
        return $this->getHtmlContent();
    }

    /**
     * Set the text/html content.
     *
     * @param string $content The text/html content
     *
     * @return static
     */
    public function setHtmlContent(#[Language('HTML')] string $content) : static
    {
        $this->htmlContent = $content;
        return $this;
    }

    /**
     * Get the text/html content.
     *
     * @return string|null The text/html content or null if not set
     */
    public function getHtmlContent() : ?string
    {
        return $this->htmlContent ?? null;
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
        if ($this->isNullOrEmptyString($this->getPlainContent())
            && $this->isNullOrEmptyString($this->getHtmlContent())
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
