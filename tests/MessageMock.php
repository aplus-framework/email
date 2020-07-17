<?php namespace Tests\Email;

use Framework\Email\Message;

class MessageMock extends Message
{
	public function renderHeaders() : string
	{
		return parent::renderHeaders();
	}

	public function renderAttachments() : string
	{
		return parent::renderAttachments();
	}

	public function renderInlineAttachments() : string
	{
		return parent::renderInlineAttachments();
	}

	public function renderPlainMessage() : ?string
	{
		return parent::renderPlainMessage();
	}

	public function renderHTMLMessage() : ?string
	{
		return parent::renderHTMLMessage();
	}

	public function renderData() : string
	{
		return parent::renderData();
	}

	public static function formatAddress(string $address, string $name = null) : string
	{
		return parent::formatAddress($address, $name);
	}

	public static function formatAddressList(array $addresses) : string
	{
		return parent::formatAddressList($addresses);
	}
}
