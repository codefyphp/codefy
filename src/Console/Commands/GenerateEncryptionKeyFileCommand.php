<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;

class GenerateEncryptionKeyFileCommand extends ConsoleCommand
{
    protected string $name = 'generate:key:file';

    protected string $description = 'Generates a file with an encryption key.';

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function handle(): int
    {
        $this->terminalRaw(string: 'Generating encryption key . . .');

        $key = Key::createNewRandomKey()->saveToAsciiSafeString();

        $this->terminalRaw(string: 'Generating encryption key file . . .');

        $path = $this->codefy->basePath() . DIRECTORY_SEPARATOR . '.enc.key';
        if (file_exists($path) || is_link($path)) {
            $this->terminalRaw('<error>The encryption key file already exists.</error>');
            return self::FAILURE;
        }
        $mask = umask(0077);
        try {
            $file = @fopen($path, 'x');
        } finally {
            umask($mask);
        }
        if ($file === false) {
            $this->terminalRaw('<error>Unable to create .enc.key; it may already exist.</error>');
            return self::FAILURE;
        }
        try {
            if (fwrite($file, $key) !== strlen($key) || !fflush($file)) {
                throw new \RuntimeException('Unable to write the encryption key.');
            }
        } finally {
            fclose($file);
        }

        $this->terminalRaw(string: '<comment>.enc.key</comment> file created.');

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
