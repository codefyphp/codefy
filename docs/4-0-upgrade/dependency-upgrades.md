# Mail, logging, and SEO dependency migration

The 4.0 integrations target `qubus/mail ^6`, `qubus/log ^5`, `qubus/error ^3`, and `melbahja/seo ^3`. The compatibility review used Mail 6.0.0, Log 5.0.0, Error 3.1.0, SEO 3.0.6, and Symfony Mailer/Mime 8.1.5. The Composer constraints also permit Symfony Mailer/Mime 7.3 and later 7.x versions; that alternative dependency resolution was not tested in this review.

## What Upgrade Could Break

- Qubus Mail's implementation became `final`. Extending it caused a fatal error while resolving the application's mailer, stopping the test suite before it could report ordinary failures.
- Error 3.1 uses Qubus Log 5. Mail logging now uses `MailerLogger`, backed by Qubus/Symfony Mailer. The framework now uses that class directly and supplies a default subject when `LOGGER_EMAIL_SUBJECT` is unset.
- SEO v3 changed `Thing` constructor argument order and replaced the sitemap options array with typed constructor parameters. It removed the old `Ping` and `Indexing` classes.

## Mail configuration

Replace PHPMailer-era transport settings with Symfony DSNs in the application's `config/mailer.php`:

```php
use function Qubus\Config\Helpers\env;

return [
    'dsn' => env('MAILER_DSN'),
    'transports' => [
        'smtp' => env('MAILER_SMTP_DSN'),
        'sendmail' => 'sendmail://default',
    ],
    'debug' => false,
    'emlfile' => '/srv/app/storage/mail/debug.eml',
];
```

