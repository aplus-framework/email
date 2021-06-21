<?php namespace Framework\Email;

use DateTime;
use LogicException;

/**
 * Class Message.
 */
class Message
{
	protected Mailer $mailer;
	protected ?string $boundary;
	/**
	 * @var array|string[]
	 */
	protected array $headers = [
		'MIME-Version' => '1.0',
	];
	/**
	 * @var array|string[]
	 */
	protected array $attachments = [];
	/**
	 * @var array|string[]
	 */
	protected array $inlineAttachments = [];
	protected ?string $plainMessage = null;
	protected ?string $htmlMessage = null;
	/**
	 * @var array|string[]
	 */
	protected array $to = [];
	/**
	 * @var array|string[]
	 */
	protected array $cc = [];
	/**
	 * @var array|string[]
	 */
	protected array $bcc = [];
	/**
	 * @var array|string[]
	 */
	protected array $replyTo = [];
	/**
	 * @var array|string[]
	 */
	protected array $from = [];
	protected ?string $subject = null;
	protected ?string $date = null;
	protected int $priority = 3;
	/**
	 * @var array|string[]
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

	public function __construct(Mailer $mailer, string $boundary = null)
	{
		$this->mailer = $mailer;
		$this->boundary = $boundary;
		if ($boundary === null) {
			$this->boundary = \md5(\uniqid((string) \microtime(true), true));
			//$this->boundary = \bin2hex(\random_bytes(10));
		}
	}

	public function __toString() : string
	{
		return $this->renderData();
	}

	public function getBoundary() : string
	{
		return $this->boundary;
	}

	/**
	 * @param string      $name
	 * @param string|null $value
	 *
	 * @return $this
	 */
	public function setHeader(string $name, ?string $value)
	{
		$this->headers[static::getHeaderName($name)] = $value;
		return $this;
	}

	public function getHeader(string $name) : ?string
	{
		return $this->headers[static::getHeaderName($name)] ?? null;
	}

	/**
	 * @return array|string[]
	 */
	public function getHeaders() : array
	{
		return $this->headers;
	}

	protected function renderHeaders() : string
	{
		$result = '';
		foreach ($this->getHeaders() as $name => $value) {
			if ($value !== null) {
				$name .= ': ' . $value;
			}
			$result .= $name . $this->mailer->getCRLF();
		}
		return $result;
	}

	protected function encodeHeader(string $name, string $value = null) : string
	{
		if ($value !== null) {
			$name .= ': ' . $value;
		}
		return \mb_encode_mimeheader($name, 'UTF-8', 'B', $this->mailer->getCRLF());
	}

	protected function prepareHeaders() : void
	{
		if ( ! $this->getHeader('Date')) {
			$this->setHeader('Date', \date('r'));
		}
		if ( ! $this->getHeader('Subject') && $this->getSubject() !== null) {
			$this->setHeader('Subject', $this->getSubject());
		}
		if ( ! $this->getHeader('From')) {
			$this->setHeader(
				'From',
				static::formatAddress($this->getFromAddress(), $this->getFromName())
			);
		}
		if ( ! $this->getHeader('To')) {
			$this->setHeader('To', static::formatAddressList($this->getTo()));
		}
		if ( ! $this->getHeader('Cc') && $this->getCc()) {
			$this->setHeader('Cc', static::formatAddressList($this->getCc()));
		}
		if ( ! $this->getHeader('Bcc') && $this->getBcc()) {
			$this->setHeader('Bcc', static::formatAddressList($this->getBcc()));
		}
		if ( ! $this->getHeader('Reply-To') && $this->getReplyTo()) {
			$this->setHeader('Reply-To', static::formatAddressList($this->getReplyTo()));
		}
		if ( ! $this->getHeader('Content-Type')) {
			$multipart = $this->getInlineAttachments() ? 'related' : 'mixed';
			$this->setHeader(
				'Content-Type',
				'multipart/' . $multipart . '; boundary="mixed-' . $this->getBoundary() . '"'
			);
		}
	}

	protected function renderData() : string
	{
		$this->prepareHeaders();
		$data = '';
		$data .= '--mixed-' . $this->getBoundary() . $this->mailer->getCRLF();
		$data .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->getBoundary() . '"' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$data .= $this->renderPlainMessage();
		$data .= $this->renderHTMLMessage();
		$data .= '--alt-' . $this->getBoundary() . '--' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$data .= $this->renderAttachments();
		$data .= $this->renderInlineAttachments();
		$data .= '--mixed-' . $this->getBoundary() . '--';
		return $this->renderHeaders() . $this->mailer->getCRLF() . $data;
	}

	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function setPlainMessage(string $message)
	{
		$this->plainMessage = $message;
		return $this;
	}

	public function getPlainMessage() : ?string
	{
		return $this->plainMessage;
	}

	protected function renderPlainMessage() : ?string
	{
		$message = $this->getPlainMessage();
		return $message ? $this->renderMessage($message, 'text/plain') : null;
	}

