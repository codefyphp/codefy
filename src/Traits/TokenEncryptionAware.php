<?php

declare(strict_types=1);

namespace Codefy\Framework\Traits;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException;
use Defuse\Crypto\Key;

trait TokenEncryptionAware
{
    //phpcs:disable
    protected ?string $key = null
    {
        get => $this->key ?? $this->configContainer->getConfigKey(key: 'app.crypto_key');
        set(null|string $key) => $this->key = $key;
    }
    //phpcs:enable

    /**
     * Sign the value.
     *
     * @param string $value
     * @return string
     * @throws EnvironmentIsBrokenException
     * @throws BadFormatException
     */
    protected function sign(string $value): string
    {
        return Crypto::encrypt($value, Key::loadFromAsciiSafeString($this->key));
    }

    /**
     * Unsign the value.
     *
     * @param string $value Encrypted value.
     * @return string Return the value if signature is valid.
     * @throws BadFormatException
     * @throws EnvironmentIsBrokenException
     * @throws WrongKeyOrModifiedCiphertextException
     */
    protected function unsign(string $value): string
    {
        return Crypto::decrypt($value, Key::loadFromAsciiSafeString($this->key));
    }

    /**
     * @param string $knownString
     * @param string $userString
     * @return bool
     */
    protected function compareTokens(string $knownString, string $userString): bool
    {
        return $knownString === $userString;
    }
}
