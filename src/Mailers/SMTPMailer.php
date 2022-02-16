<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Email\Mailers;

use Framework\Email\Mailer;
use Framework\Email\Message;

/**
 * Class SMTPMailer.
 *
 * @see https://tools.ietf.org/html/rfc2821
 *
 * @package email
 */
class SMTPMailer extends Mailer
{
    /**
     * @var false|resource $socket
     */
    protected $socket = false;

    public function __destruct()
    {
        $this->disconnect();
    }

    protected function connect() : bool
    {
        if ($this->socket && ($this->getConfig('keep_alive') === true)) {
            return $this->sendCommand('EHLO ' . $this->getConfig('hostname')) === 250;
        }
        $this->disconnect();
        $this->socket = @\fsockopen(
            $this->getConfig('host'),
            (int) $this->getConfig('port'),
            $errorCode,
            $errorMessage,
            (float) $this->getConfig('connection_timeout')
        );
        if ($this->socket === false) {
            $this->addLog('', 'Socket connection error ' . $errorCode . ': ' . $errorMessage);
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
            $closed = \fclose($this->socket);
        }
        $this->socket = false;
        return $closed ?? true;
    }

    /**
     * @see https://datatracker.ietf.org/doc/html/rfc2821#section-4.2.3
     * @see https://datatracker.ietf.org/doc/html/rfc4954#section-4.1
     *
     * @return bool
     */
    protected function authenticate() : bool
    {
        if (($this->getConfig('username') === null) && ($this->getConfig('password') === null)) {
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
     * @param Message $message
     *
     * @return bool
     */
    public function send(Message $message) : bool
    {
        if (isset($this->debugCollector)) {
            $start = \microtime(true);
            $code = $this->sendMessage($message);
            $end = \microtime(true);
            $this->debugCollector->addData([
                'start' => $start,
                'end' => $end,
                'code' => $code ?: 0,
                'from' => $message->getFromAddress() ?? $this->getConfig('username'),
                'length' => \strlen((string) $message),
                'recipients' => $message->getRecipients(),
                'headers' => $message->getHeaders(),
                'plain' => $message->getPlainMessage(),
                'html' => $message->getHtmlMessage(),
                'attachments' => $message->getAttachments(),
                'inlineAttachments' => $message->getInlineAttachments(),
            ]);
            return $code === 250;
        }
        return $this->sendMessage($message) === 250;
    }

    protected function sendMessage(Message $message) : int | false
    {
        if ( ! $this->connect()) {
            return false;
        }
        $message->setMailer($this);
        $from = $message->getFromAddress() ?? $this->getConfig('username');
        $this->sendCommand('MAIL FROM: <' . $from . '>');
        foreach ($message->getRecipients() as $address) {
            $this->sendCommand('RCPT TO: <' . $address . '>');
        }
        $this->sendCommand('DATA');
        $code = $this->sendCommand(
            $message . $this->getCrlf() . '.'
        );
        if ($this->getConfig('keep_alive') !== true) {
            $this->disconnect();
        }
        return $code;
    }

    /**
     * Get Mail Server response.
     *
     * @return string
     */
    protected function getResponse() : string
    {
        $response = '';
        // @phpstan-ignore-next-line
        \stream_set_timeout($this->socket, $this->getConfig('response_timeout'));
        // @phpstan-ignore-next-line
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
        // @phpstan-ignore-next-line
        \fwrite($this->socket, $command . $this->getCrlf());
        $response = $this->getResponse();
        $this->addLog($command, $response);
        return $this->makeResponseCode($response);
    }

    /**
     * @see https://tools.ietf.org/html/rfc2821#section-4.2.3
     * @see https://en.wikipedia.org/wiki/List_of_SMTP_server_return_codes
     *
     * @param string $response
     *
     * @return int
     */
    private function makeResponseCode(string $response) : int
    {
        return (int) \substr($response, 0, 3);
    }
}
