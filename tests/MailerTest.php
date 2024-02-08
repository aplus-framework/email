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

use Framework\Email\Mailer;
use Framework\Email\Message;
use PHPUnit\Framework\TestCase;

final class MailerTest extends TestCase
{
    protected Mailer $mailer;

    public function setup() : void
    {
        $this->mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
        ]);
    }

    public function testCrlf() : void
    {
        self::assertSame("\r\n", $this->mailer->getConfig('crlf'));
    }

    public function testCharset() : void
    {
        self::assertSame('utf-8', $this->mailer->getConfig('charset'));
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
        self::assertTrue($this->mailer->send($this->getMessage()));
    }

    public function testKeepAlive() : void
    {
        \sleep(5);
        $smtp = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
            'keep_alive' => true,
        ]);
        self::assertTrue($smtp->send($this->getMessage()));
        \sleep(5);
        self::assertTrue($smtp->send($this->getMessage()));
    }

    public function testLastResponse() : void
    {
        $mailer = new class([]) extends Mailer {
            public function setLastResponse(?string $lastResponse) : static
            {
                return parent::setLastResponse($lastResponse);
            }
        };
        self::assertNull($mailer->getLastResponse());
        $mailer->setLastResponse('250 foo');
        self::assertSame('250 foo', $mailer->getLastResponse());
        $mailer->setLastResponse('250 foo' . \PHP_EOL . '250 bar');
        self::assertSame('250 bar', $mailer->getLastResponse());
        $mailer->setLastResponse(null);
        self::assertNull($mailer->getLastResponse());
    }

    public function testFailToAuthenticateUsernameNotSet() : void
    {
        \sleep(5);
        $mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => null,
            'password' => 'foo',
        ]);
        self::assertFalse($mailer->send($this->getMessage()));
        self::assertSame(
            'Username is not set',
            $mailer->getLastResponse()
        );
    }

    public function testFailToAuthenticateUsernameIsWrong() : void
    {
        \sleep(5);
        $mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => 'foo',
            'password' => \getenv('SMTP_PASSWORD'),
            'save_logs' => true,
        ]);
        self::assertFalse($mailer->send($this->getMessage()));
        self::assertSame(
            '535 5.7.0 Invalid credentials',
            $mailer->getLastResponse()
        );
    }

    public function testFailToAuthenticatePasswordNotSet() : void
    {
        \sleep(5);
        $mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => 'foo',
            'password' => null,
        ]);
        self::assertFalse($mailer->send($this->getMessage()));
        self::assertSame(
            'Password is not set',
            $mailer->getLastResponse()
        );
    }

    public function testFailToAuthenticatePasswordIsWrong() : void
    {
        \sleep(5);
        $mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => 'foo',
            'save_logs' => true,
        ]);
        self::assertFalse($mailer->send($this->getMessage()));
        self::assertSame(
            '535 5.7.0 Invalid credentials',
            $mailer->getLastResponse()
        );
    }

    public function testFailToConnect() : void
    {
        \sleep(5);
        $smtp = new Mailer([
            'host' => 'foo',
            'save_logs' => true,
        ]);
        self::assertFalse($smtp->send($this->getMessage()));
        $log = $smtp->getLogs()[0];
        self::assertSame('', $log['command']);
        self::assertStringStartsWith(
            'Socket connection error ',
            $log['responses'][0]
        );
        self::assertStringStartsWith(
            'Socket connection error ',
            $smtp->getLastResponse()
        );
    }

    public function testLogs() : void
    {
        \sleep(5);
        $mailer = new Mailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
            'save_logs' => true,
        ]);
        $mailer->send($this->getMessage());
        self::assertNotEmpty($mailer->getLogs());
        $log = $mailer->getLogs()[0];
        self::assertSame('', $log['command']);
        self::assertStringStartsWith('220 ', $log['responses'][0]);
        $log = $mailer->getLogs()[1];
        self::assertSame('EHLO ' . \gethostname(), $log['command']);
        foreach ($log['responses'] as $response) {
            self::assertStringStartsWith('250', $response);
        }
        $log = $mailer->getLogs()[2];
        self::assertSame('STARTTLS', $log['command']);
        self::assertStringStartsWith('220', $log['responses'][0]);
        $log = $mailer->getLogs()[3];
        self::assertSame('EHLO ' . \gethostname(), $log['command']);
        foreach ($log['responses'] as $response) {
            self::assertStringStartsWith('250', $response);
        }
        $log = $mailer->getLogs()[4];
        self::assertSame('AUTH LOGIN', $log['command']);
        self::assertStringStartsWith('334', $log['responses'][0]);
        $log = $mailer->getLogs()[5];
        self::assertNotEmpty($log['command']);
        self::assertStringStartsWith('334', $log['responses'][0]);
        $log = $mailer->getLogs()[6];
        self::assertNotEmpty($log['command']);
        self::assertStringStartsWith('235 ', $log['responses'][0]);
        $log = $mailer->getLogs()[7];
        self::assertStringStartsWith('MAIL FROM:', $log['command']);
        self::assertStringStartsWith('250 ', $log['responses'][0]);
        $log = $mailer->getLogs()[8];
        self::assertStringStartsWith('RCPT TO:', $log['command']);
        self::assertStringStartsWith('250', $log['responses'][0]);
        $log = $mailer->getLogs()[9];
        self::assertSame('DATA', $log['command']);
        self::assertStringStartsWith('354', $log['responses'][0]);
        $mailer->resetLogs();
        self::assertEmpty($mailer->getLogs());
    }

    public function testLogsDisabled() : void
    {
        \sleep(5);
        $this->mailer->send($this->getMessage());
        self::assertEmpty($this->mailer->getLogs());
    }

    public function testConfigs() : void
    {
        foreach ($this->mailer->getConfigs() as $key => $value) {
            self::assertIsString($key);
        }
    }

    public function testCreateMessage() : void
    {
        $mailer = new Mailer([]);
        $m1 = $mailer->createMessage();
        $m2 = $mailer->createMessage();
        self::assertInstanceOf(Message::class, $m1);
        self::assertInstanceOf(Message::class, $m2);
        self::assertNotSame($m1, $m2);
    }
}
