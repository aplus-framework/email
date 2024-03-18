<?php
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Tests\Email\Debug;

use Framework\Email\Debug\EmailCollection;
use PHPUnit\Framework\TestCase;

final class EmailCollectionTest extends TestCase
{
    protected EmailCollection $collection;

    protected function setUp() : void
    {
        $this->collection = new EmailCollection('Email');
    }

    public function testIconPath() : void
    {
        self::assertStringStartsWith('<svg ', $this->collection->getIcon());
    }
}
