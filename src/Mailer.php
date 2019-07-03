<?php namespace Framework\Email;

/**
 * Class Mailer.
 */
abstract class Mailer
{
	/**
	 * @var array
	 */
	protected $config = [];
	/**
	 * @var array $logs
	 */
	protected $logs = [];

	/**
	 * Mailer constructor.
	 *
	 * @param array|string $username
	 * @param string       $password
	 * @param string       $server
	 * @param int          $port
	 * @param string|null  $hostname
	 */
	public function __construct(
		$username,
		string $password = null,
		string $server = 'localhost',
		int $port = 587,
		string $hostname = null
	) {
		$this->config = \is_array($username)
			? $this->makeConfig($username)
			: $this->makeConfig([
				'username' => $username,
				'password' => $password,
				'server' => $server,
				'port' => $port,
				'hostname' => $hostname ?? \gethostname(),
			]);
	}

	/**
	 * Make Base configurations.
	 *
	 * @param array $config
	 *
	 * @return array
	 */
	protected function makeConfig(array $config) : array
	{
		return \array_replace_recursive([
			'server' => 'localhost',
			'port' => 587,
			'tls' => true,
			'username' => null,
			'password' => null,
			'charset' => 'utf-8',
			'crlf' => "\r\n",
			'connection_timeout' => 10,
			'response_timeout' => 5,
			'hostname' => \gethostname(),
			'keep_alive' => false,
		], $config);
	}

	protected function getConfig(string $key)
	{
		return $this->config[$key];
	}

	public function getCRLF() : string
	{
		return $this->getConfig('crlf');
	}

	public function getCharset() : string
	{
		return $this->getConfig('charset');
	}

	/**
	 * Get log array
	 * -- contains commands and responses from Mailer server.
	 *
	 * @return array
	 */
	public function getLogs() : array
	{
		return $this->logs;
	}

	protected function addLog(string $command, string $response)
	{
		$this->logs[] = [$command, $response];
		return $this;
	}

	abstract public function send(Message $message) : bool;
}