Set `MAILER_DSN` to the production transport, for example `smtp://user:password@smtp.example.com:587`. Percent-encode reserved characters in credentials. `MAILER_SMTP_DSN` is needed when explicitly selecting `withSmtp()`. Provider-specific DSNs require the corresponding Symfony transport bridge. Consult the [Symfony Mailer transport documentation](https://symfony.com/doc/current/mailer.html#transport-setup) for transport and TLS options.

`MAILER_FROM_EMAIL` and optional `MAILER_FROM_NAME` still supply the framework `mail()` helper's sender. Its existing sender, charset, and X-Mailer filters remain available. `mailer.mail_transport` and the old individual PHPMailer SMTP options no longer select delivery. Configure `mailer.dsn` even if the old configuration still contains those keys.

- `withDsn($dsn)` selects a DSN on that message.
- `withTransport('name')` selects `mailer.transports.name`; `withTransport()` uses `mailer.dsn`.
- `withSmtp()`, `withSendmail()`, and `withQmail()` select their respective named transports. Configure each name before using it. `withMail()` selects Symfony's native transport.
- `debug: true` saves an EML file instead of sending. Protect its directory because messages can contain private data. The configured file is overwritten on each save.
- For tests, `null://null` discards messages locally. An injected Symfony `MailerInterface` can capture messages and simulate failures without delivery.

## Framework mail API

`CodefyMailer` implements `Qubus\Mail\Mailer` through composition. It retains `VERSION`, its configuration constructor argument, and the fluent methods. Each `with*()` returns a new `CodefyMailer`; retain the returned value or chain calls. It is no longer a subclass of `QubusMailer` or PHPMailer. Use the `Mailer` interface for application type declarations.

```php
use Codefy\Framework\Factory\MailerFactory;

$mail = MailerFactory::create()
    ->withFrom('sender@example.com', 'Example')
    ->withTo('recipient@example.com')
    ->withCharset('UTF-8')
    ->withHtml(true)
    ->withSubject('Welcome')
    ->withBody('<p>Welcome!</p>')
    ->withAltBody('Welcome!');

$mail->send();
```

`MailerFactory::create()` returns a fresh adapter using the application configuration and its default DSN. `PHPMailerSmtpFactory` remains as a deprecated compatibility name with the same behavior; its name does not force the SMTP transport. Select `withSmtp()` explicitly when needed.

`getMessage()` returns a copy of the Symfony `Email`. Mutating that copy does not change the adapter. The optional second constructor argument is a Symfony `MailerInterface`:

```php
$mailer = new \Codefy\Framework\Support\CodefyMailer($config, $testMailer);
$app->share($mailer);
```

The `mail()` helper now resolves the application's registered `mailer`, so injected transports work for helper calls and scheduler notifications. Charset is selected before the body is created. Symfony transport failures are logged through the file logger and return `false`; configuration errors and invalid message data still throw. Direct `send()` calls propagate transport exceptions. Catch `Symfony\Component\Mailer\Exception\TransportExceptionInterface` instead of PHPMailer exceptions.

Scheduler notifications use the same configured transport, trim comma-separated recipients, tolerate a missing sender display name, and compose plain-text messages. They return the mailer's result and propagate transport exceptions to task error handling. Missing recipients or sender still skip notification.

## Error and mail logging

`FileLoggerFactory` continues to write through the configured `filesystem.disks.logs` disk. `FileLoggerSmtpFactory` adds `Qubus\Log\Loggers\MailerLogger` only when both `LOGGER_FROM_EMAIL` and `LOGGER_TO_EMAIL` are configured. Its subject defaults to `Log notification`. It uses the default `mailer.dsn`, despite the factory's historical SMTP name; no mailer is constructed when email logging is disabled.

The Qubus error-handler integration remains compatible. Regression coverage exercises `Psr3ErrorHandler` through both file and email logging. File logging happens before email logging; a delivery exception propagates after the file record is written. The earlier production-message suppression and local-only error redirects remain covered by the full test suite.

## SEO v3

The [upstream SEO documentation](https://github.com/melbahja/seo) describes its new builders and indexing clients. Codefy's factory now constructs those v3 classes.

`thing(string $type, array $data = [])` keeps its existing public arguments and maps them to v3's `props` and `type` named arguments. Data can include nested schema objects, numeric values, and arrays. `schema()`, `metaTags()`, and `robots()` remain available. One schema item serializes directly; multiple items produce an `@graph`.

### Sitemaps

Replace `sitemap($domain, $options)` with typed parameters. The first parameter is now named `baseUrl`, so callers using `domain:` must update it. Available parameters are `baseUrl`, `saveDir`, `indexName`, `sitemapBaseUrl`, `mode`, `indent`, `hideGenerator`, and `dateFormat`. Defaults follow v3: index `sitemap.xml`, `OutputMode::TEMP`, one-space indentation, generator comment enabled, and ISO 8601 dates.

```php
use Codefy\Framework\Support\SeoFactory;
use Melbahja\Seo\Sitemap\OutputMode;

$sitemap = SeoFactory::sitemap(
    baseUrl: 'https://example.com',
    saveDir: '/srv/app/public',
    indexName: 'sitemap.xml',
    mode: OutputMode::TEMP,
    hideGenerator: true,
);
$sitemap->links('pages.xml', ['/about', '/contact']);
$sitemap->render();
```

Supply a writable `saveDir` for file output. `TEMP` writes temporary files before replacing output; `FILE` writes directly. For response generation choose `MEMORY` explicitly:

```php
$sitemap = SeoFactory::sitemap('https://example.com', mode: OutputMode::MEMORY);
$sitemap->links('pages.xml', ['/about', '/contact']);
$indexXml = $sitemap->render();
$pagesXml = $sitemap->generate('pages.xml')->render();
```

The index and each registered sitemap are separate documents. Serve both at their corresponding URLs. `sitemapBaseUrl` changes the location of generated sitemap files in the index; page URLs still use `baseUrl`. `STREAM` is also an upstream output mode. Register custom builders on the returned object with `register()`.

### Indexing

`ping()` and `indexing($host, $keys)` have been removed because their upstream classes no longer exist. Use the explicit clients:

```php
use Melbahja\Seo\Indexing\IndexNowEngine;
use Melbahja\Seo\Indexing\URLIndexingType;

$indexNow = SeoFactory::indexNow($indexNowApiKey);
$indexNow->submitUrl('https://example.com/page', IndexNowEngine::INDEXNOW);

$google = SeoFactory::googleIndexer($googleOAuthAccessToken);
$google->submitUrl('https://example.com/page', URLIndexingType::UPDATE);
```

Google requires an OAuth access token; an IndexNow verification key is not interchangeable. Submit page URLs, not sitemap URLs. Clients also expose `submitUrls()`. Creation makes no requests; submission does. Both factories accept an optional `Melbahja\Seo\Utils\HttpClient` for testing or custom HTTP behavior. A custom Google client must supply its own authorization header. Credentials are marked sensitive on the factory entry points.

IndexNow verification must be served separately. Its upstream `serveKeyFile()` sends headers and terminates PHP execution; in a framework route, prefer an ordinary text response for the exact verification path. Google does not use this key-file verification method.

## Verification scope

The complete suite passes on PHP 8.4 and 8.5: **316 tests, 1,073 assertions**, including 16 new dependency integration cases. Composer reported no known security advisories.

Regression tests cover application mailer resolution, immutable message composition, recipient isolation, attachments and EML saving, default/named DSNs, the legacy factory, helper transport failures, scheduler notifications, and file/email error logging. SEO tests cover nested schema values, meta tags, robots rules, XML validity, memory/file/temporary sitemap output, indexing requests through a fake HTTP client, and empty credentials.

Tests use injected mailers, a null transport, or debug EML files and make no live SMTP or indexing requests. Production credentials, provider bridges, and remote delivery need application-level verification.

PHPStan level 6 and the repository-wide PHP_CodeSniffer checks pass.
