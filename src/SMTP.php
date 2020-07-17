<?php namespace Framework\Email;

/**
 * Class SMTP.
 *
 * @see https://tools.ietf.org/html/rfc2821
 */
class SMTP extends Mailer
{
	/**
	 * @var resource|null $socket
	 */
	protected $socket;

	public function __destruct()
	{
		$this->disconnect();
	}

	protected function connect() : bool
	{
		if ($this->socket && $this->getConfig('keep_alive') === true) {
			return $this->sendCommand('EHLO ' . $this->getConfig('hostname')) === 250;
		}
		$this->disconnect();
		$this->socket = \fsockopen(
			$this->getConfig('server'),
			$this->getConfig('port'),
			$error_number,
			$error_string,
			$this->getConfig('connection_timeout')
		);
		if (empty($this->socket)) {
			$this->addLog('', $error_string);
			return false;
		}
		$this->addLog('', $this->getResponse());
		$this->sendCommand('EHLO ' . $this->getConfig('hostname'));
		if ($this->getConfig('tls')) {
			$this->sendCommand('STARTTLS');
			\stream_socket_enable_crypto($this->socket, true, \STREAM_CRYPTO_METHOD_TLS_CLIENT);
			$this->sendCommand('EHLO ' . $this->getConfig('hostname'));
		}
		return $this->authenticate();
	}

	protected function disconnect() : bool
	{
		if (\is_resource($this->socket)) {
			$this->sendCommand('QUIT');
			return \fclose($this->socket);
		}
		return true;
	}

	protected function authenticate() : bool
	{
		if ($this->getConfig('username') === null && $this->getConfig('password') === null) {
			return false;
		}
		$code = $this->sendCommand('AUTH LOGIN');
		if ($code === 503) { // Already authenticated
			return true;
		}
		if ($code !== 334) {
			return false;
		}
		$code = $this->sendCommand(\base64_encode($this->getConfig('username')));
		if ($code !== 334) {
			return false;
		}
		$code = $this->sendCommand(\base64_encode($this->getConfig('password')));
		return $code === 235;
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
		$from = $message->getFromAddress() ?? $this->getConfig('username');
		$this->sendCommand('MAIL FROM: <' . $from . '>');
		foreach ($message->getRecipients() as $address) {
			$this->sendCommand('RCPT TO: <' . $address . '>');
		}
		$this->sendCommand('DATA');
		$code = $this->sendCommand(
			$message . $this->getCRLF() . '.'
		);
		if ($this->getConfig('keep_alive') !== true) {
			$this->disconnect();
		}
		return $code === 250;
	}

	/**
	 * Get Mail Server response.
	 *
	 * @return string
	 */
	protected function getResponse() : string
	{
		$response = '';
		\stream_set_timeout($this->socket, $this->getConfig('response_timeout'));
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
	 * @return int Response code
	 */
	protected function sendCommand(string $command) : int
	{
		\fwrite($this->socket, $command . $this->getCRLF());
		$response = $this->getResponse();
		$this->addLog($command, $response);
		return $this->makeResponseCode($response);
	}

	/**
	 * @see https://tools.ietf.org/html/rfc2821#section-4.2.3
	 *
	 * @param string $response
	 *
	 * @return int
	 */
	private function makeResponseCode(string $response) : int
	{
		return \substr($response, 0, 3);
	}
}
