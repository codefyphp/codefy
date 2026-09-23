# 4.0 release review

## Review scope

This change set reviews first-party authentication and CSRF handling, CORS, throttling, validation and DTO boundaries, error responses, request state, persistent queues, scheduler execution, pipeline transactions, RBAC inheritance, key generation, and dependency/tooling configuration. It combines code inspection, regression tests, whole-source static analysis, syntax checks, and Composer's advisory audit. It is not a penetration-test certification or a claim that every possible vulnerability in the framework and its dependencies has been eliminated.

See the [upgrade guide](upgrade-4.0.md) for all intentional compatibility changes and the component documents for examples.

## Additional hardening and maintenance

- Password hashing now defaults to Argon2id with 19456 KiB memory, two iterations, and one thread when available. Bcrypt retains cost 12. This follows the [OWASP minimum Argon2id recommendation](https://cheatsheetseries.owasp.org/cheatsheets/Password_Storage_Cheat_Sheet.html#argon2id). Existing hashes remain verifiable; `needsRehash()` identifies hashes using old parameters. Existing algorithm/options hooks still permit application tuning. Password arguments on `hash()` and `verify()` are marked `SensitiveParameter` for stack traces.
- `generate:key:file` exclusively creates `.enc.key` under a restrictive umask, yielding mode `0600` on the tested POSIX filesystem. It refuses existing files and symlinks and checks writes. It does not rotate keys; preserve existing keys needed to decrypt environment files. `encrypt:env` received PHPDoc/line-length cleanup only; its expansion behavior is unchanged.
- RBAC role and permission insertion rejects self-cycles and indirect cycles with `LogicException`. Existing valid inheritance remains supported. Loading cyclic persisted data now fails instead of creating a recursively traversed graph.
- Source-level type annotations were corrected in Application configuration, the application helper, request context, firewall registries, and related classes. Redundant backing-property hooks were simplified in queue traits, base controllers, scheduler events, throttle conditions/intervals, and failure records while retaining their values and access restrictions. The required interface property hooks remain in `ShouldQueue` with a narrow coding-tool compatibility exclusion.
- Local storage's missing configuration fallback is now passed to the typed configuration accessor itself. An invalid/incomplete disk configuration still fails; this is not an automatic creation of a usable disk.
- `phpcs.xml` now scans all of `src`, including queues, DTOs, and security, and no longer names a missing migration directory. `composer codestan` now supplies the `src` target. PHPUnit/Pest session files use the operating system's temporary directory, allowing tests to run without writing to a system PHP session directory. Transaction tests use an isolated SQLite connection.

## Verification

The original test suite passed 227 tests with 823 assertions.

Final verification for this change set:

| Check                                         | Result                                                      |
|-----------------------------------------------|-------------------------------------------------------------|
| PHP 8.4 tests                                 | 300 passed, 992 assertions                                  |
| PHP 8.5 tests                                 | 300 passed, 992 assertions                                  |
| Source coding standards                       | 261 files checked; no errors or warnings                    |
| PHPStan, level 6                              | No errors                                                   |
| PHP 8.4 syntax                                | 321 source/test files; no syntax errors                     |
| Composer manifest                             | Valid                                                       |
| Composer advisory response                    | No known vulnerability advisories; three abandoned packages |
| Diff whitespace and local documentation links | Passed                                                      |

 Added regressions cover malformed and expired cookies, CORS allowlists/preflights, header-based throttle bypass attempts, all rate windows, validated field selection, empty selections and request clones, FormDataRequest recursion, error redirection, production information exposure, request/Fiber cleanup, transactions, aliases, job payload restoration, retry counts, stale leases, corrupted queue storage, multiple processes claiming one job, scheduler lock cleanup/exit codes, script quoting, RBAC cycles, password parameters, and exclusive key creation.

Commands used for the release review:

```sh
php84 vendor/bin/pest --compact
php vendor/bin/pest --compact
php84 vendor/bin/phpcs --standard=phpcs.xml --parallel=1 --no-cache
php84 vendor/bin/phpstan analyse -l 6 src --no-progress --debug --memory-limit=512M
composer validate --no-check-publish
composer audit --locked --no-interaction
```

Here `php84` is PHP 8.4 and `php` is the workspace's PHP 8.5 binary. PHPStan's debug mode avoids its local TCP worker server, which the execution sandbox blocks. The larger memory limit avoids the PHP 8.4 CLI's 128 MiB default.

## Deployment limits

- The PSR-6 rate limiter and CacheLocker are not atomic distributed primitives. FileLocker and NodeQueue locking are intended for a shared local filesystem, not arbitrary network storage. See the component documentation.
- Authentication-cookie expiry limits replay duration but does not implement immediate server-side logout revocation. Revoke application tokens/sessions when that behavior is required.
- Pattern-based firewall detection still depends on parsed request bodies and configured sources. Enforce body-size/depth limits before expensive parsing and detection, and retain application-specific security controls.
- Native Fiber request isolation covers RequestContext only. Full concurrent Application/Swoole lifecycle isolation was not established, and no live Swoole, SMTP, Windows, or distributed-cache integration was exercised.
- The queue changes deliberately require a data migration and coordinated worker restart. Keep the original files until the application confirms migrated work.

## Follow-up: dependency integration verification

After upgrading to Qubus Mail 6.0.0, Error 3.1.0 / Log 5.0.0, and SEO 3.0.6, the mailer inheritance fatal error and additional untested mail/SEO integration failures were repaired. The [dependency migration guide](dependency-upgrades.md) documents configuration changes, compatible factory names, breaking SEO API changes, and examples.

The full suite now passes on PHP 8.4 and PHP 8.5 with **316 tests and 1,073 assertions**. The 16 additional cases cover mail composition and delivery failures, scheduler notification content, file/email error logging, and SEO v3 output and indexing clients. Existing security regressions remain in the passing suite. Tests do not send live mail or submit URLs to search engines.

Manifest validation and platform checks pass, and the refresh reports no known security advisories.

PHPStan level 6 and repository-wide coding-standard checks also pass after the integration changes.
