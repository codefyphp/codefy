<?php

declare(strict_types=1);

use Codefy\Framework\Factory\FileLoggerFactory;
use Codefy\Framework\Factory\FileLoggerSmtpFactory;
use Codefy\Framework\Factory\MailerFactory;
use Codefy\Framework\Factory\PHPMailerSmtpFactory;
use Codefy\Framework\Scheduler\Traits\MailerAware;
use Codefy\Framework\Support\CodefyMailer;
use Qubus\Error\Handlers\Psr3ErrorHandler;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

use function Codefy\Framework\Helpers\app;
use function Codefy\Framework\Helpers\mail;

beforeEach(function () {
    $this->app = app();
    $this->originalMailer = $this->app->make('mailer');
    $this->originalMailConfig = $this->app->configContainer->getConfigKey('mailer');
    $this->originalFilesystem = $this->app->configContainer->getConfigKey('filesystem');
    $this->directory = sys_get_temp_dir() . '/codefy-mail-' . bin2hex(random_bytes(8));
    mkdir($this->directory, 0700);
    $this->originalEnv = $_ENV;
    $_ENV['MAILER_FROM_EMAIL'] = 'sender@example.test';
    $_ENV['MAILER_FROM_NAME'] = 'Test Sender';
    $_ENV['LOGGER_FROM_EMAIL'] = 'logger@example.test';
    $_ENV['LOGGER_TO_EMAIL'] = 'operator@example.test';
    $_ENV['LOGGER_EMAIL_SUBJECT'] = 'null';
    $this->app->configContainer->setConfigKey('mailer', [
        'dsn' => 'null://null', 'debug' => false,
        'transports' => ['smtp' => 'null://null'],
        'emlfile' => $this->directory . '/message.eml',
    ]);
    $filesystem = $this->originalFilesystem;
    $filesystem['disks']['logs']['root'] = $this->directory;
    $this->app->configContainer->setConfigKey('filesystem', $filesystem);
    $this->transport = new class implements MailerInterface {
        public array $messages = [];
        public bool $fail = false;

        public function send(RawMessage $message, ?Envelope $envelope = null): void
        {
            if ($this->fail) {
                throw new TransportException('Test transport unavailable');
            }
            $this->messages[] = $message;
        }
    };
    $this->mailer = new CodefyMailer($this->app->configContainer, $this->transport);
    $this->app->share($this->mailer);
});

afterEach(function () {
    $this->app->share($this->originalMailer);
    foreach (['mailer' => $this->originalMailConfig, 'filesystem' => $this->originalFilesystem] as $key => $value) {
        $this->app->configContainer->removeConfigKey($key);
        $this->app->configContainer->setConfigKey($key, $value);
    }
    $_ENV = $this->originalEnv;
    foreach (glob($this->directory . '/*') as $file) {
        unlink($file);
    }
    rmdir($this->directory);
});

it('preserves immutable mail composition and returns defensive message copies', function () {
    $first = $this->mailer->withFrom('sender@example.test')->withTo(['one@example.test' => 'One'])
        ->withCc('copy@example.test')->withBcc('hidden@example.test')->withReplyTo('reply@example.test')
        ->withSubject('First')->withCharset('UTF-8')->withHtml(true)->withBody('<p>Hello</p>')
        ->withAltBody('Hello')->withPriority(1)->withCustomHeader('X-Test', 'value')->withXMailer('Codefy test');
    $second = $first->withTo('two@example.test')->withSubject('Second');
    $copy = $first->getMessage();
    $copy->subject('Mutated')->to('changed@example.test');
    expect($first->send())->toBeTrue()->and($second->send())->toBeTrue();
    [$one, $two] = $this->transport->messages;
    expect($one->getSubject())->toBe('First')->and($one->getTo()[0]->getAddress())->toBe('one@example.test')
        ->and($two->getTo()[0]->getAddress())->toBe('two@example.test')
        ->and($one->getHtmlBody())->toBe('<p>Hello</p>')->and($one->getTextBody())->toBe('Hello')
        ->and($one->getCc()[0]->getAddress())->toBe('copy@example.test')
        ->and($one->getBcc()[0]->getAddress())->toBe('hidden@example.test')
        ->and($one->getReplyTo()[0]->getAddress())->toBe('reply@example.test')
        ->and($one->getHeaders()->get('X-Test')->getBodyAsString())->toBe('value')
        ->and($this->mailer->getMessage()->getTo())->toBe([]);
});

