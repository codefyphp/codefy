<?php

declare(strict_types=1);

namespace Codefy\Framework\Console\Commands;

use Codefy\Framework\Console\ConsoleCommand;
use Qubus\Exception\Exception;
use Symfony\Component\Console\Input\InputArgument;

class RedoCommand extends PhpMigCommand
{
    protected string $name = 'migrate:redo';

    protected function configure(): void
    {
        parent::configure();

        $this
            ->addArgument(
                name: 'version',
                mode: InputArgument::REQUIRED,
                description: 'The version number for the migration'
            )
            ->setDescription(description: 'Redo a specific migration.')
            ->setHelp(
                help: <<<EOT
The <info>migrate:redo</info> command redo a specific migration
<info>php codex migrate:redo 20111018185412</info>
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

        if (!in_array(needle: $version, haystack: $versions)) {
            return ConsoleCommand::SUCCESS;
        }

        if (!isset($migrations[$version])) {
            return ConsoleCommand::SUCCESS;
        }

        $objectmap = $this->getObjectMap();
        $objectmap['phpmig.migrator']->down($migrations[$version]);
        $objectmap['phpmig.migrator']->up($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}
