<?php

declare(strict_types=1);

namespace Codefy\Foundation\Support;

use function defined;
use function Qubus\Security\Helpers\__observer;

use const PASSWORD_ARGON2ID;
use const PASSWORD_BCRYPT;

final class Password
{
    /**
     * Algorithm to use when hashing the password (i.e. PASSWORD_DEFAULT, PASSWORD_ARGON2ID).
     *
     * @return string Password algorithm.
     */
    private static function algorithm(): string
    {
        if (!defined(constant_name: 'PASSWORD_ARGON2ID')) {
            $algo = PASSWORD_BCRYPT;
        } else {
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
     * @return array Array of options.
     */
    private static function options(): array
    {
        /**
         * Filters the password_hash() options parameter.
         *
         * @param array $options Options to pass to password_hash() function.
         */
        return __observer()->filter->applyFilter(
            'password.hash.options',
            (array) ['memory_cost' => 1<<12, 'time_cost' => 2, 'threads' => 2]
        );
    }

    /**
     * Hashes a plain text password.
     *
     * @param string $password Plain text password
     * @return string Hashed password.
     */
    public static function hash(string $password): string
    {
        return password_hash(password: $password, algo: self::algorithm(), options: self::options());
    }
}
