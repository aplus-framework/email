<?php
/*
 * This file is part of Aplus Framework Email Library.
 *
 * (c) Natan Felles <natanfelles@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace PHPSTORM_META;

registerArgumentsSet(
    'headers',
    \Framework\Email\Header::AUTO_SUBMITTED,
    \Framework\Email\Header::BCC,
    \Framework\Email\Header::CC,
    \Framework\Email\Header::COMMENTS,
    \Framework\Email\Header::CONTENT_TYPE,
    \Framework\Email\Header::DATE,
    \Framework\Email\Header::DKIM_SIGNATURE,
    \Framework\Email\Header::FROM,
    \Framework\Email\Header::IN_REPLY_TO,
    \Framework\Email\Header::KEYWORDS,
    \Framework\Email\Header::LIST_UNSUBSCRIBE_POST,
    \Framework\Email\Header::MESSAGE_ID,
    \Framework\Email\Header::MIME_VERSION,
    \Framework\Email\Header::MT_PRIORITY,
    \Framework\Email\Header::ORIGINAL_FROM,
    \Framework\Email\Header::ORIGINAL_RECIPIENT,
    \Framework\Email\Header::ORIGINAL_SUBJECT,
    \Framework\Email\Header::PRIORITY,
    \Framework\Email\Header::RECEIVED,
    \Framework\Email\Header::RECEIVED_SPF,
    \Framework\Email\Header::REFERENCES,
    \Framework\Email\Header::REPLY_TO,
    \Framework\Email\Header::RESENT_BCC,
    \Framework\Email\Header::RESENT_CC,
    \Framework\Email\Header::RESENT_DATE,
    \Framework\Email\Header::RESENT_FROM,
    \Framework\Email\Header::RESENT_MESSAGE_ID,
    \Framework\Email\Header::RESENT_SENDER,
    \Framework\Email\Header::RESENT_TO,
    \Framework\Email\Header::RETURN_PATH,
    \Framework\Email\Header::SENDER,
    \Framework\Email\Header::SUBJECT,
    \Framework\Email\Header::TO,
    \Framework\Email\Header::X_PRIORITY,
);
expectedArguments(
    \Framework\Email\Message::getHeader(),
    0,
    argumentsSet('headers')
);
expectedArguments(
    \Framework\Email\Message::setHeader(),
    0,
    argumentsSet('headers')
);
