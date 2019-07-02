<?php namespace Tests\Email;

use Framework\Email\SMTP;
use PHPUnit\Framework\TestCase;

class SMTPTest extends TestCase
{
	/**
	 * @var SMTP
	 */
	protected $smtp;

	public function setup()
	{
		$this->smtp = new SMTP('localhost');
	}

	public function testCRLF()
	{
		$this->assertEquals("\r\n", $this->smtp->getCRLF());
		$this->smtp->setCRLF("\n");
		$this->assertEquals("\n", $this->smtp->getCRLF());
	}
}
