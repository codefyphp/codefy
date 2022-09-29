<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;

class UpCommand extends PhpMigCommand
{
    protected string $name = 'migrate:up';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                name: 'version',
                mode: InputArgument::REQUIRED,
                description: 'The version number for the migration.'
            )
            ->setDescription(description: 'Run a specific migration.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:up</info> command runs a specific migration
<info>php codex migrate:up 20111018185121</info>
EOT
            );
    }

    /**
     * @throws Exception
     */
    public function handle(): int
    {
        $this->bootstrap(input: $this->input, output: $this->output);

        $migrations = $this->getMigrations();
        $versions   = $this->getAdapter()->fetchAll();

        $version = $this->getArgument(key: 'version');

        if (in_array(needle: $version, haystack: $versions)) {
            $this->terminalInfo(string: 'Migration version already exists.');
            return 0;
        }

        if (!isset($migrations[$version])) {
            $this->terminalError(string: 'No migration by that version exists.');
            return 0;
        }

        $objectmap = $this->getObjectMap();
        $objectmap['phpmig.migrator']->up($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}
