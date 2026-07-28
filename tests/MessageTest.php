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

use Framework\Email\Attachment;
use Framework\Email\Header;
use Framework\Email\Mailer;
use Framework\Email\Message;
use Framework\Email\XPriority;
use PHPUnit\Framework\TestCase;

final class MessageTest extends TestCase
{
    protected MessageMock $message;

    public function setup() : void
    {
        $this->message = new MessageMock();
        $this->message->setMailer(new Mailer('localhost'));
    }

    public function testToString() : void
    {
        $this->message->setPlainContent('Foo baz');
        $base64 = \base64_encode('Foo baz');
        self::assertStringContainsString($base64, $this->message->toString());
        self::assertStringContainsString($base64, (string) $this->message);
    }

    public function testCrlf() : void
    {
        self::assertSame("\r\n", $this->message->getCrlf());
        $message = $this->makeMessage();
        self::assertSame("\r\n", $message->getCrlf());
    }

    protected function makeMessage() : MessageMock
    {
        return new class() extends MessageMock
        {
            public function getCharset() : string
            {
                return parent::getCharset();
            }

            public function getCrlf() : string
            {
                return parent::getCrlf();
            }
        };
    }

    public function testCharset() : void
    {
        self::assertSame('utf-8', $this->message->getCharset());
        $message = $this->makeMessage();
        self::assertSame('utf-8', $message->getCharset());
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
        $this->message->setFrom('foo@bar.com');
        self::assertSame([
            'address' => 'foo@bar.com',
            'name' => null,
        ], $this->message->getFrom());
        self::assertSame(
            'foo@bar.com',
            $this->message->getHeader(Header::FROM)
        );
        $this->message->setFrom('foo@bar.com', 'Foo');
        self::assertSame([
            'address' => 'foo@bar.com',
            'name' => 'Foo',
        ], $this->message->getFrom());
        self::assertSame(
            '"Foo" <foo@bar.com>',
            $this->message->getHeader(Header::FROM)
        );
        self::assertSame('foo@bar.com', $this->message->getFromAddress());
        self::assertSame('Foo', $this->message->getFromName());
        $this->message->removeFrom();
        self::assertSame([], $this->message->getFrom());
        self::assertNull($this->message->getHeader(Header::FROM));
        self::assertNull($this->message->getFromAddress());
        self::assertNull($this->message->getFromName());
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

    public function testRemoveHeader() : void
    {
        self::assertSame('1.0', $this->message->getHeader('MIME-Version'));
        $this->message->removeHeader('MIME-Version');
        self::assertNull($this->message->getHeader('MIME-Version'));
    }

    public function testSanitizeSpacesInHeaderLines() : void
    {
        $value = "  Foo bar\nNew          line\n áéíóú  😄\n";
        $this->message->setSubject($value);
        self::assertSame($value, $this->message->getSubject());
        self::assertSame(
            'Subject: =?UTF-8?B?' . \base64_encode('Foo bar New line áéíóú 😄') . '?=',
            $this->message->getHeaderLines()['subject']
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

    public function testXMailer() : void
    {
        self::assertNull($this->message->getXMailer());
        self::assertNull($this->message->getHeader('X-Mailer'));
        $this->message->setXMailer();
        self::assertSame('Aplus Mailer', $this->message->getXMailer());
        self::assertSame('Aplus Mailer', $this->message->getHeader('X-Mailer'));
        $this->message->setXMailer('Foo Bar');
        self::assertSame('Foo Bar', $this->message->getXMailer());
        self::assertSame('Foo Bar', $this->message->getHeader('X-Mailer'));
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
        self::assertNull($this->message->getHeader(Header::REPLY_TO));
        $this->message->addReplyTo('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getReplyTo());
        self::assertSame(
            'foo@bar',
            $this->message->getHeader(Header::REPLY_TO)
        );
        $this->message->addReplyTo('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getReplyTo());
        self::assertSame(
            'foo@bar, "Baz" <foo@baz>',
            $this->message->getHeader(Header::REPLY_TO)
        );
        $this->message->removeReplyTo();
        self::assertSame([], $this->message->getReplyTo());
        self::assertNull($this->message->getHeader(Header::REPLY_TO));
    }

    public function testBcc() : void
    {
        self::assertSame([], $this->message->getBcc());
        self::assertNull($this->message->getHeader(Header::BCC));
        $this->message->addBcc('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getBcc());
        self::assertSame(
            'foo@bar',
            $this->message->getHeader(Header::BCC)
        );
        $this->message->addBcc('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getBcc());
        self::assertSame(
            'foo@bar, "Baz" <foo@baz>',
            $this->message->getHeader(Header::BCC)
        );
        $this->message->removeBcc();
        self::assertSame([], $this->message->getBcc());
        self::assertNull($this->message->getHeader(Header::BCC));
    }

    public function testCc() : void
    {
        self::assertSame([], $this->message->getCc());
        self::assertNull($this->message->getHeader(Header::CC));
        $this->message->addCc('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getCc());
        self::assertSame(
            'foo@bar',
            $this->message->getHeader(Header::CC)
        );
        $this->message->addCc('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getCc());
        self::assertSame(
            'foo@bar, "Baz" <foo@baz>',
            $this->message->getHeader(Header::CC)
        );
        $this->message->removeCc();
        self::assertSame([], $this->message->getCc());
        self::assertNull($this->message->getHeader(Header::CC));
    }

    public function testTo() : void
    {
        self::assertSame([], $this->message->getTo());
        self::assertNull($this->message->getHeader(Header::TO));
        $this->message->addTo('foo@bar');
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->getTo());
        self::assertSame(
            'foo@bar',
            $this->message->getHeader(Header::TO)
        );
        $this->message->addTo('foo@baz', 'Baz');
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => 'Baz',
        ], $this->message->getTo());
        self::assertSame(
            'foo@bar, "Baz" <foo@baz>',
            $this->message->getHeader(Header::TO)
        );
        $this->message->removeTo();
        self::assertSame([], $this->message->getTo());
        self::assertNull($this->message->getHeader(Header::TO));
    }

    public function testSubject() : void
    {
        self::assertNull($this->message->getSubject());
        $this->message->setSubject('Hello');
        self::assertSame('Hello', $this->message->getSubject());
    }

    public function testInvalidAttachmentPath() : void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Attachment file not found: ' . __DIR__);
        $this->message->addAttachment(__DIR__);
    }

    public function testInlineAttachments() : void
    {
        self::assertEmpty($this->message->getInlineAttachments());
        $this->message->setInlineAttachment(__FILE__, 'abc123');
        $attachments['abc123'] = new Attachment(__FILE__);
        self::assertEquals($attachments, $this->message->getInlineAttachments());
    }

    public function testInvalidInlineAttachmentPath() : void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Attachment file not found: ' . __DIR__);
        $this->message->setInlineAttachment(__DIR__, 'foobar');
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
            'foo@baz',
        ], $this->message->getRecipients());
    }

    public function testBody() : void
    {
        self::assertNull($this->message->getBody());
        $this->message->setBody('<b>Hi</b>');
        self::assertSame('<b>Hi</b>', $this->message->getBody());
        self::assertSame('<b>Hi</b>', $this->message->getHtmlContent());
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

    public function testSendWithEmptyFromAddress() : void
    {
        $this->message->removeFrom();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "The message 'From' address is empty"
        );
        $this->message->validate();
    }

    public function testSendWithInvalidFromAddress() : void
    {
        $this->message->setFrom('foo');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "The message 'From' address 'foo' is not a valid email"
        );
        $this->message->validate();
    }

    public function testSendWithoutToAddress() : void
    {
        $this->message->setFrom('foo@bar.com');
        $this->message->removeTo();
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "The message 'To' address is empty"
        );
        $this->message->validate();
    }

