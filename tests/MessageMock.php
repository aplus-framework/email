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
use Override;

class MessageMock extends Message
{
    #[Override]
    public function getCharset() : string
    {
        return parent::getCharset();
    }

    #[Override]
    public function getCrlf() : string
    {
        return parent::getCrlf();
    }

    #[Override]
    public function getBoundary() : string
    {
        return parent::getBoundary();
    }

    #[Override]
    public function renderHeaders() : string
    {
        return parent::renderHeaders();
    }

    #[Override]
    public function extractEmails(?string $header) : array
    {
        return parent::extractEmails($header);
    }

    #[Override]
    public static function formatAddress(string $address, ?string $name = null) : string
    {
        return parent::formatAddress($address, $name);
    }

    #[Override]
    public static function formatAddressList(array $addresses) : string
    {
        return parent::formatAddressList($addresses);
    }
}
