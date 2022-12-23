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

use Framework\Email\Debug\EmailCollector;
use JetBrains\PhpStorm\ArrayShape;
use SensitiveParameter;

/**
 * Class Mailer.
 *
 * @package email
 */
abstract class Mailer
{
    /**
     * @var array<string,mixed>
     */
    protected array $config = [];
    /**
     * @var array<int,array<string,mixed>>
     */
    protected array $logs = [];
    protected EmailCollector $debugCollector;

    /**
     * Mailer constructor.
     *
     * @param array<string,mixed>|string $username
     * @param string|null $password
     * @param string $host
     * @param int $port
     * @param string|null $hostname
     */
    public function __construct(
        #[SensitiveParameter] array | string $username,
        #[SensitiveParameter] string $password = null,
        string $host = 'localhost',
        int $port = 587,
        string $hostname = null
    ) {
        $this->config = \is_array($username)
            ? $this->makeConfig($username)
            : $this->makeConfig([
                'username' => $username,
                'password' => $password,
                'host' => $host,
                'port' => $port,
                'hostname' => $hostname ?? \gethostname(),
            ]);
    }

    /**
     * Make Base configurations.
     *
     * @param array<string,mixed> $config
     *
     * @return array<string,mixed>
     */
    #[ArrayShape([
        'host' => 'string',
        'port' => 'int',
        'tls' => 'bool',
        'username' => 'string|null',
        'password' => 'string|null',
        'charset' => 'string',
        'crlf' => 'string',
        'connection_timeout' => 'int',
        'response_timeout' => 'int',
        'hostname' => 'string',
        'keep_alive' => 'bool',
        'add_logs' => 'bool',
    ])]
    protected function makeConfig(#[SensitiveParameter] array $config) : array
    {
        return \array_replace_recursive([
            'host' => 'localhost',
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
            'add_logs' => true,
        ], $config);
    }

    /**
     * @todo Make public in version 4.0.0
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function getConfig(string $key) : mixed
    {
        return $this->config[$key];
    }

    /**
     * @return array<string,mixed>
     */
    #[ArrayShape([
        'host' => 'string',
        'port' => 'int',
        'tls' => 'bool',
        'username' => 'string|null',
        'password' => 'string|null',
        'charset' => 'string',
        'crlf' => 'string',
        'connection_timeout' => 'int',
        'response_timeout' => 'int',
        'hostname' => 'string',
        'keep_alive' => 'bool',
        'add_logs' => 'bool',
    ])]
    public function getConfigs() : array
    {
        return $this->config;
    }

    public function getCrlf() : string
    {
        return (string) $this->getConfig('crlf');
    }

    public function getCharset() : string
    {
        return (string) $this->getConfig('charset');
    }

    /**
     * Get an array of logs.
     *
     * Contains commands and responses from the Mailer server.
     *
     * @return array<int,array<string,mixed>>
     */
    public function getLogs() : array
    {
        return $this->logs;
    }

    /**
     * Reset logs.
     *
     * @return static
     */
    public function resetLogs() : static
    {
        $this->logs = [];
        return $this;
    }

    /**
     * @param string $command
     * @param string $response
     *
     * @return static
     */
    protected function addLog(string $command, string $response) : static
    {
        if ( ! $this->getConfig('add_logs')) {
            return $this;
        }
        $this->logs[] = [
            'command' => $command,
            'responses' => \explode(\PHP_EOL, $response),
        ];
        return $this;
    }

    abstract public function send(Message $message) : bool;

    public function setDebugCollector(EmailCollector $collector) : static
    {
        $collector->setMailer($this);
        $this->debugCollector = $collector;
        return $this;
    }
}
