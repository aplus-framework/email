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

use LogicException;

class Attachment
{
    protected string $filename;
    protected ?string $name;
    protected ?string $mimeType;

    public function __construct(
        string $filename,
        ?string $name = null,
        ?string $mimeType = null
    ) {
        $this->setFilename($filename);
        $this->setName($name);
        $this->setMimeType($mimeType);
    }

    public function setFilename(string $filename) : static
    {
        if (!\is_file($filename)) {
            throw new LogicException('Attachment file not found: ' . $filename);
        }
        $this->filename = $filename;
        return $this;
    }

    public function getFilename() : string
    {
        return $this->filename;
    }

    public function setName(?string $name) : static
    {
        $this->name = $name;
        return $this;
    }

    public function getName() : string
    {
        if (isset($this->name)) {
            return $this->name;
        }
        $name = \pathinfo($this->getFilename(), \PATHINFO_BASENAME);
        return \htmlspecialchars($name, \ENT_QUOTES | \ENT_HTML5);
    }

    public function setMimeType(?string $mimeType) : static
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getMimeType() : ?string
    {
        if (isset($this->mimeType)) {
            return $this->mimeType;
        }
        return \mime_content_type($this->getFilename()) ?: 'application/octet-stream';
    }

    public function getContents() : string
    {
        return \file_get_contents($this->getFilename()); // @phpstan-ignore-line
    }

    public function getBase64Contents() : string
    {
        return \base64_encode($this->getContents());
    }

    /**
     * @param int<1,max> $length
     * @param string $separator
     *
     * @return string
     */
    public function getBase64SplitContents(int $length = 76, string $separator = "\r\n") : string
    {
        return \chunk_split($this->getBase64Contents(), $length, $separator);
    }
}
