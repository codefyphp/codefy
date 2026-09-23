# Persistent queues

4.0 replaces implicit job-object JSON serialization with explicit payload serialization. `ShouldQueue` still describes executable jobs; persistent jobs additionally implement `SerializableJob`.

## Define and enqueue a job

```php
use Codefy\Framework\Queue\NodeQueue;
use Codefy\Framework\Queue\SerializableJob;
use Codefy\Framework\Queue\SimpleQueue;

final class RebuildSearchIndex extends SimpleQueue implements SerializableJob
{
    public function __construct(private int $documentId)
    {
        $this->name = 'search-index';
        $this->leaseTime = 120;
        $this->executions = 3; // Maximum delivery attempts.
        $this->schedule = '* * * * *';
    }

    public function toPayload(): array
    {
        return ['document_id' => $this->documentId];
    }

    public static function fromPayload(array $payload): static
    {
        if (!is_int($payload['document_id'] ?? null) || $payload['document_id'] <= 0) {
            throw new InvalidArgumentException('Invalid document ID.');
        }
        return new static($payload['document_id']);
    }

    public function handle(): bool
    {
        // Rebuild the index using current application services.
        // Make the operation safe to repeat if a worker dies after doing the work.
        return true;
    }
}

$queue = new NodeQueue(new RebuildSearchIndex(42));
$id = $queue->createItem();
$queue->dispatch(); // At most one due, claimable item per call.
```

Payloads must be JSON-compatible arrays. Validate their shape in `fromPayload()` and deliberately reconstruct constructor state; do not mass-assign arbitrary properties. Persist identifiers and data, not containers, closures, database connections, or live service objects. Include dynamic scheduling or retry settings in the payload if they must survive reconstruction; the example restores fixed settings in its constructor.

`JobSerializer` writes a versioned envelope with `version`, `class`, and `payload`. Decoding requires an explicit class allowlist and checks that the class implements `SerializableJob`. It does not use PHP `unserialize()` or instantiate arbitrary class names from storage. Programmatic dispatch permits the prototype job's class only; use one job class per queue name.

## Worker configuration and commands

Create or update application `config/queue.php`:

```php
return [
    'jobs' => [RebuildSearchIndex::class],
];
```

This repository's development `config` directory is ignored by Git; applications must supply this configuration themselves. Both console commands use `queue.jobs` to decide which classes may be reconstructed.

```sh
php codex queue:list
php codex queue:run
php codex queue:list --name=custom-node
php codex queue:run --name=custom-node
```

`--name` selects the node under `database_path()`, without `.json`; the default is `nodequeue`. Enqueue into the same node with `new NodeQueue($job, $absoluteNodePath)`. Workers honor that selected path. `queue:list` includes attempts and pending/leased/failed state. Invalid payloads, unregistered classes, and execution errors result in command failure. Commands process a snapshot, not an infinite daemon loop; schedule repeated invocations as needed.

`dispatch()` returns `false` for a job that is not due, a rejected `when()`/`skip()` filter, no claimable work, or a handler returning `false`. Successful handling returns `true`. Handler exceptions and PHP errors release the item and are rethrown to the caller. Storage errors propagate rather than being hidden as an empty queue. `queue:run` returns failure when a dispatched item does not complete.

## Storage and concurrency

NodeQueue uses `<node>.json` plus a stable `<node>.json.lock` file. Each operation acquires an exclusive advisory file lock around the complete read/modify/write sequence. Updates are written to a temporary file in the same directory and renamed over the JSON file. The lock is released in `finally`; corrupted JSON is rejected and not overwritten.

All producers and consumers must use this implementation and the same canonical node path. Do not edit queue JSON while workers are running. Do not delete lock files while any worker might hold them. Store files outside the web root in a directory writable only by the application account. Queue JSON is not encrypted and must not contain credentials or unnecessary sensitive information.

The backend targets local filesystems with working `flock()` and atomic same-directory rename. It does not promise cross-host locking on arbitrary network filesystems or crash durability after power loss. Use a broker/database implementation for distributed delivery requirements.

## Claims, retries, and failures

Each claim increments `executions`, sets `expire`, and creates a random `lease` ownership token. The configured positive job lease takes precedence over the fallback `claimItem($leaseTime)` argument. A live lease prevents another claim. An expired lease is reclaimable, including after worker termination.

Pass the complete returned item to `deleteItem()` or `releaseItem()`. Both check queue name, item ID, current lease token, and unexpired ownership. A stale worker cannot delete or release an item after another worker has claimed it. Successful handling removes the item; unsuccessful handling clears its lease for another attempt.

Maximum attempts are captured as `max_attempts` when enqueuing. Exhausted items are marked `failed` and retained, rather than silently deleted. `items()` returns the current queue's records, including failures, for inspection. `numberOfItems()` counts these records too. Inspect and correct failures, then explicitly enqueue replacement work; there is no automatic failed-job retry command in this release. `deleteQueue()` deliberately deletes all records for that queue name.

Garbage collection only resets expired leases belonging to the current queue and marks exhausted items. It never deletes old pending jobs merely because their creation time is older than a lease. Queued work is delivered with retry semantics, not exactly-once execution: use idempotent handlers, and set the lease longer than the expected runtime. There is no lease heartbeat or exponential retry delay in this backend.

## Migrating 3.x jobs

Stop old workers and preserve the original queue file for review. Old `object` values were JSON representations of public properties; decoding them produced arrays, and dispatch could delete them without running the job. They are not safely convertible into arbitrary PHP classes automatically.

For each known legacy queue, use trusted application logic to map its data into the corresponding new job constructor, validate the data, and enqueue into a fresh 4.0 node. Confirm counts and application state before retiring the backup. Do not provide a broad class loader or PHP object deserialization fallback for unknown legacy records.
