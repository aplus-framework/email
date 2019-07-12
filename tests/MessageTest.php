<?php namespace Tests\Email;

use Framework\Email\Message;
use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

class MessageTest extends TestCase
{
	/**
	 * @var Message
	 */
	protected $message;

	public function setup() : void
	{
		$this->message = new Message(new SMTP('localhost'));
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
}
