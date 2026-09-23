# Scheduler changes

## Execution and failures

Callbacks and dispatcher processors release their overlap locks in `finally`, including when a before hook, execution, or after hook throws. Callback output buffers are also cleaned up after a PHP error. Callback execution returns captured output when present, otherwise a string return value or an empty string.

Foreground shell processors compile configured arguments and inspect the process exit code. A nonzero exit code returns `false`. Background execution reports process launch, not eventual job completion. `onlyOneInstance()` forces foreground execution so the lock remains held while the job runs.

`Schedule::run()` records false results and catches all `Throwable` failures, then continues with later due processors. Inspect `executedProcessors` and `failedProcessors`; each failed record exposes read-only `processor` and `exception` properties. A `false` result is recorded as a generic execution failure. Call `resetRun()` before collecting results for another run on the same schedule instance. `schedule:run` exits nonzero when failures were recorded.

For `BaseTask`, teardown runs after execution even when execution throws. `TaskCompleted` is emitted only after successful execution and teardown. A failure emits `TaskFailed`, optionally invokes the configured failure-mail behavior, and returns `false`. The overlap lock is always released. Setup failure does not run teardown; keep setup's partial-allocation cleanup inside setup itself.

## Script paths and arguments

`Schedule::php()` checks that the script is a file before registering it. Interpreter and script paths are shell-quoted, and arguments are compiled once. Shell processors support positional arguments with integer keys and options with string keys:

```php
$schedule->php('/srv/app/jobs/rebuild index.php', args: [
    '--tenant' => 'example',
    '--verbose' => null,
    'one positional argument',
])->runInForeground();
```

The `null` option value represents a flag without a value. Foreground and background paths use the same argument compilation. `Schedule::command()` remains a trusted console-command expression; never pass raw HTTP input as its command string. Windows background construction no longer executes a process while merely building the command, though Windows process behavior was not exercised during this review.

## Locks

`CacheLocker` remains available for compatibility, but PSR-6 does not supply an atomic lock primitive. Its check-then-save behavior is best effort and should not enforce critical mutual exclusion across processes.

Use `FileLocker` for local workers:

```php
use Codefy\Framework\Scheduler\Mutex\FileLocker;
use Codefy\Framework\Scheduler\Mutex\Locker;

$app->share(new FileLocker('/srv/app/storage/scheduler-locks'));
$app->alias(Locker::class, FileLocker::class);
```

The locker creates a private directory if needed and uses exclusive nonblocking `flock()`. Only the locker instance owning a handle can release it. A process exit also closes its handles. Lock files remain on disk and must not be deleted while workers run. Different workers must use the same directory on a filesystem supporting file locks.

File locks are held until explicit unlock or handle/process closure; the processor's cache TTL is not a forced runtime timeout for `FileLocker`. A hung process needs operational supervision. For multiple hosts, supply a `Locker` backed by a distributed atomic lock with ownership-safe release.

Mutex names now hash the expression, command identity, arguments, and optional description. Closure identity uses source file and line positions rather than attempting to stringify a closure. Assign distinct descriptions when the same closure source is scheduled with distinct captured state. Changing source, arguments, or description changes the mutex key; deploy scheduler changes without overlapping old workers.

## Email notifications

Email notifications now compose immutable Qubus Mail 6 messages using the application's registered mailer and default DSN. Comma-separated recipients are trimmed, the sender display name is optional, and notification bodies use plain text. Transport exceptions propagate to task error handling. See [mail configuration and migration](dependency-upgrades.md#framework-mail-api).

## Time calculations

Processors can use their default null timezone without a constructor type error. String cron expressions are normalized before evaluation. `between()` and `unlessBetween()` calculate the current interval when the filter runs, so long-lived schedules do not reuse the time captured during construction. Intervals crossing midnight remain supported. Queue due checks also accept string or DateTimeZone timezone configuration and compare explicit date schedules in that zone.
