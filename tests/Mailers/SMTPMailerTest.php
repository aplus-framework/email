<?php
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Email\Mailers;

use Framework\Email\Mailers\SMTPMailer;
use Framework\Email\Message;
use PHPUnit\Framework\TestCase;

final class SMTPMailerTest extends TestCase
{
    protected SMTPMailer $smtp;

    public function setup() : void
    {
        $this->smtp = new SMTPMailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
        ]);
    }

    public function testCrlf() : void
    {
        self::assertSame("\r\n", $this->smtp->getCrlf());
    }

    public function testCharset() : void
    {
        self::assertSame('utf-8', $this->smtp->getCharset());
    }

    protected function getMessage() : Message
    {
        return (new Message())
            ->addTo((string) \getenv('SMTP_ADDRESS'))
            ->setFrom((string) \getenv('SMTP_ADDRESS'))
            ->setPlainMessage('<b>Hello!</b><img src="cid:abc123">')
            ->setHtmlMessage('<b>Hello!</b><img src="cid:abc123">')
            ->setInlineAttachment(__DIR__ . '/../logo-circle.png', 'abc123')
            ->addAttachment(__FILE__);
    }

    public function testSend() : void
    {
        \sleep(5);
        self::assertTrue($this->smtp->send($this->getMessage()));
    }

    public function testKeepAlive() : void
    {
        \sleep(5);
        $smtp = new SMTPMailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
            'keep_alive' => true,
        ]);
        self::assertTrue($smtp->send($this->getMessage()));
        \sleep(5);
        self::assertTrue($smtp->send($this->getMessage()));
    }

    public function testFailToAuthenticate() : void
    {
        \sleep(5);
        $smtp = new SMTPMailer([
            'host' => \getenv('SMTP_HOST'),
        ]);
        self::assertFalse($smtp->send($this->getMessage()));
    }

    public function testFailToConnect() : void
    {
        \sleep(5);
        $smtp = new SMTPMailer('foo');
        self::assertFalse($smtp->send($this->getMessage()));
        $log = $smtp->getLogs()[0];
        self::assertSame('', $log['command']);
        self::assertStringStartsWith('Socket connection error ', $log['responses'][0]);
    }

    public function testLogs() : void
    {
        \sleep(5);
        $this->smtp->send($this->getMessage());
        self::assertNotEmpty($this->smtp->getLogs());
        $log = $this->smtp->getLogs()[0];
        self::assertSame('', $log['command']);
        self::assertStringStartsWith('220 ', $log['responses'][0]);
        $log = $this->smtp->getLogs()[1];
        self::assertSame('EHLO ' . \gethostname(), $log['command']);
        foreach ($log['responses'] as $response) {
            self::assertStringStartsWith('250', $response);
        }
        $log = $this->smtp->getLogs()[2];
        self::assertSame('STARTTLS', $log['command']);
        self::assertStringStartsWith('220', $log['responses'][0]);
        $log = $this->smtp->getLogs()[3];
        self::assertSame('EHLO ' . \gethostname(), $log['command']);
        foreach ($log['responses'] as $response) {
            self::assertStringStartsWith('250', $response);
        }
        $log = $this->smtp->getLogs()[4];
        self::assertSame('AUTH LOGIN', $log['command']);
        self::assertStringStartsWith('334', $log['responses'][0]);
        $log = $this->smtp->getLogs()[5];
        self::assertNotEmpty($log['command']);
        self::assertStringStartsWith('334', $log['responses'][0]);
        $log = $this->smtp->getLogs()[6];
        self::assertNotEmpty($log['command']);
        self::assertStringStartsWith('235 ', $log['responses'][0]);
        $log = $this->smtp->getLogs()[7];
        self::assertStringStartsWith('MAIL FROM:', $log['command']);
        self::assertStringStartsWith('250 ', $log['responses'][0]);
        $log = $this->smtp->getLogs()[8];
        self::assertStringStartsWith('RCPT TO:', $log['command']);
        self::assertStringStartsWith('250', $log['responses'][0]);
        $log = $this->smtp->getLogs()[9];
        self::assertSame('DATA', $log['command']);
        self::assertStringStartsWith('354', $log['responses'][0]);
        $this->smtp->resetLogs();
        self::assertEmpty($this->smtp->getLogs());
    }

    public function testLogsDisabled() : void
    {
        \sleep(5);
        $smtp = new SMTPMailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
            'add_logs' => false,
        ]);
        $smtp->send($this->getMessage());
        self::assertEmpty($smtp->getLogs());
    }

    public function testConfigs() : void
    {
        foreach ($this->smtp->getConfigs() as $key => $value) {
            self::assertIsString($key);
        }
    }
}
