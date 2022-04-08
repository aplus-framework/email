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

use Framework\Email\Mailers\SMTPMailer;
use Framework\Email\Message;
use Framework\Email\XPriority;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    protected MessageMock $message;

    public function setup() : void
    {
        $this->message = new MessageMock();
        $this->message->setMailer(new SMTPMailer('localhost'));
    }

    public function testBoundary() : void
    {
        self::assertSame(32, \strlen($this->message->getBoundary()));
    }

    public function testFrom() : void
    {
        self::assertSame([], $this->message->getFrom());
        self::assertNull($this->message->getFromAddress());
        self::assertNull($this->message->getFromName());
        $this->message->setFrom('foo@bar.com', 'Foo');
        self::assertSame(['foo@bar.com', 'Foo'], $this->message->getFrom());
        self::assertSame('foo@bar.com', $this->message->getFromAddress());
        self::assertSame('Foo', $this->message->getFromName());
    }

    public function testHeaders() : void
    {
        self::assertSame(['mime-version' => '1.0'], $this->message->getHeaders());
        self::assertSame('1.0', $this->message->getHeader('MIME-Version'));
        $this->message->setHeader('To', 'foo@bar');
        self::assertSame(
            ['mime-version' => '1.0', 'to' => 'foo@bar'],
            $this->message->getHeaders()
        );
        $this->message->setHeader('MIME-Version', '2.0');
        self::assertSame(
            ['mime-version' => '2.0', 'to' => 'foo@bar'],
            $this->message->getHeaders()
        );
        self::assertSame(
            "MIME-Version: 2.0\r\nTo: foo@bar",
            $this->message->renderHeaders()
        );
    }

    public function testDate() : void
    {
        self::assertNull($this->message->getDate());
        self::assertNull($this->message->getHeader('Date'));
        $this->message->setDate();
        self::assertSame(\date('r'), $this->message->getDate());
        self::assertSame(\date('r'), $this->message->getHeader('Date'));
    }

    public function testXPriority() : void
    {
        self::assertNull($this->message->getXPriority());
        self::assertNull($this->message->getHeader('X-Priority'));
        $this->message->setXPriority(XPriority::LOW);
        self::assertSame(XPriority::LOW, $this->message->getXPriority());
        self::assertSame(
            (string) XPriority::LOW->value,
            $this->message->getHeader('X-Priority')
        );
        self::assertSame('4', $this->message->getHeader('X-Priority'));
    }

    public function testReplyTo() : void
    {
        self::assertSame([], $this->message->getReplyTo());
        $this->message->addReplyTo('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getReplyTo());
        $this->message->addReplyTo('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getReplyTo());
    }

    public function testBcc() : void
    {
        self::assertSame([], $this->message->getBcc());
        $this->message->addBcc('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getBcc());
        $this->message->addBcc('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getBcc());
    }

    public function testCc() : void
    {
        self::assertSame([], $this->message->getCc());
        $this->message->addCc('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getCc());
        $this->message->addCc('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getCc());
    }

    public function testTo() : void
    {
        self::assertSame([], $this->message->getTo());
        $this->message->addTo('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getTo());
        $this->message->addTo('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getTo());
    }

    public function testSubject() : void
    {
        self::assertNull($this->message->getSubject());
        $this->message->setSubject('Hello');
        self::assertSame('Hello', $this->message->getSubject());
    }

    public function testAttachments() : void
    {
        self::assertEmpty($this->message->getAttachments());
        $this->message->addAttachment(__FILE__);
        self::assertSame([__FILE__], $this->message->getAttachments());
        self::assertStringContainsString(
            'application/octet-stream; name="MessageTest.php"',
            $this->message->renderAttachments()
        );
    }

    public function testInvalidAttachmentPath() : void
    {
        $this->message->addAttachment(__DIR__);
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Attachment file not found: ' . __DIR__);
        $this->message->renderAttachments();
    }

    public function testInlineAttachments() : void
    {
        self::assertEmpty($this->message->getInlineAttachments());
        $this->message->setInlineAttachment(__FILE__, 'abc123');
        self::assertSame([
            'abc123' => __FILE__,
        ], $this->message->getInlineAttachments());
    }

    public function testInvalidInlineAttachmentPath() : void
    {
        $this->message->setInlineAttachment(__DIR__, 'foobar');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Inline attachment file not found: ' . __DIR__);
        $this->message->renderInlineAttachments();
    }

    public function testInlineAttachmentsContents() : void
    {
        self::assertEmpty($this->message->getInlineAttachments());
        $this->message->setInlineAttachment(__FILE__, 'foobar');
        self::assertSame(['foobar' => __FILE__], $this->message->getInlineAttachments());
        self::assertStringContainsString(
            'Content-ID: foobar',
            $this->message->renderInlineAttachments()
        );
    }

    public function testRecipients() : void
    {
        self::assertSame([], $this->message->getRecipients());
        $this->message->addTo('foo@bar');
        $this->message->addTo('foo@bar');
        $this->message->addCc('baz@bar');
        $this->message->addBcc('foo@baz');
        self::assertSame([
            'foo@bar',
            'baz@bar',
        ], $this->message->getRecipients());
    }

    public function testPlainMessage() : void
    {
        self::assertNull($this->message->getPlainMessage());
        $this->message->setPlainMessage('Hi');
        self::assertSame('Hi', $this->message->getPlainMessage());
        self::assertStringContainsString(
            'Content-Type: text/plain; charset=utf-8',
            $this->message->renderPlainMessage()
        );
    }

    public function testHtmlMessage() : void
    {
        self::assertNull($this->message->getHtmlMessage());
        $this->message->setHtmlMessage('<b>Hi</b>');
        self::assertSame('<b>Hi</b>', $this->message->getHtmlMessage());
        self::assertStringContainsString(
            'Content-Type: text/html; charset=utf-8',
            $this->message->renderHtmlMessage()
        );
    }

    public function testFormatAddress() : void
    {
        self::assertSame('foo@bar', MessageMock::formatAddress('foo@bar'));
        self::assertSame('"Foo Bar" <foo@bar>', MessageMock::formatAddress('foo@bar', 'Foo Bar'));
    }

    public function testFormatAddressList() : void
    {
        self::assertSame(
            'foo@bar, "Baz" <foo@baz>, "Foo" <foo@foo>',
            MessageMock::formatAddressList([
                'foo@bar' => null,
                'foo@baz' => 'Baz',
                'foo@foo' => 'Foo',
            ])
        );
    }

    protected function getRenderedResult() : string
    {
        $this->message->setFrom('foo@bar');
        $boundary = $this->message->getBoundary();
        return "MIME-Version: 1.0\r\n"
            . "From: foo@bar\r\n"
            . 'Date: ' . \date('r') . "\r\n"
            . "Content-Type: multipart/mixed; boundary=\"mixed-{$boundary}\"\r\n"
            . "\r\n"
            . "--mixed-{$boundary}\r\n"
            . "Content-Type: multipart/alternative; boundary=\"alt-{$boundary}\"\r\n"
            . "\r\n"
            . "--alt-{$boundary}--\r\n"
            . "\r\n"
            . "--mixed-{$boundary}--";
    }

    public function testRenderData() : void
    {
        self::assertStringContainsString(
            $this->getRenderedResult(),
            $this->message->renderData()
        );
    }

    public function testToString() : void
    {
        self::assertStringContainsString(
            $this->getRenderedResult(),
            (string) $this->message
        );
        $message = (string) new Message();
        self::assertStringContainsString('MIME-Version', $message);
        self::assertStringContainsString('Date', $message);
    }
}
