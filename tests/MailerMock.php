<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Email;

use Framework\Email\Mailer;

class MailerMock extends Mailer
{
    public function isSuccessCode(false | int $code) : bool
    {
        return parent::isSuccessCode($code);
    }
}
