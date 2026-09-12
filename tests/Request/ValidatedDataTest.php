<?php

declare(strict_types=1);

use Codefy\Framework\Tests\Request\Fixtures\ExampleFormRequest;
use Codefy\Framework\Http\Request\FormDataRequest;
use Codefy\Framework\Tests\Request\Fixtures\TestSafeInputValidator;

beforeEach(fn () => codefy());

it('excludes unruled fields from validated data', function () {
    $request = new ExampleFormRequest(parsedBody: ['id' => '123', 'is_admin' => true]);
    expect($request->validated())->toBe(['id' => '123'])
        ->and($request->all())->toBe(['id' => '123', 'is_admin' => true]);
});

it('keeps empty selections empty', function () {
    $request = new ExampleFormRequest(parsedBody: ['id' => '123']);
    expect($request->only([])->all())->toBe([])->and($request->except(['id'])->all())->toBe([]);
});

it('revalidates immutable request clones', function () {
    $request = new ExampleFormRequest(parsedBody: ['id' => 'first']);
    expect($request->validated())->toBe(['id' => 'first']);
    expect($request->withParsedBody(['id' => 'second'])->validated())->toBe(['id' => 'second']);
});

it('hydrates form data without recursively validating itself', function () {
    $request = new class (parsedBody: ['id' => '123', 'is_admin' => true]) extends FormDataRequest {
        public string $id = '';
        protected function rules(): array { return ['id' => 'required|string']; }
    };
    expect($request->validated())->toBe(['id' => '123'])->and($request->id)->toBe('123');
});

it('restricts HTTP input validators to ruled fields', function () {
    $validator = TestSafeInputValidator::make(middleware_request(parsedBody: ['id' => '123', 'is_admin' => true]));
    expect($validator->validated())->toBe(['id' => '123'])->and($validator->only([])->all())->toBe([]);
});
