<?php declare(strict_types=1);
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Framework\Email;

/**
 * Enum XPriority.
 *
 * @package email
 */
enum XPriority : int
{
    case HIGHEST = 1;
    case HIGH = 2;
    case NORMAL = 3;
    case LOW = 4;
    case LOWEST = 5;
}
