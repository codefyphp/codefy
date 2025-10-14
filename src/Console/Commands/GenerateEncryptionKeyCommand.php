<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;

use function sprintf;

class GenerateEncryptionKeyCommand extends ConsoleCommand
{
    protected string $name = 'generate:key';

    protected string $description = 'Generates a random encryption key.';

    /**
     * @throws EnvironmentIsBrokenException
     */
    public function handle(): int
    {
        $key = Key::createNewRandomKey()->saveToAsciiSafeString();

        $this->terminalRaw(string: sprintf(
            'Encryption Key: <comment>%s</comment>',
            $key
        ));

        // return value is important when using CI
        // to fail the build when the command fails
        // 0 = success, other values = fail
        return ConsoleCommand::SUCCESS;
    }
}
