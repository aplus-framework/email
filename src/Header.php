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
 * Class Header.
 *
 * @package email
 */
class Header
{
    public const AUTO_SUBMITTED = 'Auto-Submitted';
    public const BCC = 'Bcc';
    public const CC = 'Cc';
    public const COMMENTS = 'Comments';
    public const CONTENT_TYPE = 'Content-Type';
    public const DATE = 'Date';
    public const DKIM_SIGNATURE = 'DKIM-Signature';
    public const FROM = 'From';
    public const IN_REPLY_TO = 'In-Reply-To';
    public const KEYWORDS = 'Keywords';
    public const LIST_UNSUBSCRIBE_POST = 'List-Unsubscribe-Post';
    public const MESSAGE_ID = 'Message-ID';
    public const MIME_VERSION = 'MIME-Version';
    public const MT_PRIORITY = 'MT-Priority';
    public const ORIGINAL_FROM = 'Original-From';
    public const ORIGINAL_RECIPIENT = 'Original-Recipient';
    public const ORIGINAL_SUBJECT = 'Original-Subject';
    public const PRIORITY = 'Priority';
    public const RECEIVED = 'Received';
    public const RECEIVED_SPF = 'Received-SPF';
    public const REFERENCES = 'References';
    public const REPLY_TO = 'Reply-To';
    public const RESENT_BCC = 'Resent-Bcc';
    public const RESENT_CC = 'Resent-Cc';
    public const RESENT_DATE = 'Resent-Date';
    public const RESENT_FROM = 'Resent-From';
    public const RESENT_MESSAGE_ID = 'Resent-Message-ID';
    public const RESENT_SENDER = 'Resent-Sender';
    public const RESENT_TO = 'Resent-To';
    public const RETURN_PATH = 'Return-Path';
    public const SENDER = 'Sender';
    public const SUBJECT = 'Subject';
    public const TO = 'To';
    public const X_PRIORITY = 'X-Priority';
    /**
     * @var array<string,string>
     */
    protected static array $headers = [
        'auto-submitted' => 'Auto-Submitted',
        'bcc' => 'Bcc',
        'cc' => 'Cc',
        'comments' => 'Comments',
        'content-type' => 'Content-Type',
        'date' => 'Date',
        'dkim-signature' => 'DKIM-Signature',
        'from' => 'From',
        'in-reply-to' => 'In-Reply-To',
        'keywords' => 'Keywords',
        'list-unsubscribe-post' => 'List-Unsubscribe-Post',
        'message-id' => 'Message-ID',
        'mime-version' => 'MIME-Version',
        'mt-priority' => 'MT-Priority',
        'original-from' => 'Original-From',
        'original-recipient' => 'Original-Recipient',
        'original-subject' => 'Original-Subject',
        'priority' => 'Priority',
        'received' => 'Received',
        'received-spf' => 'Received-SPF',
        'references' => 'References',
        'reply-to' => 'Reply-To',
        'resent-bcc' => 'Resent-Bcc',
        'resent-cc' => 'Resent-Cc',
        'resent-date' => 'Resent-Date',
        'resent-from' => 'Resent-From',
        'resent-message-id' => 'Resent-Message-ID',
        'resent-sender' => 'Resent-Sender',
        'resent-to' => 'Resent-To',
        'return-path' => 'Return-Path',
        'sender' => 'Sender',
        'subject' => 'Subject',
        'to' => 'To',
        'x-priority' => 'X-Priority',
    ];

    public static function getName(string $name) : string
    {
        return static::$headers[\strtolower($name)] ?? $name;
    }

    public static function setName(string $name) : void
    {
        static::$headers[\strtolower($name)] = $name;
    }
}
