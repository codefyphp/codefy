<?php

use Codefy\Framework\Auth\UserSession;
use PHPUnit\Framework\Assert;

it('should return null as default for token property.', function () {
    $token = new UserSession()->token;

    Assert::assertSame(expected: null, actual: $token);
});

it('should throw error when setting token property.', function () {
    $usession = new UserSession();
    $usession->token = 'test';
})->throws(Error::class);

it('should set token and return a UserSession object.', function () {
    $usession = new UserSession();
    $usession->withToken(token: '426d9e8f-b8f7-4be0-a383-324ee3c3ea4d');

    Assert::assertSame(expected: '426d9e8f-b8f7-4be0-a383-324ee3c3ea4d', actual: $usession->token);
    Assert::assertInstanceOf(expected: UserSession::class, actual: $usession);
    Assert::assertIsObject($usession);
});

it('should be an empty token after clearing.', function () {
    $usession = new UserSession();
    $usession->withToken(token: '426d9e8f-b8f7-4be0-a383-324ee3c3ea4d');

    Assert::assertFalse(condition: $usession->isEmpty());

    $usession->clear();

    Assert::assertTrue(condition: $usession->isEmpty());
});
