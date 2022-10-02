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
use Framework\Debug\Debugger;
use Framework\Email\Header;
use Framework\Email\Mailer;

/**
 * Class EmailCollector.
 *
 * @package email
 */
class EmailCollector extends Collector
{
    protected Mailer $mailer;

    public function setMailer(Mailer $mailer) : static
    {
        $this->mailer = $mailer;
        return $this;
    }

    public function getActivities() : array
    {
        $activities = [];
        foreach ($this->getData() as $index => $data) {
            $activities[] = [
                'collector' => $this->getName(),
                'class' => static::class,
                'description' => 'Send message ' . ($index + 1),
                'start' => $data['start'],
                'end' => $data['end'],
            ];
        }
        return $activities;
    }

    public function getContents() : string
    {
        \ob_start();
        if ( ! isset($this->mailer)) {
            echo '<p>This collector has not been added to a Mailer instance.</p>';
            return \ob_get_clean(); // @phpstan-ignore-line
        }
        echo $this->showHeader();
        if ( ! $this->hasData()) {
            echo '<p>No messages have been sent.</p>';
            return \ob_get_clean(); // @phpstan-ignore-line
        }
        $count = \count($this->getData()); ?>
        <p>Sent <?= $count ?> message<?= $count === 1 ? '' : 's' ?>:</p>
        <?php
        foreach ($this->getData() as $index => $data) : ?>
            <h2>Message <?= $index + 1 ?></h2>
            <p><strong>Status:</strong> <?= $data['code'] ?> <?=
                $data['code'] === 250 ? 'OK' : ' Error' ?></p>
            <p><strong>From:</strong> <?= \htmlentities($data['from']) ?></p>
            <p>
                <strong>Recipients:</strong> <?= \htmlentities(\implode(', ', $data['recipients'])) ?>
            </p>
            <p><strong>Size:</strong> <?= Debugger::convertSize($data['length']) ?></p>
            <p>
                <strong>Time Sending:</strong> <?= \round($data['end'] - $data['start'], 6) ?> seconds
            </p>
            <h3>Headers</h3>
            <table>
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Value</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($data['headers'] as $name => $value): ?>
                    <tr>
                        <td><?= \htmlentities(Header::getName($name)) ?></td>
                        <td><?= \htmlentities($value) ?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
            <?php
            if (isset($data['html'])): ?>
                <h3>HTML Message</h3>
                <pre><code class="language-html"><?= \htmlentities($data['html']) ?></code></pre>
            <?php
            endif;
            if (isset($data['plain'])): ?>
                <h3>Plain Message</h3>
                <pre><code class="language-none"><?= \htmlentities($data['plain']) ?></code></pre>
            <?php
            endif;
            if ($data['attachments']): ?>
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
            <?php
            endif;
            if ($data['inlineAttachments']): ?>
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
            <?php
            endif;
        endforeach;
        return \ob_get_clean(); // @phpstan-ignore-line
    }

    protected function showHeader() : string
    {
        \ob_start();
        $configs = $this->mailer->getConfigs();
        ?>
        <p><strong>Host:</strong> <?= \htmlentities($configs['host']) ?></p>
        <p><strong>Port:</strong> <?= \htmlentities((string) $configs['port']) ?></p>
        <?php
        return \ob_get_clean(); // @phpstan-ignore-line
    }
}
