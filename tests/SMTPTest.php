<?php namespace Tests\Email;

use Framework\Email\Message;
use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

class SMTPTest extends TestCase
{
	/**
	 * @var SMTP
	 */
	protected $smtp;

	public function setup() : void
	{
		$this->smtp = new SMTP([
			'server' => \getenv('SMTP_SERVER'),
			'port' => \getenv('SMTP_PORT'),
			'username' => \getenv('SMTP_USERNAME'),
			'password' => \getenv('SMTP_PASSWORD'),
		]);
	}

	public function testCRLF()
	{
		$this->assertEquals("\r\n", $this->smtp->getCRLF());
	}

	public function testCharset()
	{
		$this->assertEquals('utf-8', $this->smtp->getCharset());
	}

	public function testSend()
	{
		$message = new Message($this->smtp);
		$message->addTo(\getenv('SMTP_ADDRESS'));
		$message->setFrom(\getenv('SMTP_ADDRESS'));
		$message->setPlainMessage('<b>Hello!</b>');
		$message->setHTMLMessage('<b>Hello!</b>');
		$this->assertTrue($this->smtp->send($message));
	}
}
