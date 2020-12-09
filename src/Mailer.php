<?php namespace Framework\Email;

/**
 * Class Mailer.
 */
abstract class Mailer
{
	/**
	 * @var array|mixed[]
	 */
	protected array $config = [];
	/**
	 * @var array|array[]
	 */
	protected array $logs = [];

	/**
	 * Mailer constructor.
	 *
	 * @param array|mixed[]|string $username
	 * @param string|null          $password
	 * @param string               $server
	 * @param int                  $port
	 * @param string|null          $hostname
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
	 * @param array|mixed[] $config
	 *
	 * @return array|mixed[]
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

	/**
	 * @param string $key
	 *
	 * @return mixed
	 */
	protected function getConfig(string $key)
	{
		return $this->config[$key];
	}

	public function getCRLF() : string
	{
		return (string) $this->getConfig('crlf');
	}

	public function getCharset() : string
	{
		return (string) $this->getConfig('charset');
	}

	/**
	 * Get log array
	 * -- contains commands and responses from Mailer server.
	 *
	 * @return array|array[]
	 */
	public function getLogs() : array
	{
		return $this->logs;
	}

	/**
	 * @param string $command
	 * @param string $response
	 *
	 * @return $this
	 */
	protected function addLog(string $command, string $response)
	{
		$this->logs[] = [$command, $response];
		return $this;
	}

	abstract public function send(Message $message) : bool;
}
