<?php namespace Tests\Email;

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
		$this->smtp = new SMTP('user');
	}

	public function testCRLF()
	{
		$this->assertEquals("\r\n", $this->smtp->getCRLF());
	}

	public function testCharset()
	{
		$this->assertEquals('utf-8', $this->smtp->getCharset());
	}
}
