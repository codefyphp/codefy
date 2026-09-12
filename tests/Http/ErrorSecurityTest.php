<?php

declare(strict_types=1);

use Codefy\Framework\Http\Middleware\Exception\Strategy\JsonHttpResponseStrategy;
use Codefy\Framework\Http\Middleware\Exception\Trait\HttpExceptionUtilityAware;
use Qubus\Exception\Http\HttpException;

it('allows only local error redirect paths', function (string $target, string $expected) {
    $utility = new class {
        use HttpExceptionUtilityAware;

        public function redirect(string $target): string
        {
            return $this->safeRedirectUri(middleware_request(path: '/current'), $target);
        }
    };
    expect($utility->redirect($target))->toBe($expected);
})->with([
    ['', '/'], ['https://evil.test', '/'], ['//evil.test', '/'], ['/\\evil.test', '/'],
    ['/%5cevil.test', '/'], ['/%2fevil.test', '/'], ["/\r\nevil.test", '/'],
    ['/form?retry=1', '/form?retry=1'], ['/current', '/'],
]);

it('hides production server errors and exception class names', function () {
    $app = codefy();
    $app->configContainer->setConfigKey('app', ['debug' => false]);
    $strategy = new JsonHttpResponseStrategy($app);
    $response = $strategy->createResponse(new HttpException('/', 'secret SQL password', 500), middleware_request());
    expect((string) $response->getBody())->not->toContain('secret')->not->toContain('type');
    $client = $strategy->createResponse(new HttpException('/', 'Missing field', 422), middleware_request());
    expect((string) $client->getBody())->toContain('Missing field');
});
