<?php
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Email;

use Framework\Email\Message;
use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

final class SMTPTest extends TestCase
{
    protected SMTP $smtp;

    public function setup() : void
    {
        $this->smtp = new SMTP([
            'server' => \getenv('SMTP_HOST'),
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
            ->setInlineAttachment(__DIR__ . '/logo-circle.png', 'abc123')
            ->addAttachment(__FILE__);
    }

    public function testSend() : void
    {
        \sleep(5);
        self::assertTrue($this->smtp->send($this->getMessage()));
    }

    public function testLogs() : void
    {
        \sleep(5);
        $this->smtp->send($this->getMessage());
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
}
