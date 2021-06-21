<?php namespace Tests\Email;

use Framework\Email\Message;
use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

final class SMTPTest extends TestCase
{
	protected SMTP $smtp;

	public function setup() : void
	{
		$this->smtp = new SMTP([
			'server' => \getenv('SMTP_SERVER'),
			'port' => \getenv('SMTP_PORT'),
			'username' => \getenv('SMTP_USERNAME'),
			'password' => \getenv('SMTP_PASSWORD'),
		]);
	}

	public function testCRLF() : void
	{
		self::assertSame("\r\n", $this->smtp->getCRLF());
	}

	public function testCharset() : void
	{
		self::assertSame('utf-8', $this->smtp->getCharset());
	}

	protected function getMessage() : Message
	{
		return (new Message($this->smtp))
			->addTo((string) \getenv('SMTP_ADDRESS'))
			->setFrom((string) \getenv('SMTP_ADDRESS'))
			->setPlainMessage('<b>Hello!</b><img src="cid:abc123">')
			->setHTMLMessage('<b>Hello!</b><img src="cid:abc123">')
			->setInlineAttachment(__DIR__ . '/logo-circle.png', 'abc123')
			->addAttachment(__FILE__);
	}

	public function testSend() : void
	{
		self::assertTrue($this->smtp->send($this->getMessage()));
	}

	public function testLogs() : void
	{
		$this->smtp->send($this->getMessage());
		self::assertSame([
			0 => '',
			1 => '220 smtp.mailtrap.io ESMTP ready',
		], $this->smtp->getLogs()[0]);
		self::assertSame([
			0 => 'EHLO ' . \gethostname(),
			1 => '250-smtp.mailtrap.io
250-SIZE 5242880
250-PIPELINING
250-ENHANCEDSTATUSCODES
250-8BITMIME
250-DSN
250-AUTH PLAIN LOGIN CRAM-MD5
250 STARTTLS',
		], $this->smtp->getLogs()[1]);
		self::assertSame([
			0 => 'STARTTLS',
			1 => '220 2.0.0 Start TLS',
		], $this->smtp->getLogs()[2]);
		self::assertSame([
			0 => 'EHLO ' . \gethostname(),
			1 => '250-smtp.mailtrap.io
250-SIZE 5242880
250-PIPELINING
250-ENHANCEDSTATUSCODES
250-8BITMIME
250-DSN
250 AUTH PLAIN LOGIN CRAM-MD5',
		], $this->smtp->getLogs()[3]);
		self::assertSame([
			0 => 'AUTH LOGIN',
			1 => '334 VXNlcm5hbWU6',
		], $this->smtp->getLogs()[4]);
		self::assertSame([
			0 => 'M2YxZjlkNjdjYTFkYTU=',
			1 => '334 UGFzc3dvcmQ6',
		], $this->smtp->getLogs()[5]);
		self::assertSame([
			0 => 'YjdhNTIwYTViMDg5YmM=',
			1 => '235 2.0.0 OK',
		], $this->smtp->getLogs()[6]);
		self::assertSame([
			0 => 'MAIL FROM: <db20690ae8-23245c@inbox.mailtrap.io>',
			1 => '250 2.1.0 Ok',
		], $this->smtp->getLogs()[7]);
		self::assertSame([
			0 => 'RCPT TO: <db20690ae8-23245c@inbox.mailtrap.io>',
			1 => '250 2.1.0 Ok',
		], $this->smtp->getLogs()[8]);
		self::assertSame([
			0 => 'DATA',
			1 => '354 Go ahead',
		], $this->smtp->getLogs()[9]);
	}
}
