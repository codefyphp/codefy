<?php

declare(strict_types=1);

use Codefy\Framework\Pipeline\PipeAware;
use Codefy\Framework\Proxy\Codefy;
use Codefy\Framework\Pipeline\Pipeline;
use Codefy\Framework\tests\Pipes\PipeFour;
use Codefy\Framework\tests\Pipes\PipeOne;
use Codefy\Framework\tests\Pipes\PipeThree;
use Codefy\Framework\tests\Pipes\PipeTwo;
use Codefy\Framework\Tests\Pipes\ReplaceHateWithLove;
use Codefy\Framework\Tests\Pipes\SanitizeContent;
use PHPUnit\Framework\Assert;

use function Codefy\Framework\Helpers\app;

$app = codefy();

$pipeline = new Pipeline($app);

it('should run through an entire pipeline.', function () use ($pipeline) {
    $result1 = $pipeline
        ->send(0)
        ->through(
            function ($data, $next) {
                $piped = ++$data;
                return $next($piped);
            },

            function ($data, $next) {
                $piped = ++$data;
                return $next($piped);
            },
        )
        ->thenReturn();

    Assert::assertSame(2, $result1);

    $function1 = function ($piped, $next) {
        $piped = $piped + 1;

        return $next($piped);
    };

    $function2 = function ($piped, $next) {
        $piped = $piped + 2;

        return $next($piped);
    };
    $result2 = $pipeline
        ->send(0)
        ->through($function1, $function2)
        ->thenReturn();

    Assert::assertSame(3, $result2);
});

it('has exception handled by onFailure method.', function () use ($pipeline) {
    $result = $pipeline
        ->send('data')
        ->through(PipeThree::class)
        ->onFailure(function ($piped) {
            return 'error';
        })->then(function ($piped) {
            return $piped;
        });

    Assert::assertEquals('error', $result);
});

it('has exception handled by onFailure with piped data passed.', function () use ($pipeline) {
    $result = $pipeline
        ->send('data')
        ->through(PipeThree::class)
        ->onFailure(function ($piped, $exception) {
            Assert::assertInstanceOf(Exception::class, $exception);
            return $piped;
        })->then(function ($piped) {
            return $piped;
        });

    Assert::assertEquals('data', $result);
});

it('throws an exception from pipeline.', function () use ($pipeline) {
    try {
        $pipeline
            ->send('test')
            ->through(fn() => throw new RuntimeException('runtime'))
            ->then(function ($piped) {
                return $piped;
            });
    } catch (RuntimeException $e) {
        Assert::assertSame('runtime', $e->getMessage());
    }
});

it('accepts class strings as pipe.', function () use ($pipeline) {
    $result = $pipeline
        ->send('test data')
        ->through(PipeFour::class)
        ->thenReturn();

    Assert::assertSame('test data', $result);
});

it('accepts invokable class as pipe using PipelineBuilder.', function () {
    $builder = Codefy::$PHP->pipeline->pipe(new PipeFour());

    $pipeline = $builder->build();

    $result = $pipeline
        ->send('test data')
        ->thenReturn();

    Assert::assertSame('test data', $result);
});

it('runs without parameters.', function () use ($pipeline) {
    $result = $pipeline->run(PipeTwo::class);

    Assert::assertTrue($result);
});

it('returns passed data.', function () use ($pipeline) {
    $data = ['test' => 'yeah'];

    $result = $pipeline->run(PipeFour::class, $data);

    Assert::assertSame('yeah', $result['test']);
});

it('has customizable method.', function () use ($pipeline) {
    $result = $pipeline
        ->via('differentMethod')
        ->run(PipeOne::class);

    Assert::assertTrue($result);
});

it('passes through without pipes.', function () use ($pipeline) {
    $result = $pipeline
        ->send(10)
        ->thenReturn();

    Assert::assertEquals(10, $result);
});

it('uses pipes to process the pipeline.', function () use ($pipeline) {
    $result = $pipeline
        ->send(10)
        ->pipe(
            function ($p, $next) {
                $piped =  $p * 10;
                return $next($piped);
            },
        )->pipe(
            function ($p, $next) {
                $piped = $p - 10;
                return $next($piped);
            },
        )
        ->thenReturn();

    Assert::assertEquals(90, $result);
});

it('should send self through pipeline using PipeAware trait.', function () {
    $pipeline = new class ('one', 'two') {
        use PipeAware;

        public function __construct(public string $one, public string $two)
        {
        }
    };

    $pipeline
        ->pipeThrough(
            pipes: function ($data) {
                Assert::assertSame('one', $data->one);
                Assert::assertSame('two', $data->two);
            },
        )
        ->thenReturn();
});

it('should send self through pipeline using PipeAware trait and withTransaction.', function () {
    $pipeline = new class ('one', 'two') {
        use PipeAware;

        public function __construct(public string $one, public string $two)
        {
        }
    };

    $pipeline
        ->pipeThrough(
            pipes: function ($data) {
                Assert::assertSame('one', $data->one);
                Assert::assertSame('two', $data->two);
            },
            withTransaction: true
        )
        ->thenReturn();
});

it('should replace the word hate with love.', function () {
    $result = app(Pipeline::class)
        ->send('DDD is awesome, but I <strong>hate</strong> CQRS!')
        ->through(ReplaceHateWithLove::class)
        ->thenReturn();

    Assert::assertEquals('DDD is awesome, but I <strong>love</strong> CQRS!', $result);
});

it('should replace the word hate with love and sanitize the string.', function () {
    $result = app(Pipeline::class)
        ->send('DDD is awesome, but I <strong>hate</strong> CQRS!')
        ->through(ReplaceHateWithLove::class, SanitizeContent::class)
        ->thenReturn();

    Assert::assertEquals('DDD is awesome, but I love CQRS!', $result);
});
