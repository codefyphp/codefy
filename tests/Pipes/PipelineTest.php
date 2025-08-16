<?php

declare(strict_types=1);

use Codefy\Framework\Pipeline\Pipeline;
use Codefy\Framework\Pipeline\PipelineBuilder;
use Codefy\Framework\tests\Pipes\PipeFour;
use Codefy\Framework\tests\Pipes\PipeOne;
use Codefy\Framework\tests\Pipes\PipeThree;
use Codefy\Framework\tests\Pipes\PipeTwo;
use PHPUnit\Framework\Assert;

$app = require_once __DIR__ . '/../vendor/bootstrap.php';
$pipeline = new Pipeline($app->return());

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

it('runs through an entire pipeline.', function () use ($pipeline) {
    $function1 = function ($piped, $next) {
        $piped = $piped + 1;

        return $next($piped);
    };

    $function2 = function ($piped, $next) {
        $piped = $piped + 2;

        return $next($piped);
    };
    $result = $pipeline
            ->send(0)
            ->through($function1, $function2)
            ->thenReturn();

    Assert::assertSame(3, $result);
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
    $builder = (new PipelineBuilder())
        ->pipe(new PipeFour());

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
