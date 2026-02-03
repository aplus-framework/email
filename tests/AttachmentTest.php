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

use Framework\Email\Attachment;
use PHPUnit\Framework\TestCase;

final class AttachmentTest extends TestCase
{
    public function testFilename() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame(__FILE__, $attachment->getFilename());
    }

    public function testFilenameNotFound() : void
    {
        $filename = __DIR__ . '/unknown-file.png';
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Attachment file not found: ' . $filename);
        new Attachment($filename);
    }

    public function testName() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame('AttachmentTest.php', $attachment->getName());
    }

    public function testCustomName() : void
    {
        $attachment = new Attachment(__FILE__, 'foo.js');
        self::assertSame('foo.js', $attachment->getName());
    }

    public function testMimeType() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame('text/x-php', $attachment->getMimeType());
    }

    public function testCustomMimeType() : void
    {
        $attachment = new Attachment(__FILE__, mimeType: 'image/jpeg');
        self::assertSame('image/jpeg', $attachment->getMimeType());
    }

    public function testContents() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame(\file_get_contents(__FILE__), $attachment->getContents());
    }

    public function testBase64Contents() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame(
            \base64_encode(\file_get_contents(__FILE__)), // @phpstan-ignore-line
            $attachment->getBase64Contents()
        );
    }

    public function testBase64SplitContents() : void
    {
        $attachment = new Attachment(__FILE__);
        self::assertSame(
            \chunk_split(\base64_encode(\file_get_contents(__FILE__))), // @phpstan-ignore-line
            $attachment->getBase64SplitContents()
        );
        self::assertSame(
            \chunk_split(\base64_encode(\file_get_contents(__FILE__)), 100), // @phpstan-ignore-line
            $attachment->getBase64SplitContents(100)
        );
        self::assertSame(
            \chunk_split(\base64_encode(\file_get_contents(__FILE__)), 100, 'foo'), // @phpstan-ignore-line
            $attachment->getBase64SplitContents(100, 'foo')
        );
    }
}
