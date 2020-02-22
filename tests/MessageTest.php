<?php namespace Tests\Email;

use Framework\Email\Message;
use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
	protected Message $message;

	public function setup() : void
	{
		$this->message = new Message(new SMTP('localhost'), 'abc123');
	}

	public function testBoundary()
	{
		$this->assertEquals('abc123', $this->message->getBoundary());
		$this->message = new Message(new SMTP('localhost'));
		$this->assertEquals(32, \strlen($this->message->getBoundary()));
	}

	public function testFrom()
	{
		$this->assertEquals([], $this->message->getFrom());
		$this->assertNull($this->message->getFromAddress());
		$this->assertNull($this->message->getFromName());
		$this->message->setFrom('foo@bar.com', 'Foo');
		$this->assertEquals(['foo@bar.com', 'Foo'], $this->message->getFrom());
		$this->assertEquals('foo@bar.com', $this->message->getFromAddress());
		$this->assertEquals('Foo', $this->message->getFromName());
	}

	public function testHeaders()
	{
		$this->assertEquals(['MIME-Version' => '1.0'], $this->message->getHeaders());
		$this->assertEquals('1.0', $this->message->getHeader('MIME-Version'));
		$this->message->setHeader('to', 'foo@bar');
		$this->assertEquals(
			['MIME-Version' => '1.0', 'To' => 'foo@bar'],
			$this->message->getHeaders()
		);
		$this->message->setHeader('mime-version', '2.0');
		$this->assertEquals(
			['MIME-Version' => '2.0', 'To' => 'foo@bar'],
			$this->message->getHeaders()
		);
		$this->assertEquals(
			"MIME-Version: 2.0\r\nTo: foo@bar\r\n",
			$this->message->renderHeaders()
		);
	}

	public function testDate()
	{
		$this->assertNull($this->message->getDate());
		$this->assertNull($this->message->getHeader('Date'));
		$this->message->setDate();
		$this->assertEquals(\date('r'), $this->message->getDate());
		$this->assertEquals(\date('r'), $this->message->getHeader('Date'));
	}

	public function testPriority()
	{
		$this->assertEquals(3, $this->message->getPriority());
		$this->assertNull($this->message->getHeader('X-Priority'));
		$this->message->setPriority(4);
		$this->assertEquals(4, $this->message->getPriority());
		$this->assertEquals(4, $this->message->getHeader('X-Priority'));
	}

	public function testReplyTo()
	{
		$this->assertEquals([], $this->message->getReplyTo());
		$this->message->addReplyTo('foo@bar');
		$this->assertEquals([
			'foo@bar' => null,
		], $this->message->getReplyTo());
		$this->message->addReplyTo('foo@baz', 'Baz');
		$this->assertEquals([
			'foo@bar' => null,
			'foo@baz' => 'Baz',
		], $this->message->getReplyTo());
	}

	public function testBcc()
	{
		$this->assertEquals([], $this->message->getBcc());
		$this->message->addBcc('foo@bar');
		$this->assertEquals([
			'foo@bar' => null,
		], $this->message->getBcc());
		$this->message->addBcc('foo@baz', 'Baz');
		$this->assertEquals([
			'foo@bar' => null,
			'foo@baz' => 'Baz',
		], $this->message->getBcc());
	}

	public function testCc()
	{
		$this->assertEquals([], $this->message->getCc());
		$this->message->addCc('foo@bar');
		$this->assertEquals([
			'foo@bar' => null,
		], $this->message->getCc());
		$this->message->addCc('foo@baz', 'Baz');
		$this->assertEquals([
			'foo@bar' => null,
			'foo@baz' => 'Baz',
		], $this->message->getCc());
	}

	public function testTo()
	{
		$this->assertEquals([], $this->message->getTo());
		$this->message->addTo('foo@bar');
		$this->assertEquals([
			'foo@bar' => null,
		], $this->message->getTo());
		$this->message->addTo('foo@baz', 'Baz');
		$this->assertEquals([
			'foo@bar' => null,
			'foo@baz' => 'Baz',
		], $this->message->getTo());
	}

	public function testSubject()
	{
		$this->assertNull($this->message->getSubject());
		$this->message->setSubject('Hello');
		$this->assertEquals('Hello', $this->message->getSubject());
	}

	public function testAttachments()
	{
		$this->assertEmpty($this->message->getAttachments());
		$this->message->addAttachment(__FILE__);
		$this->assertEquals([__FILE__], $this->message->getAttachments());
		$this->assertStringContainsString(
			'application/octet-stream; name="MessageTest.php"',
			$this->message->renderAttachments()
		);
	}

	public function testInvalidAttachmentPath()
	{
		$this->message->addAttachment(__DIR__);
		$this->expectException(\LogicException::class);
		$this->expectExceptionMessage('Attachment file not found: ' . __DIR__);
		$this->message->renderAttachments();
	}

	public function testInlineAttachments()
	{
		$this->assertEmpty($this->message->getInlineAttachments());
		$contents = \file_get_contents(__FILE__);
		$this->message->setInlineAttachment($contents, 'abc123');
		$this->assertEquals([
			'abc123' => $contents,
		], $this->message->getInlineAttachments());
	}

	public function testRecipients()
	{
		$this->assertEquals([], $this->message->getRecipients());
		$this->message->addTo('foo@bar');
		$this->message->addTo('foo@bar');
		$this->message->addCc('baz@bar');
		$this->message->addBcc('foo@baz');
		$this->assertEquals([
			'foo@bar',
			'baz@bar',
		], $this->message->getRecipients());
	}

	public function testPlainMessage()
	{
		$this->assertNull($this->message->getPlainMessage());
		$this->message->setPlainMessage('Hi');
		$this->assertEquals('Hi', $this->message->getPlainMessage());
		$this->assertStringContainsString(
			'Content-Type: text/plain; charset=utf-8',
			$this->message->renderPlainMessage()
		);
	}

	public function testHTMLMessage()
	{
		$this->assertNull($this->message->getHTMLMessage());
		$this->message->setHTMLMessage('<b>Hi</b>');
		$this->assertEquals('<b>Hi</b>', $this->message->getHTMLMessage());
		$this->assertStringContainsString(
			'Content-Type: text/html; charset=utf-8',
			$this->message->renderHTMLMessage()
		);
	}

	public function testFormatAddress()
	{
		$this->assertEquals('foo@bar', Message::formatAddress('foo@bar'));
		$this->assertEquals('"Foo Bar" <foo@bar>', Message::formatAddress('foo@bar', 'Foo Bar'));
	}

	public function testFormatAddressList()
	{
		$this->assertEquals(
			'foo@bar, "Baz" <foo@baz>, "Foo" <foo@foo>',
			Message::formatAddressList([
				'foo@bar' => null,
				'foo@baz' => 'Baz',
				'foo@foo' => 'Foo',
			])
		);
	}

	public function testRenderData()
	{
		$this->message->setFrom('foo@bar');
		$this->assertStringContainsString(
			"From: foo@bar\r\nTo: \r\nContent-Type: multipart/mixed; boundary=\"mixed-abc123\"\r\n\r\n--mixed-abc123\r\nContent-Type: multipart/alternative; boundary=\"alt-abc123\"\r\n\r\n--alt-abc123--\r\n\r\n--mixed-abc123--",
			$this->message->renderData()
		);
	}

	public function testToString()
	{
		$this->message->setFrom('foo@bar');
		$this->assertStringContainsString(
			"From: foo@bar\r\nTo: \r\nContent-Type: multipart/mixed; boundary=\"mixed-abc123\"\r\n\r\n--mixed-abc123\r\nContent-Type: multipart/alternative; boundary=\"alt-abc123\"\r\n\r\n--alt-abc123--\r\n\r\n--mixed-abc123--",
			(string) $this->message
		);
	}
}
