<?php namespace Framework\Email;

/**
 * Class SMTP.
 */
class SMTP
{
	/**
	 * @var string
	 */
	protected $crlf = "\r\n";
	/**
	 * @var string|null $server
	 */
	protected $server;
	/**
	 * @var string|null $hostname
	 */
	protected $hostname;
	/**
	 * @var int $port
	 */
	protected $port = 587;
	/**
	 * @var resource|null $socket
	 */
	protected $socket;
	/**
	 * @var string|null $username
	 */
	protected $username;
	/**
	 * @var string|null $password
	 */
	protected $password;
	/**
	 * @var int $connectionTimeout
	 */
	protected $connectionTimeout = 30;
	/**
	 * @var int $responseTimeout
	 */
	protected $responseTimeout = 5;
	/**
	 * @var string|null $protocol
	 */
	protected $protocol = 'tcp';
	/**
	 * @var bool $isTLS
	 */
	protected $isTLS = true;
	/**
	 * @var array $logs
	 */
	protected $logs = [];
	/**
	 * @var string $charset
	 */
	protected $charset = 'utf-8';
	/**
	 * @var array
	 */
	protected $config = [
		'username' => null,
		'password' => null,
		'server' => 'localhost',
		'port' => 587,
		'connect_timeout' => 10,
		'response_timeout' => 5,
		'crlf' => "\r\n",
	];

	/**
	 * Class constructor
	 *  -- Set server name, port and timeout values.
	 *
	 * @param string      $server
	 * @param int         $port
	 * @param int         $connectionTimeout
	 * @param int         $responseTimeout
	 * @param string|null $hostname
	 */
	public function __construct(
		string $server,
		int $port = 587,
		int $connectionTimeout = 30,
		int $responseTimeout = 10,
		string $hostname = null
	) {
		$this->port = $port;
		$this->server = $server;
		$this->connectionTimeout = $connectionTimeout;
		$this->responseTimeout = $responseTimeout;
		$this->hostname = empty($hostname) ? \gethostname() : $hostname;
	}

	public function getCRLF() : string
	{
		return $this->crlf;
	}

	public function setCRLF(string $crlf)
	{
		$this->crlf = $crlf;
		return $this;
	}

	public function getCharset() : string
	{
		return $this->charset;
	}

	/**
	 * Set SMTP Login authentication.
	 *
	 * @param string $username
	 * @param string $password
	 *
	 * @return $this
	 */
	public function setLogin(string $username, string $password)
	{
		$this->username = $username;
		$this->password = $password;
		return $this;
	}

	/**
	 * Set message character set.
	 *
	 * @param string $charset
	 *
	 * @return $this
	 */
	public function setCharset(string $charset)
	{
		$this->charset = $charset;
		return $this;
	}

	/**
	 * Set SMTP Server protocol
	 * -- default value is null (no secure protocol).
	 *
	 * @param string $protocol
	 *
	 * @return $this
	 */
	public function setProtocol(string $protocol)
	{
		if ($protocol !== 'tcp') {
			$this->isTLS = false;
		}
		$this->protocol = $protocol;
		return $this;
	}

	/**
	 * Get log array
	 * -- contains commands and responses from SMTP server.
	 *
	 * @return array
	 */
	public function getLogs() : array
	{
		return $this->logs;
	}

	public function addLog(string $command, string $response)
	{
		$this->logs[] = [$command, $response];
		return $this;
	}

	protected function connect() : bool
	{
		//\var_dump($this->getServer());
		$this->socket = \fsockopen(
			$this->getServer(),
			$this->port,
			$error_number,
			$error_string,
			$this->connectionTimeout
		);
		if (empty($this->socket)) {
			return false;
		}
		$this->logs[] = [null, $this->getResponse()];
		$this->sendCommand('EHLO ' . $this->hostname);
		if ($this->isTLS) {
			$this->sendCommand('STARTTLS');
			\stream_socket_enable_crypto($this->socket, true, \STREAM_CRYPTO_METHOD_TLS_CLIENT);
			$this->sendCommand('EHLO ' . $this->hostname);
		}
		return $this->authenticate();
	}

	protected function disconnect() : bool
	{
		return $this->socket ? \fclose($this->socket) : true;
	}

	protected function authenticate() : bool
	{
		if ($this->username === null && $this->password === null) {
			return false;
		}
		$code = $this->sendCommand('AUTH LOGIN');
		$code = \substr($code, 0, 3);
		if ($code === '503') { // Already authenticated
			return true;
		}
		if ($code !== '334') {
			return false;
		}
		$code = $this->sendCommand(\base64_encode($this->username));
		$code = \substr($code, 0, 3);
		if ($code !== '334') {
			return false;
		}
		$code = $this->sendCommand(\base64_encode($this->password));
		$code = \substr($code, 0, 3);
		return $code === '235';
	}

	/**
	 * Send an Email Message.
	 *
	 * @var Message $message
	 *
	 * @return bool
	 */
	public function send(Message $message) : bool
	{
		if ( ! $this->connect()) {
			return false;
		}
		$from = $message->getFromAddress() ?? $this->username;
		$this->sendCommand('MAIL FROM: <' . $from . '>');
		foreach ($message->getRecipients() as $address) {
			$this->sendCommand('RCPT TO: <' . $address . '>');
		}
		$this->sendCommand('DATA');
		$status = $this->sendCommand(
			$message->renderData() . $this->getCRLF() . '.'
		);
		$status = \substr($status, 0, 3);
		$this->sendCommand('QUIT');
		$this->disconnect();
		return $status === '250';
	}

	/**
	 * Get server url
	 * -- if set SMTP protocol then prepend it to server.
	 *
	 * @return string
	 */
	protected function getServer() : string
	{
		return $this->protocol ? $this->protocol . '://' . $this->server : $this->server;
	}

	/**
	 * Get Mail Server response.
	 *
	 * @return string
	 */
	protected function getResponse() : string
	{
		$response = '';
		\stream_set_timeout($this->socket, $this->responseTimeout);
		while (($line = \fgets($this->socket, 512)) !== false) {
			$response .= \trim($line) . "\n";
			if (isset($line[3]) && $line[3] === ' ') {
				break;
			}
		}
		return \trim($response);
	}

	/**
	 * Send command to mail server.
	 *
	 * @param string $command
	 *
	 * @return string
	 */
	protected function sendCommand(string $command) : string
	{
		\fwrite($this->socket, $command . $this->getCRLF());
		$response = $this->getResponse();
		$this->addLog($command, $response);
		return $response;
	}
}
