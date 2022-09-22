<?php

declare(strict_types=1);

namespace Codefy\Foundation\Console\Commands;

use Codefy\Foundation\Console\ConsoleCommand;
use Qubus\Exception\Exception;

class RedoCommand extends PhpMigCommand
{
    protected string $name = 'migrate:redo';

    protected string $description = 'Redo a specific migration.';

    protected string $help = <<<EOT
The <info>migrate:redo</info> command redo a specific migration
<info>php codex migrate:redo 20111018185412</info>
EOT;

    protected array $args = [
        [
            'version',
            'required',
            'The version number for the migration.'
        ],
    ];

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

        $this->config->getConfigKey('database.phpmig.migrator')->down($migrations[$version]);
        $this->config->getConfigKey('database.phpmig.migrator')->up($migrations[$version]);

        return ConsoleCommand::SUCCESS;
    }
}
