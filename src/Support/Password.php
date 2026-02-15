<?php

declare(strict_types=1);

namespace Codefy\Framework\Support;

use Qubus\Exception\Exception;

use function defined;
use function password_algos;
use function password_get_info;
use function password_hash;
use function password_needs_rehash;
use function password_verify;
use function Qubus\Security\Helpers\__observer;

use const PASSWORD_ARGON2ID;
use const PASSWORD_BCRYPT;

final class Password
{
    /**
     * Algorithm to use when hashing the password (i.e. PASSWORD_DEFAULT, PASSWORD_ARGON2ID).
     *
     * @return string Password algorithm.
     * @throws Exception
     */
    private static function algorithm(): string
    {
        $algo = PASSWORD_BCRYPT;

        if (defined(constant_name: 'PASSWORD_ARGON2ID')) {
            $algo = PASSWORD_ARGON2ID;
        }

        /**
         * Filters the password_hash() hashing algorithm.
         *
         * @param string $algo Hashing algorithm. Default: PASSWORD_ARGON2ID
         */
        return __observer()->filter->applyFilter('password.hash.algo', $algo);
    }

    /**
     * An associative array containing options.
     *
     * @return array<string, mixed> Array of options.
     * @throws Exception
     */
    private static function options(): array
    {
        $options = ['memory_cost' => 1 << 12, 'time_cost' => 2, 'threads' => 2];

        if (self::algorithm() === '2y') {
            $options = ['cost' => 12];
        }

        /**
         * Filters the password_hash() options parameter.
         *
         * @param array<string, mixed> $options Options to pass to password_hash() function.
         */
        return __observer()->filter->applyFilter(
            'password.hash.options',
            (array) $options
        );
    }

    /**
     * Hashes a plain text password.
     *
     * @param string $password Plain text password
     * @return string Hashed password.
     * @throws Exception
     */
    public static function hash(string $password): string
    {
        return password_hash(password: $password, algo: self::algorithm(), options: self::options());
    }

    /**
     * Checks if the given hash matches the given options.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Checks if the given hash matches the given algorithm and options provider.
     * If not, it is assumed that the hash needs to be rehashed.
     *
     * @param string $hash
     * @return bool
     * @throws Exception
     */
    public static function needsRehash(string $hash): bool
    {
        return password_needs_rehash(hash: $hash, algo: self::algorithm(), options: self::options());
    }

    /**
     * Get available password hashing algorithm IDs.
     *
     * @return array<array-key, string>
     */
    public static function algos(): array
    {
        return password_algos();
    }

    /**
     * Returns information about the given hash.
     *
     * @param string $password
     * @return array<array-key, mixed>
     */
    public static function getInfo(string $password): array
    {
        return password_get_info(hash: $password);
    }
}
