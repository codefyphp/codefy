<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;

use function Codefy\Framework\Helpers\base_path;
use function file_put_contents;

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

        file_put_contents(base_path(path: '.enc.key'), $key);

        $this->terminalRaw(string: '<comment>.enc.key</comment> file created.');

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