    public function testSendWithoutSubject() : void
    {
        $this->message->setFrom('foo@bar.com')->addTo('foo@baz');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "The message 'Subject' is empty"
        );
        $this->message->validate();
    }

    public function testSendWithoutBody() : void
    {
        $this->message->setFrom('foo@bar.com')
            ->addTo('foo@baz')
            ->setSubject('Foo bar');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The message body is empty'
        );
        $this->message->validate();
    }

    public function testSendWithBodyAsEmptyString() : void
    {
        $this->message->setFrom('foo@bar.com')
            ->addTo('foo@baz')
            ->setSubject('Foo bar')
            ->setHtmlContent('');
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'The message body is empty'
        );
        $this->message->validate();
    }

    public function testExtractEmails() : void
    {
        $header = null;
        self::assertSame([], $this->message->extractEmails($header));
        $header = 'foo@bar';
        self::assertSame([
            'foo@bar' => null,
        ], $this->message->extractEmails($header));
        $header = 'foo@bar, foo@baz';
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => null,
        ], $this->message->extractEmails($header));
        $header = 'foo@bar, foo@baz, "Foo Foo" <foo@foo.com>"';
        self::assertSame([
            'foo@bar' => null,
            'foo@baz' => null,
            'foo@foo.com' => 'Foo Foo',
        ], $this->message->extractEmails($header));
        $header = '"John   Doe" <john@doe.com>, foo@bar, foo@baz, "Foo Foo" <foo@foo.com>"';
        self::assertSame([
            'john@doe.com' => 'John Doe',
            'foo@bar' => null,
            'foo@baz' => null,
            'foo@foo.com' => 'Foo Foo',
        ], $this->message->extractEmails($header));
        $header = ' " John    Doe " <  john@doe.com >, foo @  bar, foo @baz ,"Foo   Foo"   <  foo@ foo.com >"';
        self::assertSame([
            'john@doe.com' => 'John Doe',
            'foo@bar' => null,
            'foo@baz' => null,
            'foo@foo.com' => 'Foo Foo',
        ], $this->message->extractEmails($header));
    }

    public function testHtmlOnly() : void
    {
        $this->message->setHtmlContent('Foo bar');
        self::assertStringContainsString(
            'Content-Type: text/html',
            $this->message->toString()
        );
    }

    public function testPlainOnly() : void
    {
        $this->message->setPlainContent('Foo bar');
        self::assertStringContainsString(
            'Content-Type: text/plain',
            $this->message->toString()
        );
    }

    public function testAlternative() : void
    {
        $this->message->setHtmlContent('Foo bar');
        $this->message->setPlainContent('Foo bar');
        $message = $this->message->toString();
        self::assertStringContainsString(
            'Content-Type: multipart/alternative',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: text/html',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: text/plain',
            $message
        );
    }

    public function testMixedAndInline() : void
    {
        $this->message->setHtmlContent('Foo bar');
        $this->message->setPlainContent('Foo bar');
        $this->message->setInlineAttachment(__DIR__ . '/logo-circle.png', 'logo');
        $this->message->addAttachment(__DIR__ . '/logo-circle.png');
        $message = $this->message->toString();
        self::assertStringContainsString(
            'Content-Type: multipart/mixed',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: multipart/alternative',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: multipart/related',
            $message
        );
    }

    public function testMixed() : void
    {
        $this->message->setHtmlContent('Foo bar');
        $this->message->setPlainContent('Foo bar');
        $this->message->addAttachment(__DIR__ . '/logo-circle.png');
        $message = $this->message->toString();
        self::assertStringContainsString(
            'Content-Type: multipart/mixed',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: multipart/alternative',
            $message
        );
        self::assertStringNotContainsString(
            'Content-Type: multipart/related',
            $message
        );
    }

    public function testInline() : void
    {
        $this->message->setHtmlContent('Foo bar');
        $this->message->setPlainContent('Foo bar');
        // $this->message->addAttachment(__DIR__ . '/logo-circle.png');
        $this->message->setInlineAttachment(__DIR__ . '/logo-circle.png', 'logo');
        $message = $this->message->toString();
        self::assertStringContainsString(
            'Content-Type: multipart/related',
            $message
        );
        self::assertStringContainsString(
            'Content-Type: multipart/alternative',
            $message
        );
    }

    public function testNoMethodToRenderData() : void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No method found to render data');
        $this->message->toString();
    }
}
