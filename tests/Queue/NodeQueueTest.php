<?php

declare(strict_types=1);

use Codefy\Framework\Queue\JobSerializer;
use Codefy\Framework\Queue\NodeQueue;
use Codefy\Framework\Queue\SerializableJob;
use Codefy\Framework\Queue\SimpleQueue;

use Codefy\Framework\Tests\Queue\Fixtures\ReleaseTestJob;

beforeEach(function () {
    $this->directory = sys_get_temp_dir() . '/codefy-queue-' . bin2hex(random_bytes(8));
    mkdir($this->directory, 0700);
    $this->node = $this->directory . '/jobs';
    ReleaseTestJob::$handled = [];
});
afterEach(function () {
    foreach (glob($this->directory . '/*') as $file) { unlink($file); }
    rmdir($this->directory);
});

it('restores stored payload and executes it', function () {
    $job = new ReleaseTestJob('stored');
    $queue = new NodeQueue($job, $this->node);
    $id = $queue->createItem();
    $job->value = 'changed';
    expect($id)->not->toBeEmpty()->and($queue->dispatch())->toBeTrue()
        ->and(ReleaseTestJob::$handled)->toBe(['stored'])->and($queue->numberOfItems())->toBe(0);
});

it('retains exhausted failures and counts attempts cumulatively', function () {
    $queue = new NodeQueue(new ReleaseTestJob(succeeds: false), $this->node);
    $queue->createItem();
    expect($queue->dispatch())->toBeFalse()->and($queue->dispatch())->toBeFalse()
        ->and($queue->claimItem())->toBeFalse();
    expect($queue->items()[0]['executions'])->toBe(2)->and($queue->items()[0]['failed'])->toBeTrue();
});

it('releases failed jobs on PHP errors', function () {
    $queue = new NodeQueue(new ReleaseTestJob('throw'), $this->node);
    $queue->createItem();
    expect(fn () => $queue->dispatch())->toThrow(TypeError::class);
    expect($queue->items()[0]['expire'])->toBe(0);
});

it('prevents duplicate claims and stale acknowledgements', function () {
    $queue = new NodeQueue(new ReleaseTestJob(), $this->node);
    $queue->createItem();
    $claim = $queue->claimItem();
    $other = new NodeQueue(new ReleaseTestJob(), $this->node);
    expect($other->claimItem())->toBeFalse();
    expect($queue->releaseItem($claim))->toBeTrue();
    $replacement = $other->claimItem();
    $queue->deleteItem($claim);
    expect($queue->numberOfItems())->toBe(1)->and($queue->releaseItem($claim))->toBeFalse();
    $other->deleteItem($replacement);
    expect($queue->numberOfItems())->toBe(0);
});

it('preserves old pending jobs during garbage collection', function () {
    $queue = new NodeQueue(new ReleaseTestJob(), $this->node);
    $queue->createItem();
    $items = json_decode(file_get_contents($this->node . '.json'), true);
    $items[0]['created'] = 1;
    file_put_contents($this->node . '.json', json_encode($items));
    $queue->garbageCollection();
    expect($queue->numberOfItems())->toBe(1)->and($queue->dispatch())->toBeTrue();
});

it('honors queue filters without claiming jobs', function () {
    $queue = new NodeQueue(new ReleaseTestJob(), $this->node);
    $queue->createItem();
    expect($queue->when(false)->dispatch())->toBeFalse()->and($queue->items()[0]['executions'])->toBe(0);
});

it('rejects payload classes outside the worker allowlist', function () {
    JobSerializer::decode(JobSerializer::encode(new ReleaseTestJob()), []);
})->throws(UnexpectedValueException::class);

it('does not overwrite corrupted storage', function () {
    file_put_contents($this->node . '.json', '{broken');
    $queue = new NodeQueue(new ReleaseTestJob(), $this->node);
    expect(fn () => $queue->createItem())->toThrow(JsonException::class);
    expect(file_get_contents($this->node . '.json'))->toBe('{broken');
});

it('allows only one process to claim the same job', function () {
    $queue = new NodeQueue(new ReleaseTestJob(), $this->node);
    $id = $queue->createItem();
    $script = 'require $argv[1]; $q = new \\Codefy\\Framework\\Queue\\NodeQueue('
        . 'new \\Codefy\\Framework\\Tests\\Queue\\Fixtures\\ReleaseTestJob(), $argv[2]);'
        . '$item = $q->claimItem(); echo $item === false ? "none" : $item["_id"];';
    $processes = [];
    for ($index = 0; $index < 4; ++$index) {
        $process = proc_open([PHP_BINARY, '-r', $script, dirname(__DIR__, 2) . '/vendor/autoload.php', $this->node],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        expect(is_resource($process))->toBeTrue();
        fclose($pipes[0]);
        $processes[] = [$process, $pipes];
    }
    $claimed = [];
    foreach ($processes as [$process, $pipes]) {
        $claimed[] = stream_get_contents($pipes[1]);
        stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        expect(proc_close($process))->toBe(0);
    }
    expect(count(array_filter($claimed, fn ($value) => $value === $id)))->toBe(1)
        ->and(count(array_filter($claimed, fn ($value) => $value === 'none')))->toBe(3);
});
