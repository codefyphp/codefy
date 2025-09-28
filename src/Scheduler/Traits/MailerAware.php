<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Traits;

use Qubus\Exception\Exception;

use function Codefy\Framework\Helpers\app;
use function explode;
use function php_uname;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

trait MailerAware
{
    /**
     * Send email on Exception.
     */
    public function sendEmail(Exception $ex): bool
    {
        $mailer = app(name: 'mailer');

        if (is_null__($this->options['recipients'])) {
            return false;
        }

        if (is_null__($this->options['smtpSender'])) {
            return false;
        }

        $mailer->send(function ($message) use ($ex) {
            $message->to(explode(',', $this->options['recipients']));
            $message->from($this->options['smtpSender'], $this->options['smtpSenderName']);
            $message->subject(sprintf('[%s] A Task Needs Attention!', php_uname('n')));
            $message->body($ex->getMessage());
            $message->charset('utf-8');
            $message->html(false);
        });

        return true;
    }
}
