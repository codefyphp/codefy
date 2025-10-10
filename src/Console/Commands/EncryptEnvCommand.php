<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Qubus\Http\Encryption\Env\File;

use function Codefy\Framework\Helpers\base_path;
use function file_get_contents;

class EncryptEnvCommand extends ConsoleCommand
{
    protected string $name = 'encrypt:env';

    protected string $description = 'Encrypts .env data and saves the encrypted data to a new file.';

    public function handle(): int
    {
        $this->terminalRaw(string: 'Encrypting data and creating file . . .');

        try {
            $file = file_get_contents(filename: base_path(path: '.enc.key'));

            $key = Key::loadFromAsciiSafeString(saved_key_string: $file);

            File::encrypt(base_path(path: '.env'), base_path(path: '.env.enc'), $key);
        } catch (BadFormatException | EnvironmentIsBrokenException $e) {
            return ConsoleCommand::FAILURE;
        }

        $this->terminalRaw(string: '<comment>.env.enc</comment> file created.');

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
