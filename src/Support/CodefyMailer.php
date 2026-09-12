<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Codefy\Framework\Application;
use Qubus\Config\ConfigContainer;
use Qubus\Exception\Data\TypeException;
use Qubus\Mail\Mailer;
use Qubus\Mail\QubusMailer;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/** Immutable framework adapter for Qubus Mail's final implementation. */
final class CodefyMailer implements Mailer
{
    public const string VERSION = Application::APP_VERSION;

    private QubusMailer $mailer;

    public function __construct(ConfigContainer $config, ?MailerInterface $mailer = null)
    {
        $this->mailer = new QubusMailer(config: $config, mailer: $mailer);
    }

    public function withFrom(string $address, string $name = '', bool $auto = true): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withFrom($address, $name, $auto);

        return $new;
    }

    public function withSender(string $sender = ''): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withSender($sender);

        return $new;
    }

    public function withSubject(string $subject = ''): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withSubject($subject);

        return $new;
    }

    public function withPriority(?int $priority = null): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withPriority($priority);

        return $new;
    }

    public function withCharset(string $charset = 'UTF-8'): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withCharset($charset);

        return $new;
    }

    public function withCustomHeader(string $name, ?string $value = null): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withCustomHeader($name, $value);

        return $new;
    }

    public function withContentType(string $contentType = 'text/plain'): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withContentType($contentType);

        return $new;
    }

    public function withXMailer(?string $xmailer = ''): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withXMailer($xmailer);

        return $new;
    }

    public function withHtml(bool $isHtml = false): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withHtml($isHtml);

        return $new;
    }

    /** @param string|array<array-key, string> $address */
    public function withTo(string|array $address): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withTo($address);

        return $new;
    }

    /** @param string|array<array-key, string> $address */
    public function withCc(string|array $address): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withCc($address);

        return $new;
    }

    /** @param string|array<array-key, string> $address */
    public function withBcc(string|array $address): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withBcc($address);

        return $new;
    }

    /** @param string|array<array-key, string> $address */
    public function withReplyTo(string|array $address): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withReplyTo($address);

        return $new;
    }

    public function withAttachment(
        string $path,
        string $name = '',
        string $encode = 'base64',
        string $type = '',
        string $disposition = 'attachment'
    ): self {
        $new = clone $this;
        $new->mailer = $this->mailer->withAttachment($path, $name, $encode, $type, $disposition);

        return $new;
    }

    /**
     * @param string|array<array-key, mixed> $data
     * @param array<string, mixed> $options
     */
    public function withBody(string|array $data, array $options = []): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withBody($data, $options);

        return $new;
    }

    public function withAltBody(string $message = ''): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withAltBody($message);

        return $new;
    }

    public function withTransport(string $name = 'default'): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withTransport($name);

        return $new;
    }

    public function withDsn(#[\SensitiveParameter] string $dsn): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withDsn($dsn);

        return $new;
    }

    public function withSmtp(): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withSmtp();

        return $new;
    }

    public function withMail(): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withMail();

        return $new;
    }

    public function withSendmail(): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withSendmail();

        return $new;
    }

    public function withQmail(): self
    {
        $new = clone $this;
        $new->mailer = $this->mailer->withQmail();

        return $new;
    }

    public function getMessage(): Email
    {
        return $this->mailer->getMessage();
    }

    /**
     * @throws TypeException
     * @throws TransportExceptionInterface
     */
    public function send(): bool
    {
        return $this->mailer->send();
    }

    /**
     * @throws TypeException
     */
    public function save(): bool
    {
        return $this->mailer->save();
    }
}
