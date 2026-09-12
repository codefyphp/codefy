<?php

declare(strict_types=1);

use Codefy\Framework\Auth\Rbac\Resource\BaseStorageResource;
use Codefy\Framework\Console\Commands\GenerateEncryptionKeyFileCommand;
use Codefy\Framework\Support\Password;
use Codefy\Framework\Support\Server;
use Defuse\Crypto\Key;
use Symfony\Component\Console\Tester\CommandTester;

it('rejects circular role and permission inheritance', function () {
    $storage = new class extends BaseStorageResource {
        public function load(): void
        {
        }
        public function save(): void
        {
        }
    };
    $parent = $storage->addRole('parent');
    $child = $storage->addRole('child');
    $parent->addChild($child);
    expect(fn () => $child->addChild($parent))->toThrow(LogicException::class);
    expect(fn () => $parent->addChild($parent))->toThrow(LogicException::class);
    $read = $storage->addPermission('read');
    $write = $storage->addPermission('write');
    $write->addChild($read);
    expect(fn () => $read->addChild($write))->toThrow(LogicException::class);
});

it('does not trust forwarded HTTPS headers from arbitrary clients', function () {
    $saved = $_SERVER;
    try {
        $_SERVER = ['REMOTE_ADDR' => '192.0.2.1', 'HTTP_X_FORWARDED_PROTO' => 'https'];
        expect(Server::isSsl())->toBeFalse()->and(Server::isSsl(['192.0.2.1']))->toBeTrue();
        $_SERVER = ['HTTPS' => 'on'];
        expect(Server::isSsl())->toBeTrue();
    } finally {
        $_SERVER = $saved;
    }
});

it('uses stronger hashes while verifying legacy passwords', function () {
    $hash = Password::hash('test-password');
    expect(Password::verify('test-password', $hash))->toBeTrue()
        ->and(Password::verify('wrong', $hash))->toBeFalse()->and(Password::needsRehash($hash))->toBeFalse();
    if (defined('PASSWORD_ARGON2ID')) {
        $old = password_hash('test-password', PASSWORD_ARGON2ID, ['memory_cost' => 4096, 'time_cost' => 2, 'threads' => 2]);
        expect(Password::needsRehash($old))->toBeTrue()->and(Password::verify('test-password', $old))->toBeTrue();
        expect(Password::getInfo($hash)['options']['memory_cost'])->toBe(19456);
    }
});

it('creates a private key file once without overwriting it', function () {
    $directory = sys_get_temp_dir() . '/codefy-key-' . bin2hex(random_bytes(8));
    mkdir($directory, 0700);
    $app = codefy();
    $originalPath = $app->basePath();
    $property = new ReflectionProperty($app, 'basePath');
    $property->setValue($app, $directory);
    try {
        $tester = new CommandTester(new GenerateEncryptionKeyFileCommand($app));
        expect($tester->execute([]))->toBe(0);
        $key = file_get_contents($directory . '/.enc.key');
        expect(Key::loadFromAsciiSafeString($key))->toBeInstanceOf(Key::class)
            ->and(fileperms($directory . '/.enc.key') & 0777)->toBe(0600);
        expect($tester->execute([]))->toBe(1)->and(file_get_contents($directory . '/.enc.key'))->toBe($key);
    } finally {
        $property->setValue($app, $originalPath);
        if (is_file($directory . '/.enc.key')) {
            unlink($directory . '/.enc.key');
        }
        rmdir($directory);
    }
});
