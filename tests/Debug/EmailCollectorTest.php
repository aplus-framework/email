<?php
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Email\Debug;

use Framework\Email\Debug\EmailCollector;
use Framework\Email\Mailer;
use Framework\Email\Mailers\SMTPMailer;
use Framework\Email\Message;
use PHPUnit\Framework\TestCase;

final class EmailCollectorTest extends TestCase
{
    protected EmailCollector $collector;

    protected function setUp() : void
    {
        $this->collector = new EmailCollector();
    }

    protected function makeMailer() : Mailer
    {
        $mailer = new SMTPMailer([
            'host' => \getenv('SMTP_HOST'),
            'username' => \getenv('SMTP_USERNAME'),
            'password' => \getenv('SMTP_PASSWORD'),
        ]);
        $mailer->setDebugCollector($this->collector);
        return $mailer;
    }

    public function testNoData() : void
    {
        self::assertStringContainsString(
            'No messages have been sent',
            $this->collector->getContents()
        );
    }

    public function testMessagesSent() : void
    {
        $message = new Message();
        $message->addTo((string) \getenv('SMTP_ADDRESS'))
            ->setFrom((string) \getenv('SMTP_ADDRESS'))
            ->setPlainMessage('Hello!')
            ->addAttachment(__FILE__);
        $mailer = $this->makeMailer();
        \sleep(5);
        $mailer->send($message);
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'Message 1',
            $contents
        );
        self::assertStringContainsString(
            'Sent 1 message',
            $contents
        );
        self::assertStringContainsString(
            'Headers',
            $contents
        );
        self::assertStringContainsString(
            'Plain Message',
            $contents
        );
        self::assertStringContainsString(
            'Attachments',
            $contents
        );
        self::assertStringNotContainsString(
            'HTML Message',
            $contents
        );
        self::assertStringNotContainsString(
            'Inline Attachments',
            $contents
        );
        $message->setHtmlMessage('<b>Hello!</b><img src="cid:foo">')
            ->setInlineAttachment(__DIR__ . '/../logo-circle.png', 'foo');
        \sleep(5);
        $mailer->send($message);
        $contents = $this->collector->getContents();
        self::assertStringContainsString(
            'Message 1',
            $contents
        );
        self::assertStringContainsString(
            'Message 2',
            $contents
        );
        self::assertStringNotContainsString(
            'Sent 1 message',
            $contents
        );
        self::assertStringContainsString(
            'Sent 2 messages',
            $contents
        );
        self::assertStringContainsString(
            'HTML Message',
            $contents
        );
        self::assertStringContainsString(
            'Inline Attachments',
            $contents
        );
    }

    public function testActivities() : void
    {
        $message = new Message();
        $message->addTo((string) \getenv('SMTP_ADDRESS'))
            ->setFrom((string) \getenv('SMTP_ADDRESS'))
            ->setPlainMessage('Foo');
        \sleep(5);
        $this->makeMailer()->send($message);
        self::assertSame(
            [
                'collector',
                'class',
                'description',
                'start',
                'end',
            ],
            \array_keys($this->collector->getActivities()[0])
        );
    }
}
