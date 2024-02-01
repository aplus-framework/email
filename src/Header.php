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
    public const string AUTO_SUBMITTED = 'Auto-Submitted';
    public const string BCC = 'Bcc';
    public const string CC = 'Cc';
    public const string COMMENTS = 'Comments';
    public const string CONTENT_TYPE = 'Content-Type';
    public const string DATE = 'Date';
    public const string DKIM_SIGNATURE = 'DKIM-Signature';
    public const string FROM = 'From';
    public const string IN_REPLY_TO = 'In-Reply-To';
    public const string KEYWORDS = 'Keywords';
    public const string LIST_UNSUBSCRIBE_POST = 'List-Unsubscribe-Post';
    public const string MESSAGE_ID = 'Message-ID';
    public const string MIME_VERSION = 'MIME-Version';
    public const string MT_PRIORITY = 'MT-Priority';
    public const string ORIGINAL_FROM = 'Original-From';
    public const string ORIGINAL_RECIPIENT = 'Original-Recipient';
    public const string ORIGINAL_SUBJECT = 'Original-Subject';
    public const string PRIORITY = 'Priority';
    public const string RECEIVED = 'Received';
    public const string RECEIVED_SPF = 'Received-SPF';
    public const string REFERENCES = 'References';
    public const string REPLY_TO = 'Reply-To';
    public const string RESENT_BCC = 'Resent-Bcc';
    public const string RESENT_CC = 'Resent-Cc';
    public const string RESENT_DATE = 'Resent-Date';
    public const string RESENT_FROM = 'Resent-From';
    public const string RESENT_MESSAGE_ID = 'Resent-Message-ID';
    public const string RESENT_SENDER = 'Resent-Sender';
    public const string RESENT_TO = 'Resent-To';
    public const string RETURN_PATH = 'Return-Path';
    public const string SENDER = 'Sender';
    public const string SUBJECT = 'Subject';
    public const string TO = 'To';
    public const string X_PRIORITY = 'X-Priority';
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
