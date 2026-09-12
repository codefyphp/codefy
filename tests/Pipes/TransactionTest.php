<?php

declare(strict_types=1);

use Codefy\Framework\Pipeline\Pipeline;
use Codefy\Framework\Pipeline\PipelineBuilder;
use Qubus\Injector\ServiceContainer;

function transactional_test_pipeline(PDO $connection): Pipeline
{
    $container = Mockery::mock(ServiceContainer::class);
    $container->shouldReceive('make')->with(PDO::class)->andReturn($connection);
    return new Pipeline($container)->withTransaction();
}

it('commits successful pipelines and rolls back failures', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->exec('CREATE TABLE entries (id INTEGER)');
    transactional_test_pipeline($pdo)->then(function () use ($pdo) { $pdo->exec('INSERT INTO entries VALUES (1)'); });
    expect((int) $pdo->query('SELECT COUNT(*) FROM entries')->fetchColumn())->toBe(1);
    expect(fn () => transactional_test_pipeline($pdo)->then(function () use ($pdo) {
        $pdo->exec('INSERT INTO entries VALUES (2)');
        throw new RuntimeException('failure');
    }))->toThrow(RuntimeException::class);
    expect((int) $pdo->query('SELECT COUNT(*) FROM entries')->fetchColumn())->toBe(1)->and($pdo->inTransaction())->toBeFalse();
});

it('does not roll back a transaction it did not begin', function () {
    $pdo = new PDO('sqlite::memory:');
    $pdo->beginTransaction();
    expect(fn () => transactional_test_pipeline($pdo)->thenReturn())->toThrow(LogicException::class);
    expect($pdo->inTransaction())->toBeTrue();
    $pdo->rollBack();
});

it('keeps builder pipes and leaves the original builder unchanged', function () {
    codefy();
    $builder = new PipelineBuilder();
    $piped = $builder->pipe(fn ($value, $next) => $next($value + 1));
    expect($piped->build()->send(1)->thenReturn())
        ->toBe(2)
        ->and($builder->build()->send(1)->thenReturn())
        ->toBe(1);
});
