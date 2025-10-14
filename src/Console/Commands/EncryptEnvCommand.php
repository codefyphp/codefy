<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Defuse\Crypto\Exception\BadFormatException;
use Defuse\Crypto\Exception\EnvironmentIsBrokenException;
use Defuse\Crypto\Key;
use Qubus\Http\Encryption\Env\File;
use Symfony\Component\Console\Input\InputOption;

use function Codefy\Framework\Helpers\base_path;
use function file_exists;
use function file_get_contents;
use function sprintf;

class EncryptEnvCommand extends ConsoleCommand
{
    protected string $name = 'encrypt:env';

    protected string $description = 'Encrypts .env data and saves the encrypted data to a new file.';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addOption(
                name: 'env',
                shortcut: null,
                mode: InputOption::VALUE_REQUIRED,
                description: 'Set the environment file to encrypt.'
            );
    }

    public function handle(): int
    {
        $this->terminalRaw(string: 'Encrypting data and creating file . . .');

        try {
            $savedKeyString = file_get_contents(filename: base_path(path: '.enc.key'));
            $key = Key::loadFromAsciiSafeString(saved_key_string: $savedKeyString);

            if ($this->getOptions('env')) {
                $file = base_path(path: sprintf('.env.%s', $this->getOptions('env')));
            } else {
                $file = base_path(path: '.env');
            }

            if (!file_exists($file)) {
                $this->output->writeln(sprintf('<error>File %s does not exist.</error>', $file));
                return ConsoleCommand::FAILURE;
            }

            File::encrypt($file, base_path(path: '.env.enc'), $key);
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
