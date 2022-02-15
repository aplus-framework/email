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

use Framework\Email\Header;
use PHPUnit\Framework\TestCase;

final class HeaderTest extends TestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testNames() : void
    {
        self::assertSame('From', Header::getName('from'));
        Header::setName('FRom');
        self::assertSame('FRom', Header::getName('from'));
    }

    public function testConstants() : void
    {
        $reflection = new \ReflectionClass(Header::class);
        foreach ($reflection->getConstants() as $name => $value) {
            self::assertSame(\strtoupper($name), $name);
            self::assertSame(Header::getName($value), $value);
            $name = \strtr(\strtolower($name), ['_' => '-']);
            $value = \strtolower($value);
            self::assertSame($name, $value);
        }
    }
}
