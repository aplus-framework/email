<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Email\Debug;

use Framework\Debug\Collection;

/**
 * Class EmailCollection.
 *
 * @package email
 */
class EmailCollection extends Collection
{
    protected string $iconPath = __DIR__ . '/icon/email.svg';
}
