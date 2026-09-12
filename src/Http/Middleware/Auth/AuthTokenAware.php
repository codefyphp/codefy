<?php

declare(strict_types=1);

namespace Codefy\Framework\Http\Middleware\Auth;

use Codefy\Framework\Traits\TokenEncryptionAware;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use JsonException;

trait AuthTokenAware
{
    use TokenEncryptionAware;

    /**
     * @throws BadFormatException
     * @throws JsonException
     * @throws EnvironmentIsBrokenException
     */
    protected function encryptAuthToken(string $token, int $ttl): string
    {
        if ($token === '' || $ttl <= 0) {
            throw new \InvalidArgumentException('Authentication cookies require a token and a positive lifetime.');
        }
        return $this->sign(json_encode([
            'version' => 1, 'token' => $token, 'expires' => time() + $ttl,
        ], JSON_THROW_ON_ERROR));
    }

    /**
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     */
    protected function decryptAuthToken(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return null;
        }
        try {
            $data = json_decode($this->unsign($value), true, 8, JSON_THROW_ON_ERROR);
        } catch (WrongKeyOrModifiedCiphertextException | JsonException) {
            return null;
        }
        if (
            !is_array($data) || ($data['version'] ?? null) !== 1
            || !is_string($data['token'] ?? null) || $data['token'] === ''
            || !is_int($data['expires'] ?? null) || $data['expires'] <= time()
        ) {
            return null;
        }
        return $data['token'];
    }
}
