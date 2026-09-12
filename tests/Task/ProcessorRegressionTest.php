<?php

declare(strict_types=1);

use Codefy\Framework\Scheduler\Mutex\CacheLocker;
use Codefy\Framework\Scheduler\Mutex\FileLocker;
use Codefy\Framework\Scheduler\Processor\Callback;
use Codefy\Framework\Scheduler\Processor\Shell;
use Codefy\Framework\Scheduler\Schedule;
use Psr\EventDispatcher\EventDispatcherInterface;
use Qubus\Cache\InMemoryCache;
use Qubus\Support\DateTime\QubusDateTimeZone;

it('runs callback schedules with default constructor options', function () {
    $processor = new Callback(new CacheLocker(new InMemoryCache()), fn () => 'done');
    expect($processor->isDue())->toBeTrue()->and($processor->run())->toBe('done');
});

it('releases callback locks after failures in before hooks', function () {
    $locker = new CacheLocker(new InMemoryCache());
    $processor = new Callback($locker, fn () => 'done');
    $processor->onlyOneInstance()->before(fn () => throw new TypeError('failure'));
    expect(fn () => $processor->run())->toThrow(TypeError::class);
    expect($locker->hasLock($processor))->toBeFalse();
});

it('executes foreground shell arguments and reports nonzero exit status', function () {
    $locker = new CacheLocker(new InMemoryCache());
    $processor = new Shell($locker, escapeshellarg(PHP_BINARY), ['-r' => 'exit(7);']);
    expect($processor->runInForeground()->run())->toBeFalse();
});

it('continues a schedule after PHP errors and exposes failed processors', function () {
    $dispatcher = Mockery::mock(EventDispatcherInterface::class);
    $schedule = new Schedule($dispatcher, new QubusDateTimeZone('UTC'), new CacheLocker(new InMemoryCache()));
    $schedule->command(fn () => throw new TypeError('failure'));
    $schedule->command(fn () => 'done');
    $schedule->run();
    expect($schedule->executedProcessors)->toHaveCount(1)->and($schedule->failedProcessors)->toHaveCount(1)
        ->and($schedule->failedProcessors[0]->exception)->toBeInstanceOf(TypeError::class);
});

it('uses exclusive owned file locks', function () {
    $directory = sys_get_temp_dir() . '/codefy-lock-' . bin2hex(random_bytes(8));
    $one = new FileLocker($directory);
    $two = new FileLocker($directory);
    $processor = new Callback($one, fn () => 'done');
    try {
        expect($one->tryLock($processor))->toBeTrue()->and($two->tryLock($processor))->toBeFalse()
            ->and($two->unlock($processor))->toBeFalse();
        expect($one->unlock($processor))->toBeTrue()->and($two->tryLock($processor))->toBeTrue();
    } finally {
        $two->unlock($processor);
        foreach (glob($directory . '/*') as $file) { unlink($file); }
        rmdir($directory);
    }
});

it('quotes PHP script paths and supplies positional arguments exactly once', function () {
    $directory = sys_get_temp_dir() . '/codefy script ' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    $script = $directory . '/job;name.php';
    file_put_contents($script, '<?php exit(count($argv) === 2 && $argv[1] === "one argument" ? 0 : 9);');
    try {
        $schedule = new Schedule(Mockery::mock(EventDispatcherInterface::class), new QubusDateTimeZone('UTC'), new CacheLocker(new InMemoryCache()));
        expect($schedule->php($script, args: ['one argument'])->runInForeground()->run())->toBeTrue();
    } finally {
        unlink($script);
        rmdir($directory);
    }
});
