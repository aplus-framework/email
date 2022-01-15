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

use Framework\Debug\Collector;

/**
 * Class EmailCollector.
 *
 * @package email
 */
class EmailCollector extends Collector
{
    public function getContents() : string
    {
        \ob_start();
        if ( ! $this->hasData()) {
            echo '<p>No message sent.</p>';
            return \ob_get_clean(); // @phpstan-ignore-line
        }
        $count = \count($this->getData()); ?>
        <p>Sent <?= $count ?> message<?= $count === 1 ? '' : 's' ?>:</p>
        <?php foreach ($this->getData() as $index => $data) : ?>
        <h2>Message <?= $index + 1 ?></h2>
        <p><strong>Status:</strong> <?= $data['code'] === 250 ? 'OK' : 'Error' ?></p>
        <p><strong>From:</strong> <?= \htmlentities($data['from']) ?></p>
        <p><strong>Recipients:</strong> <?= \htmlentities(\implode(', ', $data['recipients'])) ?></p>
        <p><strong>Size:</strong> <?= $this->toSize($data['length']) ?></p>
        <h3>Headers</h3>
        <table>
            <?php foreach ($data['headers'] as $name => $value): ?>
                <tr>
                    <th><?= \htmlentities($name) ?></th>
                    <td><?= \htmlentities($value) ?></td>
                </tr>
            <?php endforeach ?>
        </table>
        <?php if (isset($data['html'])): ?>
            <h3>HTML Message</h3>
            <pre><?= \htmlentities($data['html']) ?></pre>
        <?php endif ?>
        <?php if (isset($data['plain'])): ?>
            <h3>Plain Message</h3>
            <pre><?= \htmlentities($data['plain']) ?></pre>
        <?php endif ?>
        <?php if ($data['attachments']): ?>
            <h3>Attachments</h3>
            <table>
                <thead>
                <tr>
                    <th>File</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data['attachments'] as $attachment): ?>
                    <tr>
                        <td><?= \htmlentities(\realpath($attachment)); // @phpstan-ignore-line?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
        <?php if ($data['inlineAttachments']): ?>
            <h3>Inline Attachments</h3>
            <table>
                <thead>
                <tr>
                    <th>Content-ID</th>
                    <th>File</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data['inlineAttachments'] as $cid => $filename): ?>
                    <tr>
                        <td><?= \htmlentities($cid) ?></td>
                        <td><?= \htmlentities(\realpath($filename)); // @phpstan-ignore-line?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        <?php endif ?>
        <hr>
        <?php endforeach ?>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function toSize(int $size) : string
    {
        $num = 1024 * 1024 * 1024;
        if ($size >= $num) {
            return \round($size / $num, 3) . ' GB';
        }
        $num = 1024 * 1024;
        if ($size >= $num) {
            return \round($size / $num, 3) . ' MB';
        }
        $num = 1024;
        if ($size >= $num) {
            return \round($size / $num, 3) . ' KB';
        }
        return $size . ' Bytes';
    }
}
