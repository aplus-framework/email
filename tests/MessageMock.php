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

use Framework\Email\Message;

class MessageMock extends Message
{
    public function getBoundary() : string
    {
        return parent::getBoundary();
    }

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

    public function renderHtmlMessage() : ?string
    {
        return parent::renderHtmlMessage();
    }

    public function renderData() : string
    {
        return parent::renderData();
    }

    public static function formatAddress(string $address, ?string $name = null) : string
    {
        return parent::formatAddress($address, $name);
    }

    public static function formatAddressList(array $addresses) : string
    {
        return parent::formatAddressList($addresses);
    }
}
