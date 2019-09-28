<?php namespace Framework\Email;

/**
 * Class Message.
 */
class Message
{
	/**
	 * @var Mailer
	 */
	protected $mailer;
	/**
	 * @var string
	 */
	protected $boundary;
	protected $headers = [
		'MIME-Version' => '1.0',
	];
	protected $attachments = [];
	protected $inlineAttachments = [];
	protected $plainMessage;
	protected $htmlMessage;
	protected $to = [];
	protected $cc = [];
	protected $bcc = [];
	protected $replyTo = [];
	protected $from = [];
	protected $subject;
	/**
	 * @var string
	 */
	protected $date;
	protected $priority = 3;
	protected static $standardHeaders = [
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
			$this->boundary = \md5(\uniqid(\microtime(true), true));
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

	public function setHeader(string $name, ?string $value)
	{
		$this->headers[static::getHeaderName($name)] = $value;
		return $this;
	}

	public function getHeader(string $name) : ?string
	{
		return $this->headers[static::getHeaderName($name)] ?? null;
	}

	public function getHeaders() : array
	{
		return $this->headers;
	}

	public function renderHeaders() : string
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

	public function encodeHeader(string $name, string $value = null) : string
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

	public function renderData() : string
	{
		$this->prepareHeaders();
		$data = '';
		$data .= '--mixed-' . $this->getBoundary() . $this->mailer->getCRLF();
		$data .= 'Content-Type: multipart/alternative; boundary="alt-' . $this->getBoundary() . '"' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$data .= $this->renderPlainMessage();
		$data .= $this->renderHTMLMessage();
		$data .= '--alt-' . $this->getBoundary() . '--' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$data .= $this->renderAttachments();
		$data .= '--mixed-' . $this->getBoundary() . '--';
		return $this->renderHeaders() . $this->mailer->getCRLF() . $data;
	}

	public function setPlainMessage(string $message)
	{
		$this->plainMessage = $message;
		return $this;
	}

	public function getPlainMessage() : ?string
	{
		return $this->plainMessage;
	}

	public function renderPlainMessage() : ?string
	{
		$message = $this->getPlainMessage();
		return $message ? $this->renderMessage($message, 'text/plain') : null;
	}

	public function setHTMLMessage(string $message)
	{
		$this->htmlMessage = $message;
		return $this;
	}

	public function getHTMLMessage() : ?string
	{
		return $this->htmlMessage;
	}

	public function renderHTMLMessage() : ?string
	{
		$message = $this->getHTMLMessage();
		return $message ? $this->renderMessage($message) : null;
	}

	protected function renderMessage(
		string $message,
		string $content_type = 'text/html'
	) : string {
		$part = '--alt-' . $this->getBoundary() . $this->mailer->getCRLF();
		$part .= 'Content-Type: ' . $content_type . '; charset=' . $this->mailer->getCharset() . $this->mailer->getCRLF();
		$part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
		$part .= \chunk_split(\base64_encode($message)) . $this->mailer->getCRLF();
		return $part;
	}

	public function getAttachments() : array
	{
		return $this->attachments;
	}

	public function addAttachment(string $filename)
	{
		$this->attachments[] = $filename;
		return $this;
	}

	public function setInlineAttachment(string $contents, string $cid)
	{
		$this->inlineAttachments[$cid] = $contents;
		return $this;
	}

	public function getInlineAttachments() : array
	{
		return $this->inlineAttachments;
	}

	public function renderAttachments() : string
	{
		$part = '';
		foreach ($this->getAttachments() as $attachment) {
			if ( ! \is_file($attachment)) {
				throw new \LogicException('Attachment file not found: ' . $attachment);
			}
			$filename = \pathinfo($attachment, \PATHINFO_BASENAME);
			$contents = \file_get_contents($attachment);
			$part .= '--mixed-' . $this->getBoundary() . $this->mailer->getCRLF();
			$part .= 'Content-Type: application/octet-stream; name="' . $filename . '"' . $this->mailer->getCRLF();
			$part .= 'Content-Disposition: attachment; filename="' . $filename . '"' . $this->mailer->getCRLF();
			$part .= 'Content-Transfer-Encoding: base64' . $this->mailer->getCRLF() . $this->mailer->getCRLF();
			$part .= \chunk_split(\base64_encode($contents)) . $this->mailer->getCRLF();
		}
		return $part;
	}

	public function setSubject(string $subject)
	{
		$this->subject = $subject;
		return $this;
	}

	public function getSubject() : ?string
	{
		return $this->subject;
	}

	public function addTo(string $address, string $name = null)
	{
		$this->to[$address] = $name;
		return $this;
	}

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

	public function getCc() : array
	{
		return $this->cc;
	}

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

	public function getBcc() : array
	{
		return $this->bcc;
	}

	public function addReplyTo(string $address, string $name = null)
	{
		$this->replyTo[$address] = $name;
		return $this;
	}

	public function getReplyTo() : array
	{
		return $this->replyTo;
	}

	public function setFrom(string $address, string $name = null)
	{
		$this->from = [$address, $name];
		return $this;
	}

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

	public function setDate(\DateTime $datetime = null)
	{
		$this->date = $datetime ? $datetime->format('r') : \date('r');
		$this->setHeader('Date', $this->date);
		return $this;
	}

	public function getDate() : ?string
	{
		return $this->date;
	}

	public function setPriority(int $priority)
	{
		$this->priority = $priority;
		$this->setHeader('X-Priority', $priority);
		return $this;
	}

	public function getPriority() : int
	{
		return $this->priority;
	}

	public static function getHeaderName(string $header) : string
	{
		return static::$standardHeaders[\strtolower($header)] ?? $header;
	}

	public static function formatAddress(string $address, string $name = null) : string
	{
		return $name !== null ? '"' . $name . '" <' . $address . '>' : $address;
	}

	public static function formatAddressList(array $addresses) : string
	{
		$data = [];
		foreach ($addresses as $address => $name) {
			$data[] = static::formatAddress($address, $name);
		}
		return \implode(', ', $data);
	}
}
