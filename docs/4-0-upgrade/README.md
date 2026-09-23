# CodefyPHP 4.0 documentation

For the upcoming major release, start with the [4.0 upgrade guide](upgrade-4.0.md) and
[local documentation index](README.md). The [release review](release-review.md) records
security fixes, behavioral changes, verification, and deployment limitations.

This directory documents the behavior introduced during the 4.0 major-release review. PHP 8.4 remains the minimum supported version.

- [Upgrade from 3.x](upgrade-4.0.md): breaking changes and deployment order.
- [HTTP security](http-security.md): CORS, CSRF, authentication cookies, redirects, errors, and firewall inputs.
- [Rate limiting](rate-limiting.md): identities, windows, retry responses, and storage requirements.
- [Validation and pipelines](validation-and-pipelines.md): validated field selection, DTOs, transactions, and builders.
- [Persistent queues](queues.md): explicit job payloads, worker registration, leases, retries, and migration.
- [Scheduling](scheduler.md): execution results, cleanup, shell arguments, and locks.
- [Mail, logging, and SEO migration](dependency-upgrades.md): Symfony mail transports, Qubus Log 5, and SEO v3 APIs.
- [Request context and middleware configuration](request-context.md): lifecycle, Fiber isolation, and alias precedence.
- [Release review and verification](release-review.md): dependency fixes, additional changes, coverage, and limitations.