	/**
	 * @param string $message
	 *
	 * @return $this
	 */
	public function setHTMLMessage(string $message)
	{
		$this->htmlMessage = $message;
		return $this;
	}

	public function getHTMLMessage() : ?string
	{
		return $this->htmlMessage;
	}

	protected function renderHTMLMessage() : ?string
	{
		$message = $this->getHTMLMessage();
		return $message ? $this->renderMessage($message) : null;
	}

	protected function renderMessage(
		string $message,
		string $contentType = 'text/html'
	) : string {
		$part = '--alt-' . $this->getBoundary() . $this->mailer->getCRLF();
		$part .= 'Content-Type: ' . $contentType . '; charset=' . $this->mailer->getCharset() . $this->mailer->getCRLF();
		$part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$part .= \chunk_split(\base64_encode($message)) . $this->mailer->getCRLF();
		return $part;
	}

	/**
	 * @return array|string[]
	 */
	public function getAttachments() : array
	{
		return $this->attachments;
	}

	/**
	 * @param string $filename
	 *
	 * @return $this
	 */
	public function addAttachment(string $filename)
	{
		$this->attachments[] = $filename;
		return $this;
	}

	/**
	 * @param string $filename
	 * @param string $cid
	 *
	 * @return $this
	 */
	public function setInlineAttachment(string $filename, string $cid)
	{
		$this->inlineAttachments[$cid] = $filename;
		return $this;
	}

	/**
	 * @return array|string[]
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
			$contents = (string) \file_get_contents($attachment);
			$part .= '--mixed-' . $this->getBoundary() . $this->mailer->getCRLF();
			$part .= 'Content-Type: application/octet-stream; name="' . $filename . '"' . $this->mailer->getCRLF();
			$part .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $this->mailer->getCRLF();
			$part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
			$part .= \chunk_split(\base64_encode($contents)) . $this->mailer->getCRLF();
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
			$part .= '--mixed-' . $this->getBoundary() . $this->mailer->getCRLF();
			$part .= 'Content-ID: ' . $cid . $this->mailer->getCRLF();
			$part .= 'Content-Type: ' . \mime_content_type($filename) . $this->mailer->getCRLF();
			$part .= 'Content-Disposition: inline' . $this->mailer->getCRLF();
			$part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
			$part .= \chunk_split(\base64_encode($contents)) . $this->mailer->getCRLF();
		}
		return $part;
	}

	/**
	 * @param string $subject
	 *
	 * @return $this
	 */
	public function setSubject(string $subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function getSubject() : ?string
	{
		return $this->subject;
	}

	/**
	 * @param string      $address
	 * @param string|null $name
	 *
	 * @return $this
	 */
	public function addTo(string $address, string $name = null)
	{
		$this->to[$address] = $name;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getTo() : array
	{
		return $this->to;
	}

	/**
	 * Add Carbon Copy email address.
	 *
	 * @param string      $address
	 * @param string|null $name
	 *
	 * @return $this
	 */
	public function addCc(string $address, string $name = null)
	{
		$this->cc[$address] = $name;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getCc() : array
	{
		return $this->cc;
	}

	/**
	 * @return array|string[]
	 */
	public function getRecipients() : array
	{
		$recipients = \array_merge($this->getTo(), $this->getCc());
		return \array_keys($recipients);
	}

	/**
	 * Add Blind Carbon Copy email address.
	 *
	 * @param string      $address
	 * @param string|null $name
	 *
	 * @return $this
	 */
	public function addBcc(string $address, string $name = null)
	{
		$this->bcc[$address] = $name;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getBcc() : array
	{
		return $this->bcc;
	}

	/**
	 * @param string      $address
	 * @param string|null $name
	 *
	 * @return $this
	 */
	public function addReplyTo(string $address, string $name = null)
	{
		$this->replyTo[$address] = $name;
		return $this;
	}

	/**
	 * @return array|string[]
	 */
	public function getReplyTo() : array
	{
		return $this->replyTo;
	}

	/**
	 * @param string      $address
	 * @param string|null $name
	 *
	 * @return $this
	 */
	public function setFrom(string $address, string $name = null)
	{
		$this->from = [$address, $name];
		return $this;
	}

	/**
	 * @return array|string[]
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
	 * @return $this
	 */
	public function setDate(DateTime $datetime = null)
	{
		$this->date = $datetime ? $datetime->format('r') : \date('r');
		$this->setHeader('Date', $this->date);
		return $this;
	}

	public function getDate() : ?string
	{
		return $this->date;
	}

	/**
	 * @param int $priority from 1 to 5
	 *
	 * @return $this
	 */
	public function setPriority(int $priority)
	{
		$this->priority = $priority;
		$this->setHeader('X-Priority', (string) $priority);
		return $this;
	}

	public function getPriority() : int
	{
		return $this->priority;
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
	 * @param array|string[] $addresses
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
