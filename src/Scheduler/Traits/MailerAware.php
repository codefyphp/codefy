<?php

declare(strict_types=1);

namespace Codefy\Framework\Scheduler\Traits;

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use function Codefy\Framework\Helpers\app;
use function array_map;
use function explode;
use function php_uname;
use function Qubus\Support\Helpers\is_null__;
use function sprintf;

trait MailerAware
{
    /**
     * Send email on Exception.
     * @throws TransportExceptionInterface
     */
    public function sendEmail(\Throwable $ex): bool
    {
        if (is_null__($this->options['recipients'] ?? null)) {
            return false;
        }

        if (is_null__($this->options['smtpSender'] ?? null)) {
            return false;
        }

        /** @var \Qubus\Mail\Mailer $mailer */
        $mailer = app(name: 'mailer');

        return $mailer
            ->withTo(array_map('trim', explode(',', $this->options['recipients'])))
            ->withFrom($this->options['smtpSender'], $this->options['smtpSenderName'] ?? '')
            ->withSubject(sprintf('[%s] A Task Needs Attention!', php_uname('n')))
            ->withCharset('utf-8')
            ->withContentType('text/plain')
            ->withHtml(false)
            ->withBody($ex->getMessage())
            ->send();
    }
}