it('supports default DSNs named transports and the legacy factory name', function () {
    foreach ([MailerFactory::create(), PHPMailerSmtpFactory::create()] as $mailer) {
        expect($mailer)->toBeInstanceOf(CodefyMailer::class);
        foreach ([$mailer, $mailer->withSmtp(), $mailer->withTransport('smtp'), $mailer->withDsn('null://null')] as $message) {
            expect($message->withFrom('sender@example.test')->withTo('recipient@example.test')
                ->withBody('No network transport')->send())->toBeTrue();
        }
    }
});

it('saves debug messages and attachments without sending them', function () {
    $attachment = $this->directory . '/attachment.txt';
    file_put_contents($attachment, 'attachment contents');
    $this->app->configContainer->setConfigKey('mailer', ['debug' => true]);
    $message = $this->mailer->withFrom('sender@example.test')->withSender('bounce@example.test')
        ->withTo('recipient@example.test')->withContentType('text/plain')->withBody('Saved body')
        ->withAttachment($attachment, 'report.txt');
    expect($message->send())->toBeTrue()->and($this->transport->messages)->toBe([]);
    $eml = file_get_contents($this->directory . '/message.eml');
    expect($eml)->toContain('Saved body')->toContain('report.txt')->toContain('bounce@example.test');
    expect($message->save())->toBeTrue();
});

it('uses the injected mailer in the mail helper without leaking recipients between calls', function () {
    expect(mail('one@example.test', 'First', '<p>Hello</p>', ['cc' => 'copy@example.test']))->toBeTrue();
    expect(mail('two@example.test', 'Second', 'Plain text'))->toBeTrue();
    [$first, $second] = $this->transport->messages;
    expect($first->getHtmlBody())->toBe('<p>Hello</p>')
        ->and($second->getHtmlBody())->toBeNull()->and($second->getCc())->toBe([])
        ->and($second->getTo()[0]->getAddress())->toBe('two@example.test')
        ->and($first->getFrom()[0]->getAddress())->toBe('sender@example.test');
});

it('returns false and writes a file log for Symfony transport failures', function () {
    $this->transport->fail = true;
    expect(mail('recipient@example.test', 'Failure', 'Body'))->toBeFalse();
    $files = glob($this->directory . '/*.log');
    expect($files)->toHaveCount(1);
    expect(file_get_contents($files[0]))->toContain('Test transport unavailable');
});

it('sends scheduler notifications through immutable fluent messages', function () {
    $task = new class {
        use MailerAware;
        public array $options = [
            'recipients' => 'one@example.test, two@example.test',
            'smtpSender' => 'scheduler@example.test',
        ];
    };
    expect($task->sendEmail(new RuntimeException('Task failed')))->toBeTrue();
    $message = $this->transport->messages[0];
    expect($message->getTo())->toHaveCount(2)->and($message->getTextBody())->toBe('Task failed')
        ->and($message->getHtmlBody())->toBeNull()->and($message->getSubject())->toContain('A Task Needs Attention!');
    $task->options['recipients'] = null;
    expect($task->sendEmail(new RuntimeException('Skipped')))->toBeFalse()
        ->and($this->transport->messages)->toHaveCount(1);
});

it('propagates scheduler transport failures to task error handling', function () {
    $this->transport->fail = true;
    $task = new class {
        use MailerAware;
        public array $options = ['recipients' => 'one@example.test', 'smtpSender' => 'scheduler@example.test'];
    };
    expect(fn () => $task->sendEmail(new RuntimeException('Task failed')))->toThrow(TransportException::class);
});

it('logs Qubus errors to files and Symfony backed email with the new log package', function () {
    $this->app->configContainer->setConfigKey('mailer', ['debug' => true]);
    $logger = FileLoggerSmtpFactory::getLogger();
    new Psr3ErrorHandler($logger)->handle(new RuntimeException('Integration error'));
    $files = glob($this->directory . '/*.log');
    expect($files)->toHaveCount(1)->and(file_get_contents($files[0]))->toContain('Integration error');
    $eml = file_get_contents($this->directory . '/message.eml');
    expect($eml)->toContain('Integration error')->toContain('Subject: Log notification')
        ->toContain('operator@example.test')->toContain('text/plain');
});

it('builds a file only logger without mail transport configuration', function () {
    $_ENV['LOGGER_FROM_EMAIL'] = 'null';
    $_ENV['LOGGER_TO_EMAIL'] = 'null';
    $this->app->configContainer->removeConfigKey('mailer');
    $this->app->configContainer->setConfigKey('mailer', []);
    FileLoggerSmtpFactory::getLogger()->error('File only');
    FileLoggerFactory::getLogger()->error('Second record');
    expect(file_get_contents(glob($this->directory . '/*.log')[0]))
        ->toContain('File only')->toContain('Second record');
});
