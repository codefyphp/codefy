# Validation, DTOs, and pipelines

## Validated fields

Both `Http\Request\FormRequest` and `Validation\HttpInputValidator` return the validation engine's accepted data from `validated()`. Raw input remains available through `all()`.

```php
final class UpdateProfile extends FormRequest
{
    protected function rules(): array
    {
        return ['display_name' => 'required|string'];
    }
}

$request = new UpdateProfile(parsedBody: [
    'display_name' => 'Alex',
    'is_admin' => true,
]);
$request->validated(); // ['display_name' => 'Alex']
$request->all();       // Both submitted fields; not safe for automatic model updates.
```

`value()` and the typed accessors read validated data. A DTO using `fromValidatedData()` therefore sees the same field selection when it uses these methods. Add explicit rules for every field your DTO or persistence layer needs.

Validation-engine semantics still apply to nested arrays. A rule accepting an entire parent array can retain its contents; use specific nested rules and explicit application mapping when individual child keys need restriction. An empty ruleset does not turn all input into trusted data.

Empty `only([])` and `except()` selections remain empty instead of reloading the raw request. Selection clones discard cached validators. The protected `$data` cache is now nullable; subclasses that redeclare this internal property must use the compatible `?array` type. PSR request clones discard cached data and validators so changed parsed bodies, query parameters, or uploaded files are revalidated. A custom `validator(ValidationFactory $factory)` method is now actually invoked and must return a `Validation` instance.

`FormDataRequest::passedValidation()` hydrates declared properties directly from the successful validator result. It no longer calls `validated()` recursively. Override hooks carefully: a validation hook should not recursively trigger validation on the same instance.

## Pipeline builders

Pipes supplied through `PipelineBuilder::pipe()` are now included by `build()`. Builder additions still clone the builder, leaving the original unchanged. Built pipelines execute their configured pipes in order.

## Transaction ownership

A pipeline with `withTransaction()` resolves `PDO::class` from its own injected container. Register the PDO connection used by the work inside the pipeline. Successful work commits; a thrown `Throwable` rolls back before an `onFailure` handler runs. The existing `finally` callback executes on either outcome.

```php
$container->share($pdo);
$pipeline = new Pipeline($container);
$result = $pipeline->withTransaction()
    ->send($command)
    ->through($handler)
    ->thenReturn();
```

The pipeline refuses to begin when that connection already has a transaction. It does not roll back an outer transaction, and recursively entering the same active transactional pipeline throws `LogicException`. No nested savepoint support is implied. Use a nontransactional inner pipeline when an outer caller owns the transaction.

After commit, a later hook failure cannot roll back completed database work. External operations such as email or remote API calls also are not undone by a database rollback; keep those side effects outside the transaction or coordinate them through an application outbox.
